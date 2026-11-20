<?php

namespace App\Services\Klinik;

use App\Http\Controllers\MidtransController;
use App\Models\ConsultationSession;
use App\Models\Consultant;
use App\Models\ConsultantPackage;
use App\Models\Klinik\CheckoutLink;
use App\Models\Klinik\Klinik;
use App\Models\Klinik\MarkupRule;
use App\Models\Schedule;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Midtrans\Config;
use Midtrans\Snap;

/**
 * T4.2: Business logic checkout-guest (Model C) di luar controller.
 * Controller menyisakan HTTP concern: validasi, abort_unless, view/redirect.
 *
 * Static style — konsisten dengan SplitSettlementService & MarkupService.
 *
 * Return contract: method start* mengembalikan salah satu dari:
 *   - ['error' => string]              → back()->with('error', ...)->withInput()
 *   - ['redirect' => string]           → redirect(url) — Snap URL / dummy URL
 *   - ['no_config' => true]            → pembayaran belum dikonfigurasi
 *   - ['reserved' => false]            → slot/link sudah terpakai
 */
class CheckoutFlow
{
    /**
     * Build checkout link dari form clinic-pay: validasi consultant/schedule/
     * package milik klinik + mode match + slot bebas + markup cap, lalu create
     * link. Belum mengklaim link — itu terjadi di startPayment → claimLink.
     *
     * @return array{error?: string, link?: CheckoutLink}
     */
    public static function createClinicLink(array $data, Klinik $klinik): array
    {
        $consultant = Consultant::where('status', Consultant::ACCEPT)
            ->where('is_active', true)
            ->findOrFail($data['consultant_id']);
        $schedule = Schedule::where('klinik_id', $klinik->id)
            ->where('consultant_id', $consultant->id)
            ->where('status', Schedule::STATUS_CONFIRMED)
            ->where('started_at', '>', now())
            ->findOrFail($data['schedule_id']);
        $package = ConsultantPackage::where('consultant_id', $consultant->id)
            ->findOrFail($data['consultant_package_id']);

        if (($schedule->mode ?? Schedule::MODE_ONLINE) !== ($package->mode ?? ConsultantPackage::MODE_ONLINE)) {
            return ['error' => 'Paket dan jadwal harus memakai mode yang sama. Pilih paket yang sesuai dengan jadwal.'];
        }

        if (self::slotIsBooked($schedule->id) || self::slotHasActiveLink($schedule->id)) {
            return ['error' => 'Jadwal sudah tidak tersedia. Pilih jadwal lain.'];
        }

        $net = (int) $package->price;
        $markup = (int) (optional(
            MarkupRule::where('klinik_id', $klinik->id)->where('consultant_package_id', $package->id)->first()
        )->markup_amount ?? 0);
        $final = $net + $markup;

        if (!MarkupService::isValid($net, $final)) {
            return ['error' => 'Harga paket belum tersedia. Hubungi klinik untuk memperbarui harga paket ini.'];
        }

        $link = CheckoutLink::create([
            'token' => CheckoutLink::generateToken(),
            'klinik_id' => $klinik->id,
            'consultant_id' => $consultant->id,
            'schedule_id' => $schedule->id,
            'consultant_package_id' => $package->id,
            'net_price' => $net,
            'markup_amount' => $markup,
            'final_price' => $final,
            'created_by' => null,
            'expires_at' => now()->addHour(),
            // Tidak set used_at: link langsung dikonsumsi startPayment() → claimLink()
            // yang menandainya. Menandai di sini membuat claimLink selalu gagal.
        ]);

        return ['link' => $link];
    }

    /**
     * Main payment flow: voucher math → slot re-check → Snap URL → reserve
     * session atomically → kirim mail instruksi bayar.
     *
     * @return array{error?: string, redirect?: string, no_config?: bool, reserved?: bool}
     */
    public static function startPayment(array $data, CheckoutLink $link): array
    {
        $package = $link->package;
        $gross = $link->final_price;

        [$voucher, $voucherAmount, $gross, $error] = self::applyVoucher($data, $link, $gross);
        if ($error) {
            return ['error' => $error];
        }

        // Slot lock re-check (race guard — §17.7).
        if (self::slotIsBooked($link->schedule_id)) {
            return ['error' => 'Jadwal sudah tidak tersedia. Hubungi klinik.'];
        }

        // Order number konsisten (§17.1 B2B CHANGE) — idempotent retry.
        $orderNumber = 'KB-' . $link->id . '-' . now()->format('YmdHis');

        if (blank(config('midtrans.server_key'))) {
            if (self::dummyEnabled()) {
                return self::startDummyPayment($data, $link, $package, $orderNumber, $gross, $voucher, $voucherAmount);
            }

            return ['no_config' => true];
        }

        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('app.env') === 'production';
        Config::$is3ds = (bool) config('midtrans.is3ds');

        $nameParts = explode(' ', trim($data['customer_name']), 2);

        $snapData = [
            'transaction_details' => [
                'order_id' => $orderNumber,
                'gross_amount' => $gross,
            ],
            'customer_details' => [
                'first_name' => $nameParts[0],
                'last_name' => $nameParts[1] ?? ' ',
                'email' => $data['customer_email'],
                'phone' => $data['customer_phone'],
            ],
            'expiry' => [
                'start_time' => date('Y-m-d H:i:s O'),
                'unit' => 'hour',
                'duration' => 1,
            ],
            'callbacks' => [
                'finish' => route('checkout.status', $link->token), // white-label finish page
            ],
        ];

        try {
            $tokenUrl = Snap::getSnapUrl($snapData);
        } catch (\Exception $e) {
            return ['error' => 'Gagal membuat transaksi pembayaran: ' . $e->getMessage()];
        }

        if (!$session = self::reserveSession($data, $link, $package, $orderNumber, $gross, $voucher, $voucherAmount, $tokenUrl)) {
            return ['reserved' => false];
        }

        // F6: increment usage sudah di dalam reserveSession (transaksi sama).

        // White-label payment instruction mail (§17.5). Non-fatal — mailer sync
        // gagal tidak boleh membatalkan sesi yang sudah dibuat + slot terkunci.
        try {
            Mail::to($session->customer_email)->send(new \App\Mail\Klinik\Client\Bayar($session));
        } catch (\Exception $e) {
            Log::error('Mail Bayar gagal session #' . $session->id . ': ' . $e->getMessage());
        }

        return ['redirect' => $tokenUrl];
    }

    /**
     * Dummy payment untuk local dev (midtrans tanpa server key).
     *
     * @return array{error?: string, redirect?: string, reserved?: bool}
     */
    public static function startDummyPayment(
        array $data,
        CheckoutLink $link,
        ConsultantPackage $package,
        string $orderNumber,
        int $gross,
        ?Voucher $voucher,
        int $voucherAmount
    ): array {
        $dummyUrl = route('checkout.dummyPayment', $link->token);

        $session = self::reserveSession($data, $link, $package, $orderNumber, $gross, $voucher, $voucherAmount, $dummyUrl, 'DUMMY-' . $link->id);
        if (!$session) {
            return ['reserved' => false];
        }

        // F6: increment usage sudah di dalam reserveSession (transaksi sama).

        return ['redirect' => $dummyUrl];
    }

    /**
     * Voucher math (§5.5): voucher menurunkan Soluusi net (Soluusi absorbs),
     * margin klinik tetap.
     *
     * @return array{0: ?Voucher, 1: int, 2: int, 3: ?string} [voucher, voucherAmount, gross, error]
     */
    public static function applyVoucher(array $data, CheckoutLink $link, int $gross): array
    {
        if (empty($data['voucher'])) {
            return [null, 0, $gross, null];
        }

        $voucher = Voucher::where('code', $data['voucher'])->first();
        if (!$voucher) {
            return [null, 0, $gross, 'Voucher tidak ditemukan'];
        }

        [$error, $nominal] = MidtransController::nominalWithVoucher($voucher, $gross);
        if ($error) {
            return [null, 0, $gross, $nominal];
        }

        // Cast + max-guards: kolom desimal B2C nullable/legacy; tanpa ini
        // `min()` pada nominal > final menghasilkan gross negatif dan
        // ledger split menolak menyeimbangkan (`net + margin !== amount`).
        $voucherAmount = max(0, (int) min($nominal, $link->net_price, max(0, $gross - ($link->markup_amount ?? 0))));

        return [$voucher, $voucherAmount, $gross - $voucherAmount, null];
    }

    /**
     * Klaim link + kunci slot + buat waiting-session dalam SATU transaksi.
     * Dua request bersamaan: yang kalah gagal di claimLink/slot re-check dan
     * tidak meninggalkan link terbakar tanpa sesi (race §17.7).
     */
    public static function reserveSession(
        array $data,
        CheckoutLink $link,
        ConsultantPackage $package,
        string $orderNumber,
        int $gross,
        ?Voucher $voucher,
        int $voucherAmount,
        string $paymentUrl,
        ?string $snapToken = null
    ): ?ConsultationSession {
        return DB::transaction(function () use ($data, $link, $package, $orderNumber, $gross, $voucher, $voucherAmount, $paymentUrl, $snapToken) {
            Schedule::whereKey($link->schedule_id)->lockForUpdate()->firstOrFail();

            // Cek slot SEBELUM klaim: tidak ada tulisan, jadi return null tidak
            // membakar link. Lock dipegang sampai transaksi selesai.
            if (ConsultationSession::where('schedule_id', $link->schedule_id)->blocking()->exists()) {
                return null;
            }
            if (!self::claimLink($link)) {
                return null;
            }

            $session = self::createWaitingSession($data, $link, $package, $orderNumber, $gross, $voucher, $voucherAmount, $paymentUrl, $snapToken);

            // F6: increment usage dalam transaksi yang sama — kalau crash di
            // tengah, semua rollback (sesi hilang + link bebas lagi), jadi tidak
            // ada sesi jalan tanpa usage tercatat (voucher over-limit).
            if ($voucher) {
                $voucher->increment('usage');
            }

            return $session;
        });
    }

    public static function createWaitingSession(
        array $data,
        CheckoutLink $link,
        ConsultantPackage $package,
        string $orderNumber,
        int $gross,
        ?Voucher $voucher,
        int $voucherAmount,
        string $paymentUrl,
        ?string $snapToken = null
    ): ConsultationSession {
        return ConsultationSession::create([
            'schedule_id' => $link->schedule_id,
            'user_id' => null, // Model C — guest
            'consultant_id' => $link->consultant_id,
            'consultant_package_id' => $package->id,
            'klinik_id' => $link->klinik_id,
            'checkout_link_id' => $link->id,
            'complaint' => $data['complaint'] ?? null,
            'status' => 'waiting',
            'order_number' => $orderNumber,
            'payment_status' => 'waiting',
            'order_at' => now(),
            'payment' => '0',
            'package_price' => $link->final_price,
            'voucher_amount' => $voucherAmount,
            'amount' => $gross,
            'voucher_id' => $voucher?->id,
            'snaptoken' => $snapToken ?: basename($paymentUrl),
            'expired_at' => now()->addHour(),
            'url_snap' => $paymentUrl,
            // Guest PII (Model C)
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'],
            'customer_dob' => $data['customer_dob'],
            'customer_regency_id' => $data['customer_regency_id'],
            'customer_gender' => $data['customer_gender'],
        ]);
    }

    /** Atomic single-use claim: dua request bersamaan hanya satu yang lolos. */
    public static function claimLink(CheckoutLink $link): bool
    {
        return CheckoutLink::whereKey($link->id)->whereNull('used_at')->update(['used_at' => now()]) === 1;
    }

    public static function slotIsBooked(int $scheduleId): bool
    {
        return ConsultationSession::where('schedule_id', $scheduleId)
            ->blocking()
            ->exists();
    }

    public static function slotHasActiveLink(int $scheduleId): bool
    {
        return CheckoutLink::where('schedule_id', $scheduleId)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->exists();
    }

    /** Dummy flow hanya di local, atau flag eksplisit midtrans.dummy_enabled. */
    public static function dummyEnabled(): bool
    {
        return app()->environment('local') || (bool) config('midtrans.dummy_enabled');
    }

    /**
     * Mark sesi dummy sebagai lunas + split ledger (net→Soluusi, margin→klinik).
     * Idempotent by session_id; split gagal non-fatal (log error).
     */
    public static function settleDummyPayment(ConsultationSession $session): void
    {
        $session->payment_status = ConsultationSession::PAYMENT_STATUS_VERIFIED;
        $session->status = ConsultationSession::STATUS_SCHEDULED;
        $session->payment = (string) $session->amount;
        $session->payment_at = now();
        $session->save();

        try {
            SplitSettlementService::record($session);
        } catch (\Exception $e) {
            Log::error('Split settlement (dummy) gagal session #' . $session->id . ': ' . $e->getMessage());
        }
    }

    public static function latestSessionForLink(CheckoutLink $link, bool $withRelations = false): ?ConsultationSession
    {
        return ConsultationSession::where('checkout_link_id', $link->id)
            ->when($withRelations, fn ($q) => $q->with(['consultant.user', 'schedule', 'consultantPackage', 'customerRegency']))
            ->latest('id')
            ->first();
    }
}
