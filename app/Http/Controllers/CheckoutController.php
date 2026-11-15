<?php

namespace App\Http\Controllers;

use App\Models\ConsultationSession;
use App\Models\Klinik\CheckoutLink;
use App\Models\Klinik\Klinik;
use App\Services\Klinik\CheckoutFlow;
use App\Support\Checkout\CustomerDashboardState;
use App\Support\Checkout\KlinikBrowse;
use Illuminate\Http\Request;

/**
 * Model C checkout-guest (Build-NEW #9 + #5): customer tanpa akun buka
 * white-label link → isi data → bayar via Snap. Zero Soluusi brand (§10.8).
 *
 * T4.2: controller = HTTP concern saja. Business logic → CheckoutFlow;
 * view-state status → CustomerDashboardState; query browse → KlinikBrowse.
 */
class CheckoutController extends Controller
{
    public function clinic(Request $request, Klinik $klinik)
    {
        abort_unless($klinik->is_active, 404);

        return view('checkout.klinik', KlinikBrowse::viewData($klinik, $request));
    }

    public function clinicPay(Request $request, Klinik $klinik)
    {
        abort_unless($klinik->is_active, 404);

        $data = $request->validate($this->customerRules() + [
            'consultant_id' => 'required|exists:consultants,id',
            'schedule_id' => 'required|exists:schedules,id',
            'consultant_package_id' => 'required|exists:consultant_packages,id',
        ]);

        $created = CheckoutFlow::createClinicLink($data, $klinik);
        if (isset($created['error'])) {
            return back()->with('error', $created['error'])->withInput();
        }

        return $this->payResponse(CheckoutFlow::startPayment($data, $created['link']));
    }

    public function show(string $token)
    {
        $link = CheckoutLink::where('token', $token)
            ->with(['klinik', 'consultant.user', 'consultant.categories', 'schedule', 'package'])
            ->firstOrFail();

        if ($this->latestSessionForLink($link)) {
            return redirect()->route('checkout.status', $link->token);
        }

        abort_unless($link->isUsable(), 410, 'Link sudah tidak berlaku. Minta link baru ke klinik.');

        return view('checkout.show', [
            'link' => $link,
            'brand' => $link->klinik->brand_name,
            'regencies' => \App\Models\Regency::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function pay(Request $request, string $token)
    {
        $link = $this->usableLink($token);

        // Trust boundary — input validation wajib (UU PDP: minimize, tapi field
        // ini perlu untuk receipt + gcal + konsultan, Spec §10.2).
        $data = $request->validate($this->customerRules());

        return $this->payResponse(CheckoutFlow::startPayment($data, $link));
    }

    /** Map hasil CheckoutFlow::startPayment() ke HTTP response. */
    protected function payResponse(array $result)
    {
        if (isset($result['error'])) {
            return back()->with('error', $result['error'])->withInput();
        }
        if ($result['no_config'] ?? false) {
            return back()->with('error', 'Konfigurasi pembayaran belum aktif. Hubungi admin klinik.')->withInput();
        }
        if (array_key_exists('reserved', $result) && !$result['reserved']) {
            return back()->with('error', 'Jadwal sudah tidak tersedia atau link sudah dipakai. Hubungi klinik.');
        }

        return redirect($result['redirect']);
    }

    /** White-label finish/status page (guest, token = credential, expiry-bound). */
    public function status(string $token)
    {
        $link = CheckoutLink::where('token', $token)
            ->with(['klinik', 'consultant.user', 'consultant.categories', 'schedule', 'package'])
            ->firstOrFail();

        // Link expired tetap boleh buka status JIKA sudah ada sesi berbayar —
        // sesi bisa berlangsung hari kedua (dual-confirm §9.1), jauh setelah
        // expiry 1 jam. Unpaid + expired → 410 (token bukan bearer permanen).
        $session = $this->latestSessionForLink($link);
        abort_unless($link->expires_at->isFuture() || ($session && $session->payment_status === ConsultationSession::PAYMENT_STATUS_VERIFIED), 410, 'Link sudah kedaluwarsa.');

        return view('checkout.status', [
            'link' => $link,
            'session' => $session,
            'brand' => $link->klinik->brand_name,
            'dashboard' => CustomerDashboardState::for($link, $session),
        ]);
    }

    public function dummyPayment(string $token)
    {
        abort_unless(CheckoutFlow::dummyEnabled(), 404);

        $link = CheckoutLink::where('token', $token)
            ->with(['klinik', 'consultant.user', 'schedule', 'package'])
            ->firstOrFail();
        $session = ConsultationSession::where('checkout_link_id', $link->id)->latest('id')->firstOrFail();

        return view('checkout.dummy-payment', [
            'link' => $link,
            'session' => $session,
            'brand' => $link->klinik->brand_name,
        ]);
    }

    public function dummyPaymentSuccess(string $token)
    {
        abort_unless(CheckoutFlow::dummyEnabled(), 404);

        $link = CheckoutLink::where('token', $token)->firstOrFail();
        $session = ConsultationSession::where('checkout_link_id', $link->id)->latest('id')->firstOrFail();

        abort_unless($session->payment_status === ConsultationSession::PAYMENT_STATUS_WAITING, 422, 'Pembayaran dummy sudah diproses.');

        CheckoutFlow::settleDummyPayment($session);

        return redirect()
            ->route('checkout.status', $link->token)
            ->with('success', 'Pembayaran dummy berhasil. Sesi masuk status terjadwal.');
    }

    /** Customer confirm sesi selesai (dual-confirm step 2, Spec §9.1). */
    public function confirmDone(string $token)
    {
        $link = CheckoutLink::where('token', $token)->firstOrFail();
        $session = ConsultationSession::where('checkout_link_id', $link->id)->latest('id')->firstOrFail();

        // Gate expiry setelah lookup sesi: link expired tetap bisa confirm-done
        // bila sesi sudah berbayar (dual-confirm §9.1 — sesi hari kedua).
        abort_unless(
            $link->expires_at->isFuture() || $session->payment_status === ConsultationSession::PAYMENT_STATUS_VERIFIED,
            410,
            'Link sudah kedaluwarsa.'
        );

        abort_unless($session->payment_status === 'verified', 422, 'Sesi belum dibayar.');
        abort_if($session->completion_verified_at, 422, 'Sesi sudah terkonfirmasi.');

        \App\Http\Controllers\Klinik\SessionController::customerConfirm($session);

        return back()->with('success', 'Terima kasih! Sesi terkonfirmasi selesai.');
    }

    protected function customerRules(): array
    {
        return [
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:30',
            'customer_dob' => 'required|date|before:today',
            'customer_regency_id' => 'required|exists:regencies,id',
            'customer_gender' => 'required|in:male,female',
            'complaint' => 'nullable|string|max:1000',
            'voucher' => 'nullable|string|max:50',
            'consent' => 'accepted',
        ];
    }

    protected function usableLink(string $token): CheckoutLink
    {
        $link = CheckoutLink::where('token', $token)
            ->with(['klinik', 'consultant.user', 'consultant.categories', 'schedule', 'package'])
            ->firstOrFail();

        abort_unless($link->isUsable(), 410, 'Link sudah tidak berlaku. Minta link baru ke klinik.');

        return $link;
    }

    protected function latestSessionForLink(CheckoutLink $link): ?ConsultationSession
    {
        return CheckoutFlow::latestSessionForLink($link, withRelations: true);
    }
}
