<?php

namespace App\Http\Controllers\Sekolah;

use App\Http\Controllers\Controller;
use App\Models\Sekolah\SekolahAssessment;
use App\Models\Sekolah\SekolahSession;
use App\Models\Sekolah\SekolahStudent;
use Illuminate\Http\Request;

/**
 * Portal staf sekolah (guru/karyawan) — end-user wellbeing, mirror
 * StudentController tapi tanpa izin wali: staf dewasa consent sendiri
 * (consent_at), dan person row-nya type='staff' supaya agregat BK tidak
 * ikut menghitung mereka sebagai siswa terdaftar.
 *
 * E.1: role `sekolah_staff` + SekolahAdmin(role='sekolah_staff') menentukan tenant.
 */
class StaffController extends Controller
{
    public const WHO5_ITEMS = StudentController::WHO5_ITEMS;
    public const ANSWER_OPTIONS = StudentController::ANSWER_OPTIONS;

    /** Resolve person row staf untuk user login; super admin preview via ?staff_id=. */
    private function resolveStaff(): SekolahStudent
    {
        $user = auth()->user();

        if (optional($user->role)->name === 'sekolah_staff') {
            $rows = SekolahStudent::where('user_id', $user->id)
                ->where('type', SekolahStudent::TYPE_STAFF)->get();
            abort_unless($rows->count() === 1, 403, 'Membership ambigu. Hubungi ops.');
            $staff = $rows->first();

            abort_if($staff->sekolah?->status === 'suspended', 403, 'Akun organisasi Anda sedang disuspend. Hubungi Soluusi.');
            if (!request()->isMethod('GET')
                && !request()->routeIs('sekolah.staff.booking.cancel')
                && !request()->routeIs('sekolah.staff.request.cancel')) {
                abort_unless($staff->sekolah?->is_active && $staff->consent_at, 403, 'Program belum aktif untuk akun Anda. Hubungi BK.');
            }

            return $staff;
        }

        abort_unless(request()->isMethod('GET'), 403, 'Preview read-only.');
        $staff = SekolahStudent::where('type', SekolahStudent::TYPE_STAFF)->find(request('staff_id'))
            ?: SekolahStudent::where('type', SekolahStudent::TYPE_STAFF)->whereHas('user')
                ->where('sekolah_id', \App\Support\SekolahContext::current()?->id)
                ->first();
        abort_unless($staff, 403, 'Belum ada staf dengan akun untuk preview.');

        return $staff;
    }

    public function index()
    {
        $staff = $this->resolveStaff();
        $sekolah = $staff->sekolah;
        $usedMonth = \App\Services\BookingService::quotaUsed($staff, now());
        $history = $staff->sessions()->with('consultant.user')->orderByDesc('id')->limit(10)->get();

        $latest = $staff->assessments()->latest('taken_at')->first();
        $trend = $staff->assessments()->orderBy('taken_at')->limit(6)->get()
            ->map(fn ($a) => ['label' => $a->taken_at->format('d M'), 'score' => $a->score]);

        $advices = \App\Models\SessionSummary::whereIn('sekolah_session_id', $history->pluck('id'))
            ->where('status', 'completed')
            ->get(['sekolah_session_id', 'client_advice'])
            ->keyBy('sekolah_session_id');

        return view('sekolah.staff', [
            'staff' => $staff,
            'sekolah' => $sekolah,
            'isPreview' => optional(auth()->user()->role)->name !== 'sekolah_staff',
            'quota' => $sekolah->sessions_per_student_month,
            'usedMonth' => $usedMonth,
            'history' => $history,
            'assessment' => $latest,
            'assessmentTrend' => $trend,
            'needsAssessment' => ! $latest || $latest->taken_at->lt(now()->subDays(30)),
            'advices' => $advices,
        ]);
    }

    public function assessmentForm()
    {
        $staff = $this->resolveStaff();

        return view('sekolah.assessment', [
            'student' => $staff,          // view sekolah/assessment.blade.php pakai nama $student
            'sekolah' => $staff->sekolah,
            'isPreview' => optional(auth()->user()->role)->name !== 'sekolah_staff',
            'items' => self::WHO5_ITEMS,
            'options' => self::ANSWER_OPTIONS,
            'latest' => $staff->assessments()->latest('taken_at')->first(),
            'action' => route('sekolah.staff.assessment.store'),
        ]);
    }

    public function assessmentStore(Request $r)
    {
        $staff = $this->resolveStaff();

        $r->validate([
            'answers' => ['required', 'array', 'size:5'],
            'answers.*' => ['integer', 'min:0', 'max:5'],
        ]);

        $answers = array_values($r->input('answers'));
        SekolahAssessment::create([
            'sekolah_id' => $staff->sekolah_id,
            'student_id' => $staff->id,
            'kelas' => $staff->kelas,
            'answers' => $answers,
            'score' => array_sum($answers) * 4, // WHO-5: 0-25 → 0-100
            'taken_at' => now(),
        ]);

        return redirect()->route('sekolah.staff')
            ->with('success', 'Terima kasih! Hasil asesmen kamu sudah tersimpan.');
    }

    public function bookingForm()
    {
        $staff = $this->resolveStaff();
        $sekolah = $staff->sekolah;
        $usedMonth = \App\Services\BookingService::quotaUsed($staff, now());
        $quota = $sekolah->sessions_per_student_month;

        return view('sekolah.booking', [
            'student' => $staff,          // view sekolah/booking.blade.php pakai nama $student
            'sekolah' => $sekolah,
            'isPreview' => optional(auth()->user()->role)->name !== 'sekolah_staff',
            'types' => ['psikolog' => 'Psikolog Anak & Remaja', 'karir' => 'Konselor Karir'],
            'consultants' => \App\Models\ConsultantPool::poolFor('sekolah', $sekolah->id),
            'slots' => \App\Services\BookingService::available('sekolah', $sekolah->id),
            'quotaLeft' => max(0, $quota - $usedMonth),
            'action' => route('sekolah.staff.booking.store'),
        ]);
    }

    public function bookingStore(Request $r)
    {
        $staff = $this->resolveStaff();
        $r->validate([
            'konsultan_type' => ['required', 'in:psikolog,karir'],
            'schedule_id' => ['required', 'integer', 'exists:schedules,id'],
        ]);

        \App\Services\BookingService::book('sekolah', $staff, $r->only('schedule_id', 'konsultan_type'));

        return redirect()->route('sekolah.staff')
            ->with('success', 'Sesi berhasil dijadwalkan! Detail konsultan dikirim mendekati jadwal.');
    }

    public function bookingCancel(SekolahSession $session)
    {
        $staff = $this->resolveStaff();
        abort_unless($session->student_id === $staff->id, 403);
        abort_unless($session->status === 'scheduled', 422, 'Hanya sesi terjadwal yang bisa dibatalkan.');

        request()->validate(['reason' => ['required', 'string', 'max:1000']]);
        $restored = \App\Services\BookingService::cancel('sekolah', $session, request('reason'));

        return back()->with('success', $restored
            ? 'Sesi dibatalkan. Kuota kamu kembali.'
            : 'Sesi dibatalkan. Kuota tetap terpakai karena melewati batas pembatalan.');
    }

    public function bookingReschedule(Request $r, SekolahSession $session)
    {
        $staff = $this->resolveStaff();
        abort_unless($session->student_id === $staff->id, 403);
        abort_unless($session->status === 'scheduled', 422, 'Hanya sesi terjadwal yang bisa diubah.');

        $r->validate(['schedule_id' => ['required', 'integer', 'exists:schedules,id']]);
        \App\Services\BookingService::reschedule('sekolah', $staff, $session, $r->integer('schedule_id'));

        return redirect()->route('sekolah.staff')->with('success', 'Jadwal sesi berhasil diubah.');
    }

    /** Minta konseling tanpa pilih slot: masuk antrean review BK (paritas siswa B.1). */
    public function requestConsultation(Request $r)
    {
        $staff = $this->resolveStaff();
        $validated = $r->validate([
            'konsultan_type' => ['required', 'in:psikolog,karir'],
            'topic_category' => ['nullable', 'string', 'max:100'],
            'request_notes' => ['required', 'string', 'max:2000'],
        ]);

        $sekolah = $staff->sekolah;
        abort_if($staff->sessions()->where('status', 'scheduled')->whereNull('scheduled_at')->exists(),
            422, 'Masih ada permintaan menunggu review.');

        $monthStart = now()->startOfMonth();
        $usedMonth = $staff->sessions()
            ->whereIn('status', ['scheduled', 'done'])
            ->where(fn ($q) => $q->where('scheduled_at', '>=', $monthStart)->orWhereNull('scheduled_at'))->count();
        abort_if($usedMonth >= $sekolah->sessions_per_student_month, 422, 'Kuota bulan ini habis.');

        SekolahSession::create([
            'sekolah_id' => $sekolah->id,
            'student_id' => $staff->id,
            'kelas' => $staff->kelas,
            'consultant_id' => null,
            'konsultan_type' => $validated['konsultan_type'],
            'topic_category' => $validated['topic_category'] ?? null,
            'status' => 'scheduled',
            'scheduled_at' => null,
            'request_notes' => $validated['request_notes'],
            'requested_by_user_id' => auth()->id(),
            'requested_for_role' => 'self',
        ]);

        return redirect()->route('sekolah.staff')
            ->with('success', 'Permintaan terkirim. Guru BK akan meninjau dan mengatur jadwal konseling.');
    }

    public function cancelPendingRequest(SekolahSession $session)
    {
        $staff = $this->resolveStaff();
        abort_unless($session->student_id === $staff->id, 403);
        abort_unless($session->status === 'scheduled' && $session->scheduled_at === null,
            422, 'Hanya permintaan yang belum terjadwal bisa dibatalkan di sini.');

        \App\Support\SessionStateMachine::cancel($session, 'Dibatalkan oleh staf sebelum terjadwal');

        return redirect()->route('sekolah.staff')
            ->with('success', 'Permintaan konseling dibatalkan. Kuota kamu kembali.');
    }
}