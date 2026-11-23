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
Route::get('/checkout/{token}/dummy-payment', [\App\Http\Controllers\CheckoutController::class, 'dummyPayment'])->middleware('notprod')->name('checkout.dummyPayment');
Route::post('/checkout/{token}/dummy-payment/success', [\App\Http\Controllers\CheckoutController::class, 'dummyPaymentSuccess'])->middleware('notprod')->name('checkout.dummyPayment.success');
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

// --- Password reset (T5.5, broker bawaan Laravel) ---
Route::controller(\App\Http\Controllers\Auth\PasswordResetController::class)->group(function () {
    Route::get('/forgot-password', 'requestForm')->name('password.request');
    Route::post('/forgot-password', 'sendResetLink')->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', 'resetForm')->name('password.reset');
    Route::post('/reset-password', 'update')->middleware('throttle:5,1')->name('password.update');
});

// --- Ganti password wajib saat login pertama (ONB.3) ---
Route::controller(\App\Http\Controllers\Auth\ChangePasswordController::class)
    ->middleware('auth')->group(function () {
        Route::get('/ubah-password', 'show')->name('password.change');
        Route::post('/ubah-password', 'store')->middleware('throttle:6,1')->name('password.change.store');
    });

// --- Onboarding login pertama: konfirmasi profil (ONB.4) ---
Route::controller(\App\Http\Controllers\Onboarding\ProfileController::class)
    // Urutan gerbang sama dengan portal: ganti password dulu, baru konfirmasi
    // profil. Guard role membuat endpoint tidak bisa dijangkau role lain.
    ->middleware(['auth', 'force.password', 'role:employee,student,sekolah_staff,admin'])->group(function () {
    Route::get('/onboarding/perusahaan/profil', 'show')->defaults('vertical', 'perusahaan')->name('perusahaan.profile');
    Route::post('/onboarding/perusahaan/profil', 'store')->defaults('vertical', 'perusahaan')->name('perusahaan.profile.store');
    Route::get('/onboarding/sekolah/profil', 'show')->defaults('vertical', 'sekolah')->name('sekolah.profile');
    Route::post('/onboarding/sekolah/profil', 'store')->defaults('vertical', 'sekolah')->name('sekolah.profile.store');
});

// --- Landing page B2B (public) + vertical detail pages ---
Route::get('/', [\App\Http\Controllers\LandingController::class, 'index'])->name('landing');
Route::get('/klinik', fn () => view('verticals.klinik'))->name('landing.klinik');
Route::get('/sekolah', fn () => view('verticals.sekolah'))->name('landing.sekolah');
Route::get('/perusahaan', fn () => view('verticals.perusahaan'))->name('landing.perusahaan');

Route::get('/portal', fn () => redirect()->route('loginIndex'));

// --- Registrasi partner (publik, approval oleh ops) ---
Route::get('/daftar-mitra', [\App\Http\Controllers\PartnerRegisterController::class, 'create'])->name('partner.register');
Route::post('/daftar-mitra', [\App\Http\Controllers\PartnerRegisterController::class, 'store'])->middleware('throttle:5,1')->name('partner.register.store');

// --- Klinik portal (Partner Admin/Staff) — Build-NEW #5/#6 ---
Route::prefix('klinik')->as('klinik.')->middleware(['auth', 'role:klinik_admin,admin', 'force.password'])->group(function () {
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
Route::get('/progress', [\App\Http\Controllers\ProgressController::class, 'index'])->middleware(['auth', 'role:admin'])->name('progress');

// --- Sekolah portal (BK coordinator + siswa) ---
Route::prefix('sekolah-portal')->as('sekolah.')->middleware(['auth', 'force.password', 'profile.confirmed'])->group(function () {
    Route::middleware('role:bk,admin')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Sekolah\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/export', [\App\Http\Controllers\Sekolah\DashboardController::class, 'export'])->name('dashboard.export');
        Route::get('/dashboard/ai-insight', [\App\Http\Controllers\Sekolah\DashboardController::class, 'aiInsight'])->name('dashboard.ai-insight');
        Route::post('/dashboard/ai-insight', [\App\Http\Controllers\Sekolah\DashboardController::class, 'aiInsight']);
        Route::get('/report', [\App\Http\Controllers\Sekolah\ReportController::class, 'index'])->name('report');
        Route::get('/students', [\App\Http\Controllers\Sekolah\RosterController::class, 'index'])->name('students');
        Route::post('/students', [\App\Http\Controllers\Sekolah\RosterController::class, 'store'])->name('students.store');
        Route::post('/students/refer', [\App\Http\Controllers\Sekolah\RosterController::class, 'refer'])->name('students.refer');
        Route::post('/students/{student}/consent', [\App\Http\Controllers\Sekolah\RosterController::class, 'consent'])->name('students.consent');
        Route::post('/students/invite', [\App\Http\Controllers\Sekolah\RosterController::class, 'invite'])->name('students.invite');
        // ONB.6: import CSV massal siswa + staf (kolom `tipe` opsional).
        Route::get('/students/import', [\App\Http\Controllers\Sekolah\RosterImportController::class, 'index'])->name('students.import');
        Route::post('/students/import', [\App\Http\Controllers\Sekolah\RosterImportController::class, 'preview'])->middleware('throttle:10,1')->name('students.import.preview');
        Route::post('/students/import/commit', [\App\Http\Controllers\Sekolah\RosterImportController::class, 'commit'])->middleware('throttle:10,1')->name('students.import.commit');
        // ONB.5: BK menyalakan/mematikan konsultan yang sudah di-assign Soluusi.
        Route::get('/konsultan', [\App\Http\Controllers\Sekolah\ConsultantPoolController::class, 'index'])->name('consultants.pool');
        Route::post('/konsultan/toggle', [\App\Http\Controllers\Sekolah\ConsultantPoolController::class, 'toggle'])->middleware('throttle:20,1')->name('consultants.pool.toggle');
        Route::get('/review', [\App\Http\Controllers\Sekolah\BkReviewController::class, 'index'])->name('review');
        Route::post('/review/{session}/approve', [\App\Http\Controllers\Sekolah\BkReviewController::class, 'approve'])->middleware('throttle:10,1')->name('review.approve');
        Route::post('/review/{session}/reject', [\App\Http\Controllers\Sekolah\BkReviewController::class, 'reject'])->middleware('throttle:10,1')->name('review.reject');
    });
    Route::middleware('role:sekolah_staff,admin')->group(function () {
        Route::get('/staff', [\App\Http\Controllers\Sekolah\StaffController::class, 'index'])->name('staff');
        Route::get('/staff/assessment', [\App\Http\Controllers\Sekolah\StaffController::class, 'assessmentForm'])->name('staff.assessment.form');
        Route::post('/staff/assessment', [\App\Http\Controllers\Sekolah\StaffController::class, 'assessmentStore'])->name('staff.assessment.store');
        Route::get('/staff/booking', [\App\Http\Controllers\Sekolah\StaffController::class, 'bookingForm'])->name('staff.booking');
        Route::post('/staff/booking', [\App\Http\Controllers\Sekolah\StaffController::class, 'bookingStore'])->middleware('throttle:10,1')->name('staff.booking.store');
        Route::post('/staff/request', [\App\Http\Controllers\Sekolah\StaffController::class, 'requestConsultation'])->middleware('throttle:10,1')->name('staff.request');
        Route::post('/staff/request/{session}/cancel', [\App\Http\Controllers\Sekolah\StaffController::class, 'cancelPendingRequest'])->middleware('throttle:10,1')->name('staff.request.cancel');
        Route::post('/staff/sessions/{session}/cancel', [\App\Http\Controllers\Sekolah\StaffController::class, 'bookingCancel'])->middleware('throttle:10,1')->name('staff.booking.cancel');
        Route::post('/staff/sessions/{session}/reschedule', [\App\Http\Controllers\Sekolah\StaffController::class, 'bookingReschedule'])->middleware('throttle:10,1')->name('staff.booking.reschedule');
        Route::get('/staff/sessions/{session}/chat', [\App\Http\Controllers\Sekolah\ChatController::class, 'student'])->defaults('prefix', 'sekolah.staff')->name('staff.chat');
        Route::post('/staff/sessions/{session}/chat', [\App\Http\Controllers\Sekolah\ChatController::class, 'studentStore'])->defaults('prefix', 'sekolah.staff')->middleware('throttle:60,1')->name('staff.chat.store');
        Route::post('/staff/sessions/{session}/end', [\App\Http\Controllers\Sekolah\ChatController::class, 'studentEnd'])->defaults('prefix', 'sekolah.staff')->name('staff.chat.end');
        Route::post('/staff/sessions/{session}/rate', [\App\Http\Controllers\Sekolah\ChatController::class, 'studentRate'])->defaults('prefix', 'sekolah.staff')->name('staff.chat.rate');
    });
    Route::middleware('role:student,admin')->group(function () {
        Route::get('/siswa', [\App\Http\Controllers\Sekolah\StudentController::class, 'index'])->name('student');
        Route::get('/siswa/assessment', [\App\Http\Controllers\Sekolah\StudentController::class, 'assessmentForm'])->name('assessment.form');
        Route::post('/siswa/assessment', [\App\Http\Controllers\Sekolah\StudentController::class, 'assessmentStore'])->name('assessment.store');
        Route::get('/siswa/booking', [\App\Http\Controllers\Sekolah\StudentController::class, 'bookingForm'])->name('booking');
        Route::post('/siswa/booking', [\App\Http\Controllers\Sekolah\StudentController::class, 'bookingStore'])->middleware('throttle:10,1')->name('booking.store');
        Route::post('/siswa/request', [\App\Http\Controllers\Sekolah\StudentController::class, 'requestConsultation'])->middleware('throttle:10,1')->name('siswa.request');
        Route::post('/siswa/request/{session}/cancel', [\App\Http\Controllers\Sekolah\StudentController::class, 'cancelPendingRequest'])->middleware('throttle:10,1')->name('siswa.request.cancel');
        Route::post('/siswa/sessions/{session}/cancel', [\App\Http\Controllers\Sekolah\StudentController::class, 'bookingCancel'])->middleware('throttle:10,1')->name('booking.cancel');
        Route::post('/siswa/sessions/{session}/reschedule', [\App\Http\Controllers\Sekolah\StudentController::class, 'bookingReschedule'])->middleware('throttle:10,1')->name('booking.reschedule');
        Route::get('/siswa/sessions/{session}/chat', [\App\Http\Controllers\Sekolah\ChatController::class, 'student'])->name('chat');
        Route::post('/siswa/sessions/{session}/chat', [\App\Http\Controllers\Sekolah\ChatController::class, 'studentStore'])->middleware('throttle:60,1')->name('chat.store');
        Route::post('/siswa/sessions/{session}/end', [\App\Http\Controllers\Sekolah\ChatController::class, 'studentEnd'])->name('chat.end');
        Route::post('/siswa/sessions/{session}/rate', [\App\Http\Controllers\Sekolah\ChatController::class, 'studentRate'])->name('chat.rate');
    });
});

// --- Perusahaan portal (CAP — HR admin + karyawan) ---
Route::prefix('perusahaan')->as('perusahaan.')->middleware(['auth', 'force.password', 'profile.confirmed'])->group(function () {
    Route::middleware('role:hr_admin,admin')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Perusahaan\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/export', [\App\Http\Controllers\Perusahaan\DashboardController::class, 'export'])->name('dashboard.export');
        Route::get('/dashboard/ai-insight', [\App\Http\Controllers\Perusahaan\DashboardController::class, 'aiInsight'])->name('dashboard.ai-insight');
        Route::post('/dashboard/ai-insight', [\App\Http\Controllers\Perusahaan\DashboardController::class, 'aiInsight']);
        Route::get('/report', [\App\Http\Controllers\Perusahaan\ReportController::class, 'index'])->name('report');
        Route::get('/report/assessment', [\App\Http\Controllers\Perusahaan\ReportController::class, 'assessment'])->name('report.assessment');
        Route::get('/employees', [\App\Http\Controllers\Perusahaan\RosterController::class, 'index'])->name('employees');
        Route::post('/employees', [\App\Http\Controllers\Perusahaan\RosterController::class, 'store'])->name('employees.store');
        Route::post('/employees/{employee}/consent', [\App\Http\Controllers\Perusahaan\RosterController::class, 'consent'])->name('employees.consent');
        Route::post('/employees/invite', [\App\Http\Controllers\Perusahaan\RosterController::class, 'invite'])->name('employees.invite');
        // ONB.2: import CSV massal (tanpa email).
        Route::get('/employees/import', [\App\Http\Controllers\Perusahaan\RosterImportController::class, 'index'])->name('employees.import');
        Route::post('/employees/import', [\App\Http\Controllers\Perusahaan\RosterImportController::class, 'preview'])->middleware('throttle:10,1')->name('employees.import.preview');
        Route::post('/employees/import/commit', [\App\Http\Controllers\Perusahaan\RosterImportController::class, 'commit'])->middleware('throttle:10,1')->name('employees.import.commit');
        Route::get('/review', [\App\Http\Controllers\Perusahaan\HrReviewController::class, 'index'])->name('review');
        Route::post('/review/{session}/approve', [\App\Http\Controllers\Perusahaan\HrReviewController::class, 'approve'])->middleware('throttle:10,1')->name('review.approve');
        Route::post('/review/{session}/reject', [\App\Http\Controllers\Perusahaan\HrReviewController::class, 'reject'])->middleware('throttle:10,1')->name('review.reject');
        // ONB.5: HR menyalakan/mematikan konsultan yang sudah di-assign Soluusi.
        Route::get('/konsultan', [\App\Http\Controllers\Perusahaan\ConsultantPoolController::class, 'index'])->name('consultants.pool');
        Route::post('/konsultan/toggle', [\App\Http\Controllers\Perusahaan\ConsultantPoolController::class, 'toggle'])->middleware('throttle:20,1')->name('consultants.pool.toggle');
        // Katalog + detail + request konsultan untuk HR.
        Route::get('/konsultan/katalog', [\App\Http\Controllers\Perusahaan\ConsultantCatalogController::class, 'index'])->name('consultants.catalog');
        Route::get('/konsultan/katalog/{consultant}', [\App\Http\Controllers\Perusahaan\ConsultantCatalogController::class, 'show'])->name('consultants.detail');
        Route::post('/konsultan/katalog/{consultant}/request', [\App\Http\Controllers\Perusahaan\ConsultantCatalogController::class, 'requestStore'])->middleware('throttle:10,1')->name('consultants.request');
        // Kesejahteraan agregat — halaman chart lengkap dari card dashboard.
        Route::get('/kesejahteraan', [\App\Http\Controllers\Perusahaan\WellbeingController::class, 'index'])->name('wellbeing');
        // W2: refund kuota (HR/superadmin, reason wajib, audit_logs entry)
        Route::post('/consultation-quota/{ledger}/refund', [\App\Http\Controllers\Perusahaan\QuotaAdminController::class, 'refund'])->name('quota.refund');

        // W5: Room 1-on-1 (M5) — CRUD terbatas HR admin, tenant-scoped via PerusahaanContext.
        Route::get('/rooms', [\App\Http\Controllers\Perusahaan\RoomController::class, 'index'])->name('rooms.index');
        Route::post('/rooms', [\App\Http\Controllers\Perusahaan\RoomController::class, 'store'])->name('rooms.store');
        Route::post('/rooms/{room}/toggle', [\App\Http\Controllers\Perusahaan\RoomController::class, 'toggle'])->name('rooms.toggle');
        // W5 (K10): HR override link meeting per sesi.
        Route::post('/sessions/{session}/meeting-link', [\App\Http\Controllers\ChatController::class, 'meetingLink'])->name('sessions.meeting-link');
    });
    Route::middleware('role:employee,admin')->group(function () {
        Route::get('/employee', [\App\Http\Controllers\Perusahaan\EmployeeController::class, 'index'])->name('employee');
        Route::get('/employee/assessment', [\App\Http\Controllers\Perusahaan\EmployeeController::class, 'assessmentForm'])->name('assessment.form');
        Route::post('/employee/assessment', [\App\Http\Controllers\Perusahaan\EmployeeController::class, 'assessmentStore'])->name('assessment.store');
        Route::get('/employee/assessments', [\App\Http\Controllers\Perusahaan\AssessmentController::class, 'index'])->name('assessment.instruments');
        Route::get('/employee/assessments/{code}', [\App\Http\Controllers\Perusahaan\AssessmentController::class, 'form'])->name('assessment.instrument.form');
        Route::post('/employee/assessments/{code}', [\App\Http\Controllers\Perusahaan\AssessmentController::class, 'store'])->name('assessment.instrument.store');
        Route::get('/employee/assessments/result/{assessment}', [\App\Http\Controllers\Perusahaan\AssessmentController::class, 'result'])->name('assessment.result');
        Route::get('/employee/consultants', [\App\Http\Controllers\Perusahaan\EmployeeController::class, 'consultants'])->name('consultants');
    Route::get('/employee/consultants/{consultant}', [\App\Http\Controllers\Perusahaan\EmployeeController::class, 'consultantShow'])->name('consultants.show');
        Route::post('/employee/consultants/{consultant}/start-chat', [\App\Http\Controllers\Perusahaan\EmployeeController::class, 'startChat'])->name('consultants.start-chat');
        Route::get('/employee/booking', [\App\Http\Controllers\Perusahaan\EmployeeController::class, 'bookingForm'])->name('booking');
        Route::post('/employee/booking', [\App\Http\Controllers\Perusahaan\EmployeeController::class, 'bookingStore'])->middleware('throttle:10,1')->name('booking.store');
        Route::post('/employee/request-consultation', [\App\Http\Controllers\Perusahaan\EmployeeController::class, 'requestConsultation'])->middleware('throttle:10,1')->name('request-consultation');
        Route::post('/employee/request/{session}/cancel', [\App\Http\Controllers\Perusahaan\EmployeeController::class, 'cancelPendingRequest'])->middleware('throttle:10,1')->name('request.cancel');
        Route::post('/employee/slot-proposals/{proposal}/confirm', [\App\Http\Controllers\Perusahaan\EmployeeController::class, 'confirmSlot'])->middleware('throttle:10,1')->name('slots.confirm');
        Route::post('/employee/sessions/{session}/cancel', [\App\Http\Controllers\Perusahaan\EmployeeController::class, 'bookingCancel'])->middleware('throttle:10,1')->name('booking.cancel');
        Route::post('/employee/sessions/{session}/reschedule', [\App\Http\Controllers\Perusahaan\EmployeeController::class, 'bookingReschedule'])->middleware('throttle:10,1')->name('booking.reschedule');
        Route::get('/employee/sessions/{session}/chat', [\App\Http\Controllers\ChatController::class, 'employee'])->name('chat');
        Route::post('/employee/sessions/{session}/chat', [\App\Http\Controllers\ChatController::class, 'employeeStore'])->middleware('throttle:60,1')->name('chat.store');
        Route::post('/employee/sessions/{session}/end', [\App\Http\Controllers\ChatController::class, 'employeeEnd'])->name('chat.end');
        Route::post('/employee/sessions/{session}/rate', [\App\Http\Controllers\ChatController::class, 'employeeRate'])->name('chat.rate');
        // W7 (K14): ulasan sesi (rating 1-5 + komentar, 1x, post-done, konsultan hanya lihat agregat).
        Route::post('/employee/sessions/{session}/review', [\App\Http\Controllers\Perusahaan\ConsultationRecordController::class, 'storeReview'])->name('reviews.store');
    });
});

// --- Konsultan (session + dual-confirm) — Build-NEW #4/#11 ---
// W3/recon-R2: role admin ditambahkan — konsultan portal satu-satunya yang
// superadmin tidak bisa masuk; superadmin butuh preview kalender konsultan.
Route::prefix('consultant')->as('consultant.')->middleware(['auth', 'role:consultant,admin', 'force.password'])->group(function () {
    Route::get('/sessions', [\App\Http\Controllers\Klinik\SessionController::class, 'index'])->name('sessions');
    Route::get('/calendar', [\App\Http\Controllers\ConsultantCalendarController::class, 'index'])->name('calendar');
    Route::post('/sessions/{session}/check-in', [\App\Http\Controllers\Klinik\SessionController::class, 'checkIn'])->name('sessions.checkIn');
    Route::post('/sessions/{session}/mark-done', [\App\Http\Controllers\Klinik\SessionController::class, 'markDone'])->name('sessions.markDone');
    Route::post('/sessions/{session}/schedule', [\App\Http\Controllers\Klinik\SessionController::class, 'schedulePerusahaan'])->name('sessions.schedule');
    Route::get('/sessions/{session}/propose-slots', [\App\Http\Controllers\Consultant\SlotProposalController::class, 'create'])->name('sessions.propose-slots.form');
    Route::post('/sessions/{session}/propose-slots', [\App\Http\Controllers\Consultant\SlotProposalController::class, 'store'])->middleware('throttle:10,1')->name('sessions.propose-slots');
    // W5 (K10): konsultan set link meeting per sesi (gmeet/zoom, https).
    Route::post('/sessions/{session}/meeting-link', [\App\Http\Controllers\ChatController::class, 'meetingLink'])->name('sessions.meeting-link');
    Route::get('/sessions/{session}/chat', [\App\Http\Controllers\ChatController::class, 'consultant'])->name('chat');
    Route::post('/sessions/{session}/chat', [\App\Http\Controllers\ChatController::class, 'consultantStore'])->middleware('throttle:60,1')->name('chat.store');
    Route::post('/sessions/{session}/start', [\App\Http\Controllers\ChatController::class, 'consultantStart'])->name('chat.start');
    Route::post('/sessions/{session}/end', [\App\Http\Controllers\ChatController::class, 'consultantEnd'])->name('chat.end');
    // Sesi sekolah (SAP) — room + start/end terpisah dari CAP (model binding beda).
    Route::get('/sekolah-sessions/{session}/chat', [\App\Http\Controllers\Sekolah\ChatController::class, 'consultant'])->name('sekolah.chat');
    Route::post('/sekolah-sessions/{session}/chat', [\App\Http\Controllers\Sekolah\ChatController::class, 'consultantStore'])->middleware('throttle:60,1')->name('sekolah.chat.store');
    Route::post('/sekolah-sessions/{session}/start', [\App\Http\Controllers\Sekolah\ChatController::class, 'consultantStart'])->name('sekolah.chat.start');
    Route::post('/sekolah-sessions/{session}/end', [\App\Http\Controllers\Sekolah\ChatController::class, 'consultantEnd'])->name('sekolah.chat.end');
    Route::post('/sekolah-sessions/{session}/schedule', [\App\Http\Controllers\Klinik\SessionController::class, 'scheduleSekolah'])->middleware('throttle:10,1')->name('sekolah.schedule');
    // W7 (K14): catatan klinis (encrypted + audit) & hasil sesi — konsultan pemilik sesi saja.
    Route::post('/sessions/{session}/note', [\App\Http\Controllers\Perusahaan\ConsultationRecordController::class, 'storeNote'])->name('notes.store');
    Route::post('/sessions/{session}/result', [\App\Http\Controllers\Perusahaan\ConsultationRecordController::class, 'storeResult'])->name('results.store');
});

// --- Associate (HAP) dashboard — see own clinics + commission tracking ---
Route::prefix('associate')->as('associate.')->middleware(['auth', 'role:associate,admin', 'force.password'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Associate\DashboardController::class, 'index'])->name('dashboard');
    Route::post('/pervormas/{vertical}/{pervorma}/request-payout', [\App\Http\Controllers\Associate\DashboardController::class, 'requestPayout'])->name('pervormas.request-payout');
});

// --- Super admin (ops review + approval mitra) ---
Route::prefix('admin')->as('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/referral-overview', [\App\Http\Controllers\AdminDashboardController::class, 'referralOverview'])->name('referral-overview');
    Route::get('/consultant-per-klinik', [\App\Http\Controllers\AdminDashboardController::class, 'consultantPerKlinik'])->name('consultant-per-klinik');
    Route::get('/perusahaans', [\App\Http\Controllers\AdminPerusahaanController::class, 'index'])->name('perusahaans.index');
    Route::post('/perusahaans', [\App\Http\Controllers\AdminPerusahaanController::class, 'store'])->name('perusahaans.store');

    // A3: provisioning wizard 1 pintu (3 vertikal + akun admin tenant, 1 transaksi).
    Route::get('/provision', [\App\Http\Controllers\Admin\ProvisioningController::class, 'create'])->name('provision');
    Route::post('/provision', [\App\Http\Controllers\Admin\ProvisioningController::class, 'store'])->name('provision.store');

    // A5: audit log viewer (read-only, payload ter-mask).
    Route::get('/audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-logs.index');

    // A7: ops work queue terpadu (read-only; aksi link ke route existing).
    Route::get('/ops-queue', [\App\Http\Controllers\Admin\OpsQueueController::class, 'index'])->name('ops-queue.index');

    // A1: tenant management 3 vertikal (list/search/detail/edit; sekolah + create).
    Route::prefix('tenants')->as('tenants.')->group(function () {
        Route::get('/kliniks', [\App\Http\Controllers\Admin\Tenant\KlinikController::class, 'index'])->name('kliniks.index');
        Route::get('/kliniks/{klinik}', [\App\Http\Controllers\Admin\Tenant\KlinikController::class, 'show'])->name('kliniks.show');
        Route::get('/kliniks/{klinik}/edit', [\App\Http\Controllers\Admin\Tenant\KlinikController::class, 'edit'])->name('kliniks.edit');
        Route::put('/kliniks/{klinik}', [\App\Http\Controllers\Admin\Tenant\KlinikController::class, 'update'])->name('kliniks.update');

        Route::get('/perusahaans', [\App\Http\Controllers\Admin\Tenant\PerusahaanController::class, 'index'])->name('perusahaans.index');
        Route::get('/perusahaans/{perusahaan}', [\App\Http\Controllers\Admin\Tenant\PerusahaanController::class, 'show'])->name('perusahaans.show');
        Route::get('/perusahaans/{perusahaan}/edit', [\App\Http\Controllers\Admin\Tenant\PerusahaanController::class, 'edit'])->name('perusahaans.edit');
        Route::put('/perusahaans/{perusahaan}', [\App\Http\Controllers\Admin\Tenant\PerusahaanController::class, 'update'])->name('perusahaans.update');

        Route::get('/sekolahs', [\App\Http\Controllers\Admin\Tenant\SekolahController::class, 'index'])->name('sekolahs.index');
        Route::get('/sekolahs/create', [\App\Http\Controllers\Admin\Tenant\SekolahController::class, 'create'])->name('sekolahs.create');
        Route::post('/sekolahs', [\App\Http\Controllers\Admin\Tenant\SekolahController::class, 'store'])->name('sekolahs.store');
        Route::get('/sekolahs/{sekolah}', [\App\Http\Controllers\Admin\Tenant\SekolahController::class, 'show'])->name('sekolahs.show');
        Route::get('/sekolahs/{sekolah}/edit', [\App\Http\Controllers\Admin\Tenant\SekolahController::class, 'edit'])->name('sekolahs.edit');
        Route::put('/sekolahs/{sekolah}', [\App\Http\Controllers\Admin\Tenant\SekolahController::class, 'update'])->name('sekolahs.update');

        // A2: tenant lifecycle (suspend/activate, alasan wajib).
        Route::post('/kliniks/{klinik}/suspend', [\App\Http\Controllers\Admin\Tenant\LifecycleController::class, 'suspendKlinik'])->name('kliniks.suspend');
        Route::post('/kliniks/{klinik}/activate', [\App\Http\Controllers\Admin\Tenant\LifecycleController::class, 'activateKlinik'])->name('kliniks.lifecycle-activate');
        Route::post('/perusahaans/{perusahaan}/suspend', [\App\Http\Controllers\Admin\Tenant\LifecycleController::class, 'suspendPerusahaan'])->name('perusahaans.suspend');
        Route::post('/perusahaans/{perusahaan}/activate', [\App\Http\Controllers\Admin\Tenant\LifecycleController::class, 'activatePerusahaan'])->name('perusahaans.lifecycle-activate');
        Route::post('/sekolahs/{sekolah}/suspend', [\App\Http\Controllers\Admin\Tenant\LifecycleController::class, 'suspendSekolah'])->name('sekolahs.suspend');
        Route::post('/sekolahs/{sekolah}/activate', [\App\Http\Controllers\Admin\Tenant\LifecycleController::class, 'activateSekolah'])->name('sekolahs.lifecycle-activate');

        // C4: assign associate ke tenant (user harus role associate; kosong = unassign).
        Route::post('/kliniks/{klinik}/associate', [\App\Http\Controllers\Admin\Tenant\AssociateAssignmentController::class, 'assignKlinik'])->name('kliniks.assign-associate');
        Route::post('/perusahaans/{perusahaan}/associate', [\App\Http\Controllers\Admin\Tenant\AssociateAssignmentController::class, 'assignPerusahaan'])->name('perusahaans.assign-associate');
        Route::post('/sekolahs/{sekolah}/associate', [\App\Http\Controllers\Admin\Tenant\AssociateAssignmentController::class, 'assignSekolah'])->name('sekolahs.assign-associate');
    });

    Route::get('/pervorma-sessions', [\App\Http\Controllers\AdminDashboardController::class, 'pervormaSessions'])->name('pervorma-sessions');

    // A6: system health — failed jobs + queue size + schedule last-run.
    Route::get('/system-health', [\App\Http\Controllers\Admin\SystemHealthController::class, 'index'])->name('system-health.index');
    Route::post('/system-health/failed-jobs/{uuid}/retry', [\App\Http\Controllers\Admin\SystemHealthController::class, 'retry'])->name('system-health.failed-jobs.retry');
    Route::post('/system-health/failed-jobs/{uuid}/delete', [\App\Http\Controllers\Admin\SystemHealthController::class, 'delete'])->name('system-health.failed-jobs.delete');

    Route::post('/pervormas/{vertical}/{pervorma}/pay', [\App\Http\Controllers\Admin\PervormaController::class, 'pay'])->name('pervormas.pay');

    // Approval mitra baru (dari registrasi /daftar-mitra)
    Route::post('/kliniks/{klinik}/approve', [\App\Http\Controllers\Admin\KlinikApprovalController::class, 'store'])->name('kliniks.approve');

    // Paket klinik B2B — pembuatan & akses hanya super admin (harga sesuai budget mitra).
    Route::get('/kliniks/{klinik}/packages', [\App\Http\Controllers\Klinik\KlinikPackageController::class, 'index'])->name('kliniks.packages.index');
    Route::post('/kliniks/{klinik}/packages', [\App\Http\Controllers\Klinik\KlinikPackageController::class, 'store'])->name('kliniks.packages.store');
    Route::put('/packages/{package}', [\App\Http\Controllers\Klinik\KlinikPackageController::class, 'update'])->name('kliniks.packages.update');
    Route::delete('/packages/{package}', [\App\Http\Controllers\Klinik\KlinikPackageController::class, 'destroy'])->name('kliniks.packages.destroy');

    Route::get('/kliniks/{klinik}/markups', [\App\Http\Controllers\Klinik\MarkupRuleController::class, 'index'])->name('kliniks.markups.index');
    Route::post('/kliniks/{klinik}/markups', [\App\Http\Controllers\Klinik\MarkupRuleController::class, 'store'])->name('kliniks.markups.store');

    // Aktivasi program perusahaan (CAP)
    Route::post('/perusahaans/{perusahaan}/activate', [\App\Http\Controllers\Admin\PerusahaanActivationController::class, 'store'])->name('perusahaans.activate');

    // W3 (K6/K7/K8): assignment konsultan + workhour + exception + flag lock request
    Route::get('/assignments', [\App\Http\Controllers\AdminAssignmentController::class, 'index'])->name('assignments.index');
    Route::post('/assignments', [\App\Http\Controllers\AdminAssignmentController::class, 'store'])->name('assignments.store');
    Route::post('/assignments/{assignment}/end', [\App\Http\Controllers\AdminAssignmentController::class, 'end'])->name('assignments.end');
    Route::post('/assignments/{assignment}/activate', [\App\Http\Controllers\AdminAssignmentController::class, 'activate'])->name('assignments.activate');
    Route::post('/workhours', [\App\Http\Controllers\AdminAssignmentController::class, 'storeWorkHour'])->name('workhours.store');
    Route::delete('/workhours/{workHour}', [\App\Http\Controllers\AdminAssignmentController::class, 'destroyWorkHour'])->name('workhours.destroy');
    Route::post('/exceptions', [\App\Http\Controllers\AdminAssignmentController::class, 'storeException'])->name('exceptions.store');
    Route::delete('/exceptions/{exception}', [\App\Http\Controllers\AdminAssignmentController::class, 'destroyException'])->name('exceptions.destroy');
    Route::post('/perusahaans/{perusahaan}/request-toggle', [\App\Http\Controllers\AdminAssignmentController::class, 'toggleRequest'])->name('perusahaans.request-toggle');

    // Aktivasi program sekolah
    Route::post('/sekolahs/{sekolah}/activate', [\App\Http\Controllers\Admin\SekolahActivationController::class, 'store'])->name('sekolahs.activate');

    // Settle margin klinik (finance: margin sudah ditransfer ke klinik)
    Route::post('/splits/{split}/settle', [\App\Http\Controllers\Admin\SplitSettlementController::class, 'settle'])->name('splits.settle');

    // Ops manual verify (dual-confirm fallback, Spec §9.1 step 4)
    Route::post('/sessions/{session}/verify', [\App\Http\Controllers\Admin\SessionVerificationController::class, 'store'])->name('sessions.verify');

    // F2.2: view-as superadmin — lihat portal seolah-olah employee target.
    Route::post('/view-as/start', [\App\Http\Controllers\Admin\ViewAsController::class, 'start'])->name('view-as.start');
    Route::post('/view-as/stop', [\App\Http\Controllers\Admin\ViewAsController::class, 'stop'])->name('view-as.stop');

    // F2.1: consultant assignment management (assign/remove dari pool tenant).
    Route::get('/consultant-assignment', [\App\Http\Controllers\Admin\ConsultantAssignmentController::class, 'index'])->name('consultant-assignment.index');
    Route::post('/consultant-assignment/assign', [\App\Http\Controllers\Admin\ConsultantAssignmentController::class, 'assign'])->middleware('throttle:20,1')->name('consultant-assignment.assign');
    Route::post('/consultant-assignment/remove', [\App\Http\Controllers\Admin\ConsultantAssignmentController::class, 'remove'])->middleware('throttle:20,1')->name('consultant-assignment.remove');

    // wb-T3: katalog paket Wellbeing superadmin (edit saja, no create/delete —
    // katalog = 4 paket seeder K3). Snapshot kontrak tak tersentuh (K4).
    Route::get('/wellbeing-packages', [\App\Http\Controllers\Admin\WellbeingPackageController::class, 'index'])->name('wellbeing-packages.index');
    Route::get('/wellbeing-packages/{package}/edit', [\App\Http\Controllers\Admin\WellbeingPackageController::class, 'edit'])->name('wellbeing-packages.edit');
    Route::put('/wellbeing-packages/{package}', [\App\Http\Controllers\Admin\WellbeingPackageController::class, 'update'])->name('wellbeing-packages.update');
    Route::put('/wellbeing-packages/{package}/bands/{band}', [\App\Http\Controllers\Admin\WellbeingPackageController::class, 'updateBand'])->name('wellbeing-packages.bands.update');

    // wb-T4: Assign Program wizard superadmin (create + store + terminasi 30-day notice).
    Route::get('/wellbeing-programs/create', [\App\Http\Controllers\Admin\WellbeingProgramController::class, 'create'])->name('wellbeing-programs.create');
    Route::post('/wellbeing-programs', [\App\Http\Controllers\Admin\WellbeingProgramController::class, 'store'])->name('wellbeing-programs.store');
    Route::post('/wellbeing-programs/{program}/request-termination', [\App\Http\Controllers\Admin\WellbeingProgramController::class, 'requestTermination'])->name('wellbeing-programs.request-termination');
});
