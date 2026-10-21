<?php

namespace App\Http\Controllers\Sekolah;

use App\Http\Controllers\Controller;
use App\Models\Sekolah\SekolahAssessment;
use App\Models\Sekolah\SekolahSession;
use App\Models\Sekolah\SekolahStudent;
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
            $rows = SekolahStudent::where('user_id', $user->id)->get();
            abort_unless($rows->count() === 1, 403, 'Membership ambigu. Hubungi ops.');
            $student = $rows->first();
            if (!request()->isMethod('GET') && !request()->routeIs('sekolah.booking.cancel')) {
                abort_unless($student->sekolah?->is_active && $student->parent_consent_at, 403, 'Program/izin wali belum aktif. Hubungi BK.');
            }
            abort_unless($student, 403, 'Akun belum terhubung ke data siswa.');
            return $student;
        }

        abort_unless(request()->isMethod('GET'), 403, 'Preview read-only.');
        $student = SekolahStudent::find(request('student_id'))
            ?: SekolahStudent::whereHas('user')
                ->where('sekolah_id', SekolahContext::current()?->id)
                ->first();
        abort_unless($student, 403, 'Belum ada siswa dengan akun untuk preview.');
        return $student;
    }

    public function index()
    {
        $student = $this->resolveStudent();
        $sekolah = $student->sekolah;
        $monthStart = now()->startOfMonth();

        $usedMonth = $student->sessions()->where('status', 'done')
            ->where('completed_at', '>=', $monthStart)->count();
        $scheduledMonth = $student->sessions()->where('status', 'scheduled')
            ->where('scheduled_at', '>=', $monthStart)->count();
        $quota = $sekolah->sessions_per_student_month;
        $history = $student->sessions()->with('consultant.user')->orderByDesc('id')->limit(10)->get();

        $latest = $student->assessments()->latest('taken_at')->first();
        $trend = $student->assessments()->orderBy('taken_at')->limit(6)->get()
            ->map(fn ($a) => ['label' => $a->taken_at->format('d M'), 'score' => $a->score]);
        $needsAssessment = !$latest || $latest->taken_at->lt(now()->subDays(30));

        return view('sekolah.student', [
            'student' => $student,
            'sekolah' => $sekolah,
            'isPreview' => optional(auth()->user()->role)->name !== 'student',
            'quota' => $quota,
            'usedMonth' => $usedMonth,
            'scheduledMonth' => $scheduledMonth,
            'history' => $history,
            'assessment' => $latest,
            'assessmentTrend' => $trend,
            'needsAssessment' => $needsAssessment,
        ]);
    }

    public function assessmentForm()
    {
        $student = $this->resolveStudent();
        $latest = $student->assessments()->latest('taken_at')->first();

        return view('sekolah.assessment', [
            'student' => $student,
            'sekolah' => $student->sekolah,
            'isPreview' => optional(auth()->user()->role)->name !== 'student',
            'items' => self::WHO5_ITEMS,
            'options' => self::ANSWER_OPTIONS,
            'latest' => $latest,
        ]);
    }

    public function assessmentStore(Request $r)
    {
        $student = $this->resolveStudent();

        $r->validate([
            'answers' => ['required', 'array', 'size:5'],
            'answers.*' => ['integer', 'min:0', 'max:5'],
        ]);

        $answers = array_values($r->input('answers'));
        SekolahAssessment::create([
            'sekolah_id' => $student->sekolah_id,
            'student_id' => $student->id,
            'kelas' => $student->kelas,
            'answers' => $answers,
            'score' => array_sum($answers) * 4, // WHO-5: 0-25 → 0-100
            'taken_at' => now(),
        ]);

        return redirect()->route('sekolah.student')
            ->with('success', 'Terima kasih! Hasil asesmen kamu sudah tersimpan.');
    }

    public function bookingForm()
    {
        $student = $this->resolveStudent();
        $sekolah = $student->sekolah;
        $monthStart = now()->startOfMonth();

        $usedMonth = $student->sessions()
            ->whereIn('status', ['scheduled', 'done'])
            ->where('scheduled_at', '>=', $monthStart)->count();
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

    /** Slot booking: 7 hari ke depan, jam sekolah 08-15, skip weekend. */
    private function slots()
    {
        return collect(range(1, 7))->flatMap(function ($i) {
            $day = now()->addDays($i);
            if ($day->isWeekend()) {
                return collect();
            }
            return collect(range(8, 15))->map(fn ($h) => $day->copy()->setTime($h, 0));
        })->filter(fn ($s) => $s->isFuture())->groupBy(fn ($s) => $s->format('Y-m-d'));
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
}
