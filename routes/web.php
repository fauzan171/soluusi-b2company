<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — B2B Klinik (white-label)
|--------------------------------------------------------------------------
| Customer = GUEST (Model C): hanya checkout link routes, no account.
| Admin klinik / konsultan / associate / super admin: auth + role middleware.
*/

// --- Guest: white-label checkout link (Build-NEW #5/#9) ---
Route::get('/booking/klinik/{klinik:uuid}', [\App\Http\Controllers\CheckoutController::class, 'clinic'])->name('checkout.klinik');
Route::post('/booking/klinik/{klinik:uuid}/pay', [\App\Http\Controllers\CheckoutController::class, 'clinicPay'])->name('checkout.klinik.pay');
Route::get('/checkout/{token}', [\App\Http\Controllers\CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout/{token}/pay', [\App\Http\Controllers\CheckoutController::class, 'pay'])->name('checkout.pay');
Route::get('/checkout/{token}/dummy-payment', [\App\Http\Controllers\CheckoutController::class, 'dummyPayment'])->name('checkout.dummyPayment');
Route::post('/checkout/{token}/dummy-payment/success', [\App\Http\Controllers\CheckoutController::class, 'dummyPaymentSuccess'])->name('checkout.dummyPayment.success');
Route::get('/checkout/{token}/status', [\App\Http\Controllers\CheckoutController::class, 'status'])->name('checkout.status');
Route::post('/checkout/{token}/confirm-done', [\App\Http\Controllers\CheckoutController::class, 'confirmDone'])->name('checkout.confirmDone');

// --- Auth (admin klinik, konsultan, associate, super admin) ---
Route::controller(\App\Http\Controllers\AuthController::class)->group(function () {
    Route::get('/login', 'index')->name('loginIndex');
    // 10/menit per IP: cukup untuk demo login multi-role, tetap menahan brute-force.
    Route::post('/login', 'postLogin')->middleware('throttle:10,1')->name('login');
    Route::get('/auth/{provider}', 'redirectToProvider')->name('login.google');
    Route::get('/auth/{provider}/callback', 'handleProvideCallback');
    Route::post('/logout', 'logout')->name('logout')->middleware('auth');
});

// --- Landing page B2B (public) + lead capture + vertical detail pages ---
Route::get('/', [\App\Http\Controllers\LandingController::class, 'index'])->name('landing');
Route::get('/klinik', fn () => view('verticals.klinik'))->name('landing.klinik');
Route::get('/sekolah', fn () => view('verticals.sekolah'))->name('landing.sekolah');
Route::get('/perusahaan', fn () => view('verticals.perusahaan'))->name('landing.perusahaan');
Route::post('/lead', [\App\Http\Controllers\LandingController::class, 'storeLead'])->middleware('throttle:5,1')->name('landing.lead');

Route::get('/portal', fn () => redirect()->route('loginIndex'));

// --- Registrasi partner (publik, approval oleh ops) ---
Route::get('/daftar-mitra', [\App\Http\Controllers\PartnerRegisterController::class, 'create'])->name('partner.register');
Route::post('/daftar-mitra', [\App\Http\Controllers\PartnerRegisterController::class, 'store'])->middleware('throttle:5,1')->name('partner.register.store');

// --- Klinik portal (Partner Admin/Staff) — Build-NEW #5/#6 ---
Route::prefix('klinik')->as('klinik.')->middleware(['auth', 'role:klinik_admin,admin'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Klinik\DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('klinik.active')->group(function () {
        Route::get('/consultants', [\App\Http\Controllers\Klinik\ConsultantController::class, 'index'])->name('consultants.index');
        Route::get('/consultants/{consultant}', [\App\Http\Controllers\Klinik\ConsultantController::class, 'show'])->name('consultants.show');
        Route::get('/schedules', [\App\Http\Controllers\Klinik\ScheduleController::class, 'index'])->name('schedules.index');
        Route::get('/schedules/create', [\App\Http\Controllers\Klinik\ScheduleController::class, 'create'])->name('schedules.create');
        Route::post('/schedules', [\App\Http\Controllers\Klinik\ScheduleController::class, 'store'])->name('schedules.store');
        Route::get('/links', [\App\Http\Controllers\Klinik\CheckoutLinkController::class, 'index'])->name('links.index');
        Route::get('/links/create', [\App\Http\Controllers\Klinik\CheckoutLinkController::class, 'create'])->name('links.create');
        Route::post('/links', [\App\Http\Controllers\Klinik\CheckoutLinkController::class, 'store'])->name('links.store');
    });
});

// --- Self-register karyawan & siswa (roster harus sudah ada; akun menautkan login ke roster) ---
Route::get('/daftar/{type}', [\App\Http\Controllers\SelfRegisterController::class, 'form'])->middleware(['signed', 'throttle:10,1'])->name('selfregister.form');
Route::post('/daftar/{type}', [\App\Http\Controllers\SelfRegisterController::class, 'store'])->middleware(['signed', 'throttle:10,1'])->name('selfregister.store');

// Status pengembangan — untuk presentasi. Hapus saat tidak dipakai.
Route::get('/progress', [\App\Http\Controllers\ProgressController::class, 'index'])->name('progress');

// --- Sekolah portal (BK coordinator + siswa) ---
Route::prefix('sekolah-portal')->as('sekolah.')->middleware(['auth'])->group(function () {
    Route::middleware('role:bk,admin')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Sekolah\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/export', [\App\Http\Controllers\Sekolah\DashboardController::class, 'export'])->name('dashboard.export');
        Route::get('/dashboard/ai-insight', [\App\Http\Controllers\Sekolah\DashboardController::class, 'aiInsight'])->name('dashboard.ai-insight');
        Route::post('/dashboard/ai-insight', [\App\Http\Controllers\Sekolah\DashboardController::class, 'aiInsight']);
        Route::get('/students', [\App\Http\Controllers\Sekolah\RosterController::class, 'index'])->name('students');
        Route::post('/students', [\App\Http\Controllers\Sekolah\RosterController::class, 'store'])->name('students.store');
        Route::post('/students/refer', [\App\Http\Controllers\Sekolah\RosterController::class, 'refer'])->name('students.refer');
        Route::post('/students/{student}/consent', [\App\Http\Controllers\Sekolah\RosterController::class, 'consent'])->name('students.consent');
        Route::post('/students/invite', [\App\Http\Controllers\Sekolah\RosterController::class, 'invite'])->name('students.invite');
    });
    Route::middleware('role:student,admin')->group(function () {
        Route::get('/siswa', [\App\Http\Controllers\Sekolah\StudentController::class, 'index'])->name('student');
        Route::get('/siswa/assessment', [\App\Http\Controllers\Sekolah\StudentController::class, 'assessmentForm'])->name('assessment.form');
        Route::post('/siswa/assessment', [\App\Http\Controllers\Sekolah\StudentController::class, 'assessmentStore'])->name('assessment.store');
        Route::get('/siswa/booking', [\App\Http\Controllers\Sekolah\StudentController::class, 'bookingForm'])->name('booking');
        Route::post('/siswa/booking', [\App\Http\Controllers\Sekolah\StudentController::class, 'bookingStore'])->name('booking.store');
        Route::post('/siswa/sessions/{session}/cancel', [\App\Http\Controllers\Sekolah\StudentController::class, 'bookingCancel'])->name('booking.cancel');
        Route::post('/siswa/sessions/{session}/reschedule', [\App\Http\Controllers\Sekolah\StudentController::class, 'bookingReschedule'])->name('booking.reschedule');
    });
});

// --- Perusahaan portal (CAP — HR admin + karyawan) ---
Route::prefix('perusahaan')->as('perusahaan.')->middleware(['auth'])->group(function () {
    Route::middleware('role:hr_admin,admin')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Perusahaan\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/export', [\App\Http\Controllers\Perusahaan\DashboardController::class, 'export'])->name('dashboard.export');
        Route::get('/dashboard/ai-insight', [\App\Http\Controllers\Perusahaan\DashboardController::class, 'aiInsight'])->name('dashboard.ai-insight');
        Route::post('/dashboard/ai-insight', [\App\Http\Controllers\Perusahaan\DashboardController::class, 'aiInsight']);
        Route::get('/report', [\App\Http\Controllers\Perusahaan\ReportController::class, 'index'])->name('report');
        Route::get('/employees', [\App\Http\Controllers\Perusahaan\RosterController::class, 'index'])->name('employees');
        Route::post('/employees', [\App\Http\Controllers\Perusahaan\RosterController::class, 'store'])->name('employees.store');
        Route::post('/employees/{employee}/consent', [\App\Http\Controllers\Perusahaan\RosterController::class, 'consent'])->name('employees.consent');
        Route::post('/employees/invite', [\App\Http\Controllers\Perusahaan\RosterController::class, 'invite'])->name('employees.invite');
    });
    Route::middleware('role:employee,admin')->group(function () {
        Route::get('/employee', [\App\Http\Controllers\Perusahaan\EmployeeController::class, 'index'])->name('employee');
        Route::get('/employee/assessment', [\App\Http\Controllers\Perusahaan\EmployeeController::class, 'assessmentForm'])->name('assessment.form');
        Route::post('/employee/assessment', [\App\Http\Controllers\Perusahaan\EmployeeController::class, 'assessmentStore'])->name('assessment.store');
        Route::get('/employee/booking', [\App\Http\Controllers\Perusahaan\EmployeeController::class, 'bookingForm'])->name('booking');
        Route::post('/employee/booking', [\App\Http\Controllers\Perusahaan\EmployeeController::class, 'bookingStore'])->name('booking.store');
        Route::post('/employee/sessions/{session}/cancel', [\App\Http\Controllers\Perusahaan\EmployeeController::class, 'bookingCancel'])->name('booking.cancel');
        Route::post('/employee/sessions/{session}/reschedule', [\App\Http\Controllers\Perusahaan\EmployeeController::class, 'bookingReschedule'])->name('booking.reschedule');
    });
});

// --- Konsultan (session + dual-confirm) — Build-NEW #4/#11 ---
Route::prefix('consultant')->as('consultant.')->middleware(['auth', 'role:consultant'])->group(function () {
    Route::get('/sessions', [\App\Http\Controllers\Klinik\SessionController::class, 'index'])->name('sessions');
    Route::post('/sessions/{session}/check-in', [\App\Http\Controllers\Klinik\SessionController::class, 'checkIn'])->name('sessions.checkIn');
    Route::post('/sessions/{session}/mark-done', [\App\Http\Controllers\Klinik\SessionController::class, 'markDone'])->name('sessions.markDone');
});

// --- Associate (HAP) dashboard — see own clinics + commission tracking ---
Route::prefix('associate')->as('associate.')->middleware(['auth', 'role:associate'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Associate\DashboardController::class, 'index'])->name('dashboard');
    Route::post('/pervormas/{pervorma}/request-payout', [\App\Http\Controllers\Associate\DashboardController::class, 'requestPayout'])->name('pervormas.request-payout');
});

// --- Super admin (ops review + approval mitra) ---
Route::prefix('admin')->as('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/referral-overview', [\App\Http\Controllers\AdminDashboardController::class, 'referralOverview'])->name('referral-overview');
    Route::get('/consultant-per-klinik', [\App\Http\Controllers\AdminDashboardController::class, 'consultantPerKlinik'])->name('consultant-per-klinik');
    Route::get('/pervorma-sessions', [\App\Http\Controllers\AdminDashboardController::class, 'pervormaSessions'])->name('pervorma-sessions');

    Route::post('/pervormas/{pervorma}/pay', function (\App\Models\Klinik\HapPervorma $pervorma) {
        abort_if($pervorma->status === 'paid', 422, 'Pervorma sudah dibayar.');
        $pervorma->status = 'paid';
        $pervorma->paid_at = now();
        $pervorma->save();

        return back()->with('success', "Pervorma Stage {$pervorma->stage} ditandai sudah dibayar.");
    })->name('pervormas.pay');

    // Approval mitra baru (dari registrasi /daftar-mitra)
    Route::post('/kliniks/{klinik}/approve', function (\App\Models\Klinik\Klinik $klinik) {
        $klinik->is_active = true;
        $klinik->approved_at = now();
        $klinik->save();
        return back()->with('success', "Mitra {$klinik->name} di-approve. Dashboard mitra sekarang aktif.");
    })->name('kliniks.approve');

    // Paket klinik B2B — pembuatan & akses hanya super admin (harga sesuai budget mitra).
    Route::get('/kliniks/{klinik}/packages', [\App\Http\Controllers\Klinik\KlinikPackageController::class, 'index'])->name('kliniks.packages.index');
    Route::post('/kliniks/{klinik}/packages', [\App\Http\Controllers\Klinik\KlinikPackageController::class, 'store'])->name('kliniks.packages.store');
    Route::put('/packages/{package}', [\App\Http\Controllers\Klinik\KlinikPackageController::class, 'update'])->name('kliniks.packages.update');
    Route::delete('/packages/{package}', [\App\Http\Controllers\Klinik\KlinikPackageController::class, 'destroy'])->name('kliniks.packages.destroy');

    Route::get('/kliniks/{klinik}/markups', [\App\Http\Controllers\Klinik\MarkupRuleController::class, 'index'])->name('kliniks.markups.index');
    Route::post('/kliniks/{klinik}/markups', [\App\Http\Controllers\Klinik\MarkupRuleController::class, 'store'])->name('kliniks.markups.store');

    // Aktivasi program perusahaan (CAP)
    Route::post('/perusahaans/{perusahaan}/activate', function (\App\Models\Perusahaan\Perusahaan $perusahaan) {
        $perusahaan->is_active = true;
        $perusahaan->save();
        return back()->with('success', "Program {$perusahaan->brand} diaktifkan. Dashboard HR sekarang aktif.");
    })->name('perusahaans.activate');

    // Aktivasi program sekolah
    Route::post('/sekolahs/{sekolah}/activate', function (\App\Models\Sekolah\Sekolah $sekolah) {
        $sekolah->is_active = true;
        $sekolah->save();
        return back()->with('success', "Program {$sekolah->brand} diaktifkan. Dashboard BK sekarang aktif.");
    })->name('sekolahs.activate');

    // Settle margin klinik (finance: margin sudah ditransfer ke klinik)
    Route::post('/splits/{split}/settle', function (\App\Models\Klinik\SplitSettlement $split) {
        abort_if($split->status === 'settled', 422, 'Sudah settled.');
        $split->status = 'settled';
        $split->settled_at = now();
        $split->save();
        return back()->with('success', "Margin Rp" . number_format($split->klinik_margin, 0, ',', '.') . " untuk {$split->klinik->brand_name} ditandai settled.");
    })->name('splits.settle');

    // Ops manual verify (dual-confirm fallback, Spec §9.1 step 4)
    Route::post('/sessions/{session}/verify', function (\App\Models\ConsultationSession $session) {
        abort_if($session->completion_verified_at, 422, 'Sudah terverifikasi.');
        $session->customer_confirmed_at = $session->customer_confirmed_at ?? now();
        $session->needs_ops_review = false;
        $session->save();
        \App\Http\Controllers\Klinik\SessionController::customerConfirm($session);
        return back()->with('success', 'Sesi diverifikasi manual oleh ops.');
    })->name('sessions.verify');
});
