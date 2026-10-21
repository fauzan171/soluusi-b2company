<?php

namespace App\Http\Controllers;

use App\Models\ConsultationSession;
use App\Models\Voucher;
use App\Services\Klinik\SplitSettlementService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Midtrans webhook + voucher logic (mirror B2C + B2B FIX):
 * - FIX E.2 #1 [SECURITY]: signature_key verify SEBELUM proses (B2C hole).
 * - FIX E.2 #2 [DATA-LOSS]: gcal non-fatal — status scheduled regardless.
 * - B2B ADD: split_settlements ledger (Build-NEW #1), white-label mails.
 */
class MidtransController extends Controller
{
    /**
     * Webhook Midtrans (POST api/midtrans-callback).
     */
    public function callback(Request $request)
    {
        // FIX [SECURITY]: verify signature dulu — reject + log mismatch.
        $request->validate([
            'order_id' => ['required', 'string', 'max:255'],
            'status_code' => ['required', 'string'],
            'gross_amount' => ['required', 'regex:/^\d+(\.0{1,2})?$/'],
            'signature_key' => ['required', 'string'],
        ]);
        $serverKey = config('midtrans.server_key');
        abort_unless(is_string($serverKey) && trim($serverKey) !== '', 503, 'Payment verification is not configured.');
        $expected = hash('sha512', $request->order_id . $request->status_code . $request->gross_amount . $serverKey);
        if (!hash_equals($expected, (string) $request->signature_key)) {
            Log::warning('Midtrans callback: signature mismatch', ['order_id' => $request->order_id]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $session = ConsultationSession::where('order_number', $request->order_id)->first();
        if (!$session) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        abort_unless((int) $request->gross_amount === (int) $session->amount, 422, 'Gross amount does not match order.');

        // Sesi yang sudah dibatalkan/kedaluwarsa tidak boleh dihidupkan kembali oleh
        // notifikasi susulan: slotnya sudah dilepas dan bisa dipesan pasien lain.
        $terminal = [
            ConsultationSession::PAYMENT_STATUS_CANCELED,
            ConsultationSession::PAYMENT_STATUS_FAILED,
            ConsultationSession::PAYMENT_STATUS_REFUNDED,
        ];
        if (in_array($session->payment_status, $terminal, true)
            && in_array($request->transaction_status, ['settlement', 'capture', 'pending'], true)) {
            $session->needs_ops_review = true;
            $session->save();
            Log::warning('Midtrans callback: pembayaran masuk untuk sesi terminal, perlu review ops', [
                'order_id' => $request->order_id,
                'payment_status' => $session->payment_status,
                'transaction_status' => $request->transaction_status,
            ]);

            return response()->json(['message' => 'Session already closed; flagged for ops review']);
        }

        if ($session->payment_status === ConsultationSession::PAYMENT_STATUS_VERIFIED
            && !in_array($request->transaction_status, ['settlement', 'capture', 'refund'], true)) {
            return response()->json(['message' => 'Already paid; stale notification ignored']);
        }
        abort_unless(in_array($request->transaction_status, ['settlement', 'capture', 'pending', 'expire', 'deny', 'cancel', 'failure', 'refund'], true), 422, 'Unknown transaction status.');

        // Idempotent: settlement berulang tetap memastikan split ledger ada.
        // Percobaan pertama bisa sudah menyimpan payment namun gagal menulis
        // ledger; retry webhook harus dapat memulihkannya.
        if ($session->payment_status === 'verified' && in_array($request->transaction_status, ['settlement', 'capture'])) {
            try {
                SplitSettlementService::record($session);
            } catch (Exception $e) {
                Log::error('Split settlement retry gagal session #' . $session->id . ': ' . $e->getMessage());
            }
            return response()->json(['message' => 'Already processed']);
        }

        $session->payment_method = $request->payment_type ?? 'Midtrans';

        switch ($request->transaction_status) {
            case 'capture':
                if ($request->fraud_status !== 'accept') {
                    $session->payment_status = 'waiting';
                    break;
                }
                // fallthrough ke settle flow
            case 'settlement':
                $session->payment_status = 'verified';
                $session->payment_at = $request->transaction_time;
                $session->payment = $request->gross_amount;
                // FIX [DATA-LOSS]: scheduled SEKARANG — gcal best-effort di bawah,
                // tidak lagi blocker (B2C bug: gcal throw → session stuck waiting).
                $session->status = 'scheduled';
                $session->save();

                // Link terpakai begitu lunas (kalau belum di-set saat checkout).
                if ($session->checkout_link_id) {
                    \App\Models\Klinik\CheckoutLink::whereKey($session->checkout_link_id)
                        ->whereNull('used_at')
                        ->update(['used_at' => now()]);
                }

                // B2B ADD: split ledger (net→Soluusi, margin→klinik). Idempotent.
                try {
                    SplitSettlementService::record($session);
                } catch (Exception $e) {
                    Log::error('Split settlement gagal session #' . $session->id . ': ' . $e->getMessage());
                }

                // gcal best-effort (try/catch; google_calendar_event_id nullable).
                try {
                    $calendarService = new \App\Services\GoogleCalendarService($session->klinik?->gcal_id);
                    $eventData = $calendarService->createEvent($session);
                    if ($eventData) {
                        $session->link_meeting = $eventData['meet_link'] ?? $eventData['event_link'];
                        $session->google_calendar_event_id = $eventData['event_id'] ?? null;
                        $session->save();
                    }
                } catch (Exception $e) {
                    Log::error('Google Calendar event creation failed (non-fatal): ' . $e->getMessage());
                }

                // White-label receipt mails (§17.5).
                try {
                    if ($session->customer_email) {
                        Mail::to($session->customer_email)->send(new \App\Mail\Klinik\Client\SudahBayar($session));
                    }
                    if ($session->consultant?->user?->email) {
                        Mail::to($session->consultant->user->email)->send(new \App\Mail\Klinik\Konsultan\SudahBayar($session));
                    }
                } catch (Exception $e) {
                    Log::error('Mail SudahBayar gagal (non-fatal): ' . $e->getMessage());
                }
                break;
            case 'pending':
                $session->payment_status = 'waiting';
                break;
            case 'expire':
                $session->payment_status = 'canceled';
                $session->status = 'canceled';
                $session->payment = 0;
                break;
            case 'cancel':
                $session->payment_status = 'canceled';
                $session->status = 'canceled';
                $session->payment = 0;
                break;
            case 'refund':
                // Uang kembali ke pasien: hutang split dibatalkan, bukan dihapus.
                $session->payment_status = ConsultationSession::PAYMENT_STATUS_REFUNDED;
                $session->status = 'canceled';
                $session->payment = 0;
                SplitSettlementService::reverse($session);
                break;
            case 'deny':
            case 'failure':
                $session->payment_status = 'failed';
                $session->payment = 0;
                break;
            default:
                $session->payment_status = 'waiting';
        }

        $session->save();

        return response()->json(['message' => 'success']);
    }

    public function canceledTransaction(Request $request)
    {
        $request->validate([
            'order_id' => ['required', 'string', 'max:255'],
            'status_code' => ['required', 'string'],
            'gross_amount' => ['required', 'regex:/^\d+(\.0{1,2})?$/'],
            'signature_key' => ['required', 'string'],
        ]);
        $serverKey = config('midtrans.server_key');
        abort_unless(is_string($serverKey) && trim($serverKey) !== '', 503, 'Payment verification is not configured.');
        $expected = hash('sha512', $request->order_id . $request->status_code . $request->gross_amount . $serverKey);
        if (!hash_equals($expected, (string) $request->signature_key)) {
            Log::warning('Midtrans cancel callback: signature mismatch', ['order_id' => $request->order_id]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $session = ConsultationSession::where('order_number', $request->order_id)->first();

        if (!$session) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        abort_unless((int) $request->gross_amount === (int) $session->amount, 422, 'Gross amount does not match order.');
        if ($session->payment_status !== 'verified') {
            $session->payment_status = 'canceled';
            $session->status = 'canceled';
            $session->save();
        }

        return response()->json(['message' => 'success']);
    }

    /**
     * Voucher nominal (§17.6 mirror verbatim + B2B free_trial §15.12).
     * Return [bool $error, string|int $messageOrNominal]. $price = harga yang
     * dikenakan voucher (final price untuk klinik flow).
     */
    public static function nominalWithVoucher(Voucher $voucher, $price)
    {
        if (!Carbon::now()->between($voucher->start_date, $voucher->end_date)) {
            return [true, 'Voucher tidak dapat digunakan'];
        }

        if (!$voucher->is_active || $voucher->usage >= $voucher->kuota) {
            return [true, 'Voucher sudah habis'];
        }

        if ($voucher->min_transaction > $price) {
            return [true, 'Minimal transaksi tidak memenuhi syarat'];
        }

        if ($voucher->type == 'diskon') {
            if ($voucher->max_discount < $voucher->nominal) {
                return [true, 'Maksimal diskon tidak memenuhi syarat'];
            }
            $nominal = $price * $voucher->discount_percentage / 100;
            $nominal = $nominal > $voucher->max_discount ? $voucher->max_discount : $nominal;
        } elseif ($voucher->type == 'potongan') {
            if ($voucher->max_discount < $voucher->nominal) {
                return [true, 'Maksimal diskon tidak memenuhi syarat'];
            }
            $nominal = $voucher->nominal;
        } elseif ($voucher->type == 'free_trial') {
            // §15.12 DECIDED: free trial 10 min, nominal 0 → full discount.
            $nominal = $price;
        } else {
            return [true, 'Tipe voucher tidak dikenal'];
        }

        return [false, $nominal];
    }

    /** Slot availability (§17.7 mirror). */
    public function checkScheduleAvailability(Request $request)
    {
        $request->validate(['schedule_id' => 'required|exists:schedules,id']);

        $existingSession = ConsultationSession::where('schedule_id', $request->schedule_id)
            ->blocking()
            ->first();

        return response()->json([
            'available' => !$existingSession,
            'message' => $existingSession ? 'Jadwal sudah dipesan' : 'Jadwal tersedia'
        ]);
    }

    /** Expiry sweep (§17.7 mirror). Dipanggil juga dari scheduler. */
    public function updateExpiredPayments()
    {
        ConsultationSession::where('payment_status', 'waiting')
            ->where('expired_at', '<', now())
            ->update([
                'payment_status' => 'canceled',
                'status' => 'canceled'
            ]);
    }
}
