<?php

namespace App\Http\Controllers\Sekolah;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentInstrument;
use App\Models\Sekolah\SekolahAssessment;
use App\Models\Sekolah\SekolahSession;
use App\Models\Sekolah\SekolahStudent;
use App\Services\AssessmentAccess;
use App\Services\SekolahAssessmentService;
use App\Services\Screening\InstrumentScoring;
use App\Support\SekolahContext;
use Illuminate\Http\Request;

/**
 * Portal siswa (end-user wellbeing). White-label sekolah — zero Soluusi.
 * Siswa lihat data MILIKNYA SENDIRI (kuota, riwayat, hasil WHO-5).
 * Mirror EmployeeController (CAP) — konsultan_type & label disesuaikan sekolah.
 */
class StudentController extends Controller
{
    /** Pertanyaan WHO-5 — sama dengan EmployeeController (satu instrumen). */
    public const WHO5_ITEMS = \App\Http\Controllers\Perusahaan\EmployeeController::WHO5_ITEMS;
    public const ANSWER_OPTIONS = \App\Http\Controllers\Perusahaan\EmployeeController::ANSWER_OPTIONS;

    /** Resolve SekolahStudent untuk user login; super admin preview via ?student_id=. */
    private function resolveStudent(): SekolahStudent
    {
        $user = auth()->user();

        if (optional($user->role)->name === 'student') {
            $rows = SekolahStudent::where('user_id', $user->id)->where('type', SekolahStudent::TYPE_STUDENT)->get();
            abort_unless($rows->count() === 1, 403, 'Membership ambigu. Hubungi ops.');
            $student = $rows->first();
            // Suspend = blok penuh (GET+POST). is_active/consent = blok POST saja.
            abort_if($student->sekolah?->status === 'suspended', 403, 'Akun organisasi Anda sedang disuspend. Hubungi Soluusi.');
            if (!request()->isMethod('GET') && !request()->routeIs('sekolah.booking.cancel') && !request()->routeIs('sekolah.siswa.request.cancel')) {
                abort_unless($student->sekolah?->is_active && $student->parent_consent_at, 403, 'Program/izin wali belum aktif. Hubungi BK.');
            }
            abort_unless($student, 403, 'Akun belum terhubung ke data siswa.');
            return $student;
        }

        abort_unless(request()->isMethod('GET'), 403, 'Preview read-only.');
        $student = SekolahStudent::where('type', SekolahStudent::TYPE_STUDENT)->find(request('student_id'))
            ?: SekolahStudent::where('type', SekolahStudent::TYPE_STUDENT)->whereHas('user')
                ->where('sekolah_id', SekolahContext::current()?->id)
                ->first();
        abort_unless($student, 403, 'Belum ada siswa dengan akun untuk preview.');
        return $student;
    }

    public function index()
    {
        $student = $this->resolveStudent();
        $sekolah = $student->sekolah;
        $usedMonth = \App\Services\BookingService::quotaUsed($student, now());
        $quota = $sekolah->sessions_per_student_month;
        $history = $student->sessions()->with('consultant.user')->orderByDesc('id')->limit(10)->get();

        $latest = $student->assessments()->where('instrument_code', 'who5')->latest('taken_at')->first();
        $trend = $student->assessments()->where('instrument_code', 'who5')->orderBy('taken_at')->limit(6)->get()
            ->map(fn ($a) => ['label' => $a->taken_at->format('d M'), 'score' => $a->score]);
        $needsAssessment = !$latest || $latest->taken_at->lt(now()->subDays(30));

        // Saran AI per sesi (siswa lihat miliknya sendiri saja — C.3).
        $advices = \App\Models\SessionSummary::whereIn('sekolah_session_id', $history->pluck('id'))
            ->where('status', 'completed')
            ->get(['sekolah_session_id', 'client_advice'])
            ->keyBy('sekolah_session_id');

        return view('sekolah.student', [
            'student' => $student,
            'sekolah' => $sekolah,
            'isPreview' => optional(auth()->user()->role)->name !== 'student',
            'quota' => $quota,
            'usedMonth' => $usedMonth,
            'history' => $history,
            'assessment' => $latest,
            'assessmentTrend' => $trend,
            'needsAssessment' => $needsAssessment,
            'advices' => $advices,
        ]);
    }

    public function assessmentForm(Request $r, ?string $code = null)
    {
        $student = $this->resolveStudent();
        $code ??= 'who5';

        $assignment = AssessmentAccess::resolveForSekolahMember($student, $code);
        abort_unless($assignment, 403, 'Instrumen belum di-assign untuk kamu.');
        if (AssessmentAccess::hasSubmitted($assignment, $student->id, 'sekolah')) {
            return redirect()->route('sekolah.assessment.list')->with('info', 'Kamu sudah mengisi asesmen ini untuk periode sekarang.');
        }

        $instrument = AssessmentInstrument::where('code', $code)->firstOrFail();
        $latest = $student->assessments()->where('instrument_code', $code)->latest('taken_at')->first();

        return view('sekolah.assessment-instrument', [
            'student' => $student,
            'sekolah' => $student->sekolah,
            'isPreview' => optional(auth()->user()->role)->name !== 'student',
            'instrument' => $instrument,
            'items' => InstrumentScoring::items($instrument),
            'latest' => $latest,
            'listUrl' => route('sekolah.assessment.list'),
            'action' => route('sekolah.assessment.store', $code),
        ]);
    }

    /** B3: simpan jawaban via engine; legacy indexed shape (0-4) tetap jalan (D8). */
    public function assessmentStore(Request $r, ?string $code = null)
    {
        $student = $this->resolveStudent();
        $code ??= 'who5';

        $assignment = AssessmentAccess::resolveForSekolahMember($student, $code);
        abort_unless($assignment, 403, 'Instrumen belum di-assign untuk kamu.');
        if (AssessmentAccess::hasSubmitted($assignment, $student->id, 'sekolah')) {
            return redirect()->route('sekolah.assessment.list')->with('info', 'Kamu sudah mengisi asesmen ini untuk periode sekarang.');
        }

        $instrument = AssessmentInstrument::where('code', $code)->firstOrFail();
        SekolahAssessmentService::submit($student, $assignment, $instrument, $r);

        return redirect()->route('sekolah.student')
            ->with('success', 'Terima kasih! Hasil asesmen kamu sudah tersimpan.');
    }

    /** B3: daftar instrumen yang di-assign untuk siswa ini (D2). */
    public function assessmentList()
    {
        $student = $this->resolveStudent();

        $instruments = AssessmentAccess::forSekolahMember($student)
            ->map(function (AssessmentAssignment $assignment) use ($student) {
                $instrument = AssessmentInstrument::where('code', $assignment->instrument_code)->first();
                if (!$instrument) {
                    return null;
                }
                $latest = $student->assessments()
                    ->where('instrument_code', $instrument->code)
                    ->latest('taken_at')->first();
                $instrument->latest_score = $latest?->score;
                $instrument->latest_band = $latest ? $instrument->bandLabelFor((int) $latest->score) : null;
                $instrument->latest_taken = $latest?->taken_at;
                $instrument->done = AssessmentAccess::hasSubmitted($assignment, $student->id, 'sekolah');
                return $instrument;
            })
            ->filter()
            ->values();

        return view('sekolah.assessment-instruments', [
            'student' => $student,
            'sekolah' => $student->sekolah,
            'isPreview' => optional(auth()->user()->role)->name !== 'student',
            'instruments' => $instruments,
            'backUrl' => route('sekolah.student'),
            'formRoute' => fn ($code) => route('sekolah.assessment.form', $code),
        ]);
    }

    public function bookingForm()
    {
        $student = $this->resolveStudent();
        $sekolah = $student->sekolah;
        $usedMonth = \App\Services\BookingService::quotaUsed($student, now());
        $quota = $sekolah->sessions_per_student_month;

        return view('sekolah.booking', [
            'student' => $student,
            'sekolah' => $sekolah,
            'isPreview' => optional(auth()->user()->role)->name !== 'student',
            'types' => ['psikolog' => 'Psikolog Anak & Remaja', 'karir' => 'Konselor Karir'],
            'consultants' => \App\Models\ConsultantPool::poolFor('sekolah', $sekolah->id),
            'slots' => \App\Services\BookingService::available('sekolah', $sekolah->id),
            'quotaLeft' => max(0, $quota - $usedMonth),
        ]);
    }

    public function bookingStore(Request $r)
    {
        $student = $this->resolveStudent();
        $r->validate([
            'konsultan_type' => ['required', 'in:psikolog,karir'],
            'consultant_id' => ['nullable', 'integer'],
            'mode' => ['nullable', 'in:online,offline'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ]);

        $r->validate(['schedule_id' => ['required', 'integer', 'exists:schedules,id']]);
        \App\Services\BookingService::book('sekolah', $student, $r->only('schedule_id', 'konsultan_type'));

        return redirect()->route('sekolah.student')
            ->with('success', 'Sesi berhasil dijadwalkan! Detail konsultan dikirim mendekati jadwal.');
    }

    public function bookingCancel(SekolahSession $session)
    {
        $student = $this->resolveStudent();
        abort_unless($session->student_id === $student->id, 403);
        abort_unless($session->status === 'scheduled', 422, 'Hanya sesi terjadwal yang bisa dibatalkan.');

        request()->validate(['reason' => ['required', 'string', 'max:1000']]);
        $restored = \App\Services\BookingService::cancel('sekolah', $session, request('reason'));

        return back()->with('success', $restored ? 'Sesi dibatalkan. Kuota kamu kembali.' : 'Sesi dibatalkan. Kuota tetap terpakai karena melewati batas pembatalan.');
    }

    /** Reschedule = cancel + pilih slot baru (paritas dengan portal karyawan). */
    public function bookingReschedule(Request $r, SekolahSession $session)
    {
        $student = $this->resolveStudent();
        abort_unless($session->student_id === $student->id, 403);
        abort_unless($session->status === 'scheduled', 422, 'Hanya sesi terjadwal yang bisa diubah.');

        $r->validate(['schedule_id' => ['required', 'integer', 'exists:schedules,id']]);
        \App\Services\BookingService::reschedule('sekolah', $student, $session, $r->integer('schedule_id'));

        return redirect()->route('sekolah.student')->with('success', 'Jadwal sesi berhasil diubah.');
    }

    /** Minta konseling tanpa pilih slot: masuk antrean review BK (paritas F1.1 employee flow). */
    public function requestConsultation(Request $r)
    {
        $student = $this->resolveStudent();
        $validated = $r->validate([
            'konsultan_type' => ['required', 'in:psikolog,karir'],
            'topic_category' => ['nullable', 'string', 'max:100'],
            'request_notes' => ['required', 'string', 'max:2000'],
        ]);

        $sekolah = $student->sekolah;
        abort_if($student->sessions()->where('status', 'scheduled')->whereNull('scheduled_at')->exists(),
            422, 'Masih ada permintaan menunggu review.');

        $monthStart = now()->startOfMonth();
        $usedMonth = $student->sessions()
            ->whereIn('status', ['scheduled', 'done'])
            ->where(fn ($q) => $q->where('scheduled_at', '>=', $monthStart)->orWhereNull('scheduled_at'))->count();
        abort_if($usedMonth >= $sekolah->sessions_per_student_month, 422, 'Kuota bulan ini habis.');

        // Gate review BK: 'scheduled' + scheduled_at NULL + consultant_id NULL.
        SekolahSession::create([
            'sekolah_id' => $sekolah->id,
            'student_id' => $student->id,
            'kelas' => $student->kelas,
            'consultant_id' => null,
            'konsultan_type' => $validated['konsultan_type'],
            'topic_category' => $validated['topic_category'] ?? null,
            'status' => 'scheduled',
            'scheduled_at' => null,
            'request_notes' => $validated['request_notes'],
            'requested_by_user_id' => auth()->id(),
            'requested_for_role' => 'self',
        ]);

        return redirect()->route('sekolah.student')
            ->with('success', 'Permintaan terkirim. Guru BK akan meninjau dan mengatur jadwal konseling.');
    }

    /** Batalkan permintaan konseling yang masih menunggu review BK (paritas T5.3). */
    public function cancelPendingRequest(SekolahSession $session)
    {
        $student = $this->resolveStudent();
        abort_unless($session->student_id === $student->id, 403);
        abort_unless($session->status === 'scheduled' && $session->scheduled_at === null,
            422, 'Hanya permintaan yang belum terjadwal bisa dibatalkan di sini.');

        \App\Support\SessionStateMachine::cancel($session, 'Dibatalkan oleh siswa sebelum terjadwal');

        return redirect()->route('sekolah.student')
            ->with('success', 'Permintaan konseling dibatalkan. Kuota kamu kembali.');
    }
}
