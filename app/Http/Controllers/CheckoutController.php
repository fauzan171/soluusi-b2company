<?php

namespace App\Http\Controllers;

use App\Models\ConsultationSession;
use App\Models\Consultant;
use App\Models\ConsultantPackage;
use App\Models\Klinik\CheckoutLink;
use App\Models\Klinik\Klinik;
use App\Models\Klinik\MarkupRule;
use App\Models\Schedule;
use App\Models\Voucher;
use App\Services\Klinik\SplitSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Midtrans\Config;
use Midtrans\Snap;

/**
 * Model C checkout-guest (Build-NEW #9 + #5). Customer TANPA akun: buka
 * white-label link → isi data → bayar langsung (Snap). White-label: zero
 * Soluusi brand di page (§10.8 anti-circumvention).
 */
class CheckoutController extends Controller
{
    public function clinic(Request $request, Klinik $klinik)
    {
        abort_unless($klinik->is_active, 404);

        $consultants = $this->availableConsultantsQuery($klinik, $request)
            ->paginate(9)
            ->withQueryString();

        $selectedConsultant = null;
        $schedules = collect();
        $packages = collect();
        $markups = collect();

        if ($request->filled('consultant_id')) {
            $selectedConsultant = $this->availableConsultantsQuery($klinik, $request)
                ->where('consultants.id', $request->integer('consultant_id'))
                ->first();

            if ($selectedConsultant) {
                $schedules = $this->availableSchedulesQuery($klinik, $selectedConsultant->id)
                    ->orderBy('started_at')
                    ->limit(40)
                    ->get();

                $packages = ConsultantPackage::where('consultant_id', $selectedConsultant->id)
                    ->when($request->filled('mode'), fn ($query) => $query->where('mode', $request->mode))
                    ->orderBy('mode')
                    ->orderBy('duration')
                    ->get();

                $markups = MarkupRule::where('klinik_id', $klinik->id)
                    ->whereIn('consultant_package_id', $packages->pluck('id'))
                    ->get()
                    ->keyBy('consultant_package_id');
            }
        }

        return view('checkout.klinik', [
            'klinik' => $klinik,
            'brand' => $klinik->brand_name,
            'consultants' => $consultants,
            'selectedConsultant' => $selectedConsultant,
            'schedules' => $schedules,
            'packages' => $packages,
            'markups' => $markups,
            'regencies' => \App\Models\Regency::orderBy('name')->get(['id', 'name']),
            'categories' => \App\Models\Category::whereIn('vertical', ['klinik', 'both'])->orderBy('name')->get(),
        ]);
    }

    public function clinicPay(Request $request, Klinik $klinik)
    {
        abort_unless($klinik->is_active, 404);

        $data = $request->validate($this->customerRules() + [
            'consultant_id' => 'required|exists:consultants,id',
            'schedule_id' => 'required|exists:schedules,id',
            'consultant_package_id' => 'required|exists:consultant_packages,id',
        ]);

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
            return back()
                ->with('error', 'Paket dan jadwal harus memakai mode yang sama. Pilih paket yang sesuai dengan jadwal.')
                ->withInput();
        }

        if ($this->slotIsBooked($schedule->id) || $this->slotHasActiveLink($schedule->id)) {
            return back()->with('error', 'Jadwal sudah tidak tersedia. Pilih jadwal lain.')->withInput();
        }

        $net = (int) $package->price;
        $markup = (int) (optional(
            MarkupRule::where('klinik_id', $klinik->id)->where('consultant_package_id', $package->id)->first()
        )->markup_amount ?? 0);
        $final = $net + $markup;

        if (!\App\Services\Klinik\MarkupService::isValid($net, $final)) {
            return back()
                ->with('error', 'Harga paket belum tersedia. Hubungi klinik untuk memperbarui harga paket ini.')
                ->withInput();
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
            'used_at' => now(),
        ]);

        return $this->startPayment($data, $link);
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

        return $this->startPayment($data, $link);
    }

    protected function startPayment(array $data, CheckoutLink $link)
    {
        $package = $link->package;
        $gross = $link->final_price;
        $voucherAmount = 0;
        $voucher = null;

        if (!empty($data['voucher'])) {
            $voucher = Voucher::where('code', $data['voucher'])->first();
            if (!$voucher) {
                return back()->with('error', 'Voucher tidak ditemukan')->withInput();
            }
            [$error, $nominal] = MidtransController::nominalWithVoucher($voucher, $gross);
            if ($error) {
                return back()->with('error', $nominal)->withInput();
            }
            // §5.5: voucher menurunkan Soluusi net (Soluusi absorbs), margin klinik tetap.
            $voucherAmount = min($nominal, $link->net_price);
            $gross -= $voucherAmount;
        }

        // Slot lock re-check (race guard — §17.7).
        $booked = ConsultationSession::where('schedule_id', $link->schedule_id)
            ->blocking()->exists();
        if ($booked) {
            return back()->with('error', 'Jadwal sudah tidak tersedia. Hubungi klinik.');
        }

        // Order number konsisten (§17.1 B2B CHANGE) — idempotent retry.
        $orderNumber = 'KB-' . $link->id . '-' . now()->format('YmdHis');

        if (blank(config('midtrans.server_key'))) {
            if ($this->dummyEnabled()) {
                return $this->startDummyPayment($data, $link, $package, $orderNumber, $gross, $voucher, $voucherAmount);
            }

            return back()->with('error', 'Konfigurasi pembayaran belum aktif. Hubungi admin klinik.')->withInput();
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
            return back()->with('error', 'Gagal membuat transaksi pembayaran: ' . $e->getMessage());
        }

        if (!$this->claimLink($link)) {
            return back()->with('error', 'Link sudah dipakai. Minta link baru ke klinik.');
        }

        $session = $this->createWaitingSession($data, $link, $package, $orderNumber, $gross, $voucher, $voucherAmount, $tokenUrl);

        if ($voucher) {
            $voucher->increment('usage');
        }

        // White-label payment instruction mail (§17.5).
        Mail::to($session->customer_email)->send(new \App\Mail\Klinik\Client\Bayar($session));

        return redirect($tokenUrl);
    }

    protected function startDummyPayment(
        array $data,
        CheckoutLink $link,
        ConsultantPackage $package,
        string $orderNumber,
        int $gross,
        ?Voucher $voucher,
        int $voucherAmount
    ) {
        $dummyUrl = route('checkout.dummyPayment', $link->token);

        if (!$this->claimLink($link)) {
            return back()->with('error', 'Link sudah dipakai. Minta link baru ke klinik.');
        }

        $this->createWaitingSession($data, $link, $package, $orderNumber, $gross, $voucher, $voucherAmount, $dummyUrl, 'DUMMY-' . $link->id);

        if ($voucher) {
            $voucher->increment('usage');
        }

        return redirect($dummyUrl);
    }

    protected function createWaitingSession(
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

    /** White-label finish/status page (guest, token = credential). */
    public function status(string $token)
    {
        $link = CheckoutLink::where('token', $token)
            ->with(['klinik', 'consultant.user', 'consultant.categories', 'schedule', 'package'])
            ->firstOrFail();
        $session = $this->latestSessionForLink($link);

        return view('checkout.status', [
            'link' => $link,
            'session' => $session,
            'brand' => $link->klinik->brand_name,
            'dashboard' => $this->customerDashboardState($link, $session),
        ]);
    }

    public function dummyPayment(string $token)
    {
        abort_unless($this->dummyEnabled(), 404);

        $link = CheckoutLink::where('token', $token)
            ->with(['klinik', 'consultant.user', 'schedule', 'package'])
            ->firstOrFail();
        $session = ConsultationSession::where('checkout_link_id', $link->id)
            ->latest('id')
            ->firstOrFail();

        return view('checkout.dummy-payment', [
            'link' => $link,
            'session' => $session,
            'brand' => $link->klinik->brand_name,
        ]);
    }

    public function dummyPaymentSuccess(string $token)
    {
        abort_unless($this->dummyEnabled(), 404);

        $link = CheckoutLink::where('token', $token)->firstOrFail();
        $session = ConsultationSession::where('checkout_link_id', $link->id)
            ->latest('id')
            ->firstOrFail();

        abort_unless($session->payment_status === ConsultationSession::PAYMENT_STATUS_WAITING, 422, 'Pembayaran dummy sudah diproses.');

        $session->payment_status = ConsultationSession::PAYMENT_STATUS_VERIFIED;
        $session->status = ConsultationSession::STATUS_SCHEDULED;
        $session->payment = (string) $session->amount;
        $session->payment_at = now();
        $session->save();

        // Split ledger (net→Soluusi, margin→klinik). Idempotent by session_id.
        try {
            SplitSettlementService::record($session);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Split settlement (dummy) gagal session #' . $session->id . ': ' . $e->getMessage());
        }

        return redirect()
            ->route('checkout.status', $link->token)
            ->with('success', 'Pembayaran dummy berhasil. Sesi masuk status terjadwal.');
    }

    /** Customer confirm sesi selesai (dual-confirm step 2, Spec §9.1). */
    public function confirmDone(string $token)
    {
        $link = CheckoutLink::where('token', $token)->firstOrFail();
        $session = ConsultationSession::where('checkout_link_id', $link->id)->latest('id')->firstOrFail();

        abort_unless($session->payment_status === 'verified', 422, 'Sesi belum dibayar.');
        abort_if($session->completion_verified_at, 422, 'Sesi sudah terkonfirmasi.');

        \App\Http\Controllers\Klinik\SessionController::customerConfirm($session);

        return back()->with('success', 'Terima kasih! Sesi terkonfirmasi selesai.');
    }

    /** Dummy flow hanya di local, atau flag eksplisit midtrans.dummy_enabled. */
    protected function dummyEnabled(): bool
    {
        return app()->environment('local') || (bool) config('midtrans.dummy_enabled');
    }

    /** Atomic single-use claim: dua request bersamaan hanya satu yang lolos. */
    protected function claimLink(CheckoutLink $link): bool
    {
        return CheckoutLink::whereKey($link->id)->whereNull('used_at')->update(['used_at' => now()]) === 1;
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
        return ConsultationSession::where('checkout_link_id', $link->id)
            ->with(['consultant.user', 'schedule', 'consultantPackage', 'customerRegency'])
            ->latest('id')
            ->first();
    }

    protected function customerDashboardState(CheckoutLink $link, ?ConsultationSession $session): array
    {
        if (!$session) {
            return [
                'title' => 'Belum checkout',
                'subtitle' => $link->isUsable()
                    ? 'Link aktif. Lengkapi data untuk mengunci jadwal konsultasi.'
                    : 'Link belum dipakai dan sudah tidak aktif. Minta link baru ke klinik.',
                'tone' => $link->isUsable() ? 'amber' : 'red',
                'paymentLabel' => 'Belum dibuat',
                'sessionLabel' => 'Belum booking',
                'nextAction' => $link->isUsable() ? 'Isi data dan bayar untuk mengamankan jadwal.' : 'Hubungi klinik untuk link baru.',
                'progress' => 1,
            ];
        }

        if ($session->payment_status === ConsultationSession::PAYMENT_STATUS_WAITING) {
            return [
                'title' => 'Menunggu pembayaran',
                'subtitle' => 'Sesi sudah dibuat sementara. Jadwal terkunci sampai batas waktu pembayaran.',
                'tone' => 'amber',
                'paymentLabel' => 'Menunggu pembayaran',
                'sessionLabel' => 'Belum terjadwal final',
                'nextAction' => 'Lanjutkan pembayaran agar jadwal dikonfirmasi.',
                'progress' => 2,
            ];
        }

        if (in_array($session->payment_status, [ConsultationSession::PAYMENT_STATUS_CANCELED, ConsultationSession::PAYMENT_STATUS_FAILED], true)
            || $session->status === ConsultationSession::STATUS_CANCELED) {
            return [
                'title' => $session->payment_status === ConsultationSession::PAYMENT_STATUS_FAILED ? 'Pembayaran gagal' : 'Sesi dibatalkan',
                'subtitle' => 'Sesi ini tidak aktif untuk konsultasi.',
                'tone' => 'red',
                'paymentLabel' => $session->payment_status === ConsultationSession::PAYMENT_STATUS_FAILED ? 'Gagal' : 'Dibatalkan',
                'sessionLabel' => 'Tidak aktif',
                'nextAction' => 'Hubungi klinik untuk membuat jadwal atau link baru.',
                'progress' => 1,
            ];
        }

        if ($session->completion_verified_at) {
            return [
                'title' => 'Sesi selesai',
                'subtitle' => 'Konsultasi sudah selesai dan terkonfirmasi.',
                'tone' => 'green',
                'paymentLabel' => 'Terverifikasi',
                'sessionLabel' => 'Selesai',
                'nextAction' => 'Tidak ada aksi lanjutan untuk sesi ini.',
                'progress' => 4,
            ];
        }

        if ($session->konsultan_done_at) {
            return [
                'title' => 'Menunggu konfirmasi kamu',
                'subtitle' => 'Konsultan sudah menandai sesi selesai. Konfirmasi jika sesi benar sudah berlangsung.',
                'tone' => 'blue',
                'paymentLabel' => 'Terverifikasi',
                'sessionLabel' => 'Menunggu konfirmasi',
                'nextAction' => 'Konfirmasi sesi selesai setelah konsultasi berlangsung.',
                'progress' => 3,
            ];
        }

        if ($session->payment_status === ConsultationSession::PAYMENT_STATUS_VERIFIED) {
            return [
                'title' => 'Sesi terjadwal',
                'subtitle' => 'Pembayaran sudah diterima. Simpan jadwal konsultasi kamu.',
                'tone' => 'green',
                'paymentLabel' => 'Terverifikasi',
                'sessionLabel' => 'Terjadwal',
                'nextAction' => 'Datang sesuai jadwal. Link meeting akan muncul jika sesi online sudah disiapkan.',
                'progress' => 3,
            ];
        }

        return [
            'title' => 'Status sedang diproses',
            'subtitle' => 'Data sesi sedang diperbarui.',
            'tone' => 'slate',
            'paymentLabel' => ucfirst(str_replace('_', ' ', $session->payment_status)),
            'sessionLabel' => ucfirst(str_replace('_', ' ', $session->status)),
            'nextAction' => 'Muat ulang halaman ini beberapa saat lagi.',
            'progress' => 2,
        ];
    }

    protected function availableConsultantsQuery(Klinik $klinik, Request $request)
    {
        return Consultant::query()
            ->where('status', Consultant::ACCEPT)
            ->where('is_active', true)
            ->with(['user.regency.province', 'categories'])
            ->withCount([
                'schedules' => fn ($query) => $this->availableSchedulesQuery($klinik, null, $query)
                    ->when($request->filled('mode'), fn ($q) => $q->where('mode', $request->mode)),
                'packages',
            ])
            ->whereHas('schedules', fn ($query) => $this->availableSchedulesQuery($klinik, null, $query)
                ->when($request->filled('mode'), fn ($q) => $q->where('mode', $request->mode)))
            ->whereHas('packages', fn ($query) => $query
                ->when($request->filled('mode'), fn ($q) => $q->where('mode', $request->mode)))
            ->when($request->filled('category_id'), fn ($query) =>
                $query->whereHas('categories', fn ($category) => $category->where('categories.id', $request->category_id)))
            ->when($request->filled('q'), fn ($query) =>
                $query->whereHas('user', fn ($user) =>
                    $user->where('firstname', 'like', '%' . $request->q . '%')
                        ->orWhere('lastname', 'like', '%' . $request->q . '%')))
            ->orderByDesc('schedules_count')
            ->orderBy('id');
    }

    protected function availableSchedulesQuery(Klinik $klinik, ?int $consultantId = null, $query = null)
    {
        $query = $query ?: Schedule::query();

        return $query
            ->where('klinik_id', $klinik->id)
            ->when($consultantId, fn ($q) => $q->where('consultant_id', $consultantId))
            ->where('status', Schedule::STATUS_CONFIRMED)
            ->where('started_at', '>', now())
            ->whereDoesntHave('consultationSession', function ($session) {
                $session->blocking();
            })
            ->whereDoesntHave('checkoutLinks', function ($link) {
                $link->whereNull('used_at')->where('expires_at', '>', now());
            });
    }

    protected function slotIsBooked(int $scheduleId): bool
    {
        return ConsultationSession::where('schedule_id', $scheduleId)
            ->blocking()
            ->exists();
    }

    protected function slotHasActiveLink(int $scheduleId): bool
    {
        return CheckoutLink::where('schedule_id', $scheduleId)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->exists();
    }
}
