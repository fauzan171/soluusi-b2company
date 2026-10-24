<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Models\Perusahaan\PerusahaanAssessment;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use Illuminate\Http\Request;

/**
 * Portal karyawan (end-user wellbeing). Branded white-label — zero Soluusi.
 * Karyawan lihat data MILIKNYA SENDIRI (quota, riwayat, hasil assessment).
 */
class EmployeeController extends Controller
{
    /** Pertanyaan WHO-5 (terjemahan Indonesia, instrumen standar). */
    public const WHO5_ITEMS = [
        'Saya merasa ceria dan senang',
        'Saya merasa tenang dan rileks',
        'Saya merasa aktif dan bersemangat',
        'Saya bangun tidur dengan perasaan segar dan beristirahat',
        'Kehidupan sehari-hari saya dipenuhi hal yang menarik',
    ];

    public const ANSWER_OPTIONS = [
        0 => 'Tidak pernah',
        1 => 'Sebagian kecil waktu',
        2 => 'Kurang dari setengah waktu',
        3 => 'Lebih dari setengah waktu',
        4 => 'Sebagian besar waktu',
        5 => 'Sepanjang waktu',
    ];

    /** Resolve PerusahaanEmployee untuk user login; super admin bisa preview via ?employee_id=. */
    private function resolveEmployee(): PerusahaanEmployee
    {
        $user = auth()->user();

        if (optional($user->role)->name === 'employee') {
            $rows = PerusahaanEmployee::where('user_id', $user->id)->get();
            abort_unless($rows->count() === 1, 403, 'Membership ambigu. Hubungi ops.');
            $employee = $rows->first();
            if (!request()->isMethod('GET') && !request()->routeIs('perusahaan.booking.cancel')) {
                abort_unless($employee->perusahaan?->is_active && $employee->consent_at, 403, 'Program/consent belum aktif. Hubungi HR.');
            }
            abort_unless($employee, 403, 'Akun belum terhubung ke data karyawan.');
            return $employee;
        }

        abort_unless(request()->isMethod('GET'), 403, 'Preview read-only.');
        // Super admin: preview experience karyawan.
        $employee = PerusahaanEmployee::find(request('employee_id'))
            ?: PerusahaanEmployee::whereHas('user')
                ->where('perusahaan_id', \App\Support\PerusahaanContext::current()?->id)
                ->first();
        abort_unless($employee, 403, 'Belum ada karyawan dengan akun untuk preview.');
        return $employee;
    }

    public function index()
    {
        $employee = $this->resolveEmployee();
        $perusahaan = $employee->perusahaan;
        $monthStart = now()->startOfMonth();

        $usedMonth = $employee->sessions()->where('status', 'done')
            ->where('completed_at', '>=', $monthStart)->count();
        $scheduledMonth = $employee->sessions()->where('status', 'scheduled')
            ->where('scheduled_at', '>=', $monthStart)->count();
        $quota = $perusahaan->sessions_per_employee_month;
        $history = $employee->sessions()->with('consultant.user')->orderByDesc('id')->limit(10)->get();

        $latest = $employee->assessments()->latest('taken_at')->first();
        $trend = $employee->assessments()->orderBy('taken_at')->limit(6)->get()
            ->map(fn ($a) => ['label' => $a->taken_at->format('d M'), 'score' => $a->score]);
        $needsAssessment = !$latest || $latest->taken_at->lt(now()->subDays(30));

        return view('perusahaan.employee', [
            'employee' => $employee,
            'perusahaan' => $perusahaan,
            'isPreview' => optional(auth()->user()->role)->name !== 'employee',
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
        $employee = $this->resolveEmployee();
        $latest = $employee->assessments()->latest('taken_at')->first();

        return view('perusahaan.assessment', [
            'employee' => $employee,
            'perusahaan' => $employee->perusahaan,
            'isPreview' => optional(auth()->user()->role)->name !== 'employee',
            'items' => self::WHO5_ITEMS,
            'options' => self::ANSWER_OPTIONS,
            'latest' => $latest,
        ]);
    }

    public function assessmentStore(Request $r)
    {
        $employee = $this->resolveEmployee();

        $r->validate([
            'answers' => ['required', 'array', 'size:5'],
            'answers.*' => ['integer', 'min:0', 'max:5'],
        ]);

        $answers = array_values($r->input('answers'));
        PerusahaanAssessment::create([
            'perusahaan_id' => $employee->perusahaan_id,
            'employee_id' => $employee->id,
            'departement_id' => $employee->departement_id,
            'answers' => $answers,
            'score' => array_sum($answers) * 4, // 0-25 → 0-100 (formula WHO-5 standar)
            'taken_at' => now(),
        ]);

        return redirect()->route('perusahaan.employee')
            ->with('success', 'Terima kasih! Hasil asesmen kamu sudah tersimpan.');
    }

    public function bookingForm()
    {
        $employee = $this->resolveEmployee();
        $perusahaan = $employee->perusahaan;
        $monthStart = now()->startOfMonth();

        $usedMonth = $employee->sessions()
            ->whereIn('status', ['scheduled', 'done'])
            ->where('scheduled_at', '>=', $monthStart)->count();
        $quota = $perusahaan->sessions_per_employee_month;

        return view('perusahaan.booking', [
            'employee' => $employee,
            'perusahaan' => $perusahaan,
            'isPreview' => optional(auth()->user()->role)->name !== 'employee',
            'types' => ['psikolog' => 'Psikolog', 'nutrisionis' => 'Nutrisionis', 'fisioterapis' => 'Fisioterapis', 'karir' => 'Konselor Karir'],
            'consultants' => \App\Models\ConsultantPool::poolFor('perusahaan', $perusahaan->id),
            'slots' => \App\Services\BookingService::available('perusahaan', $perusahaan->id),
            'quotaLeft' => max(0, $quota - $usedMonth),
        ]);
    }

    /** Batalkan sesi terjadwal milik sendiri; kuota mengikuti cutoff. */
    public function bookingCancel(PerusahaanSession $session)
    {
        $employee = $this->resolveEmployee();
        abort_unless($session->employee_id === $employee->id, 403);
        abort_unless($session->status === 'scheduled', 422, 'Hanya sesi terjadwal yang bisa dibatalkan.');

        request()->validate(['reason' => ['required', 'string', 'max:1000']]);
        $restored = \App\Services\BookingService::cancel('perusahaan', $session, request('reason'));

        return back()->with('success', $restored ? 'Sesi dibatalkan. Kuota kamu kembali.' : 'Sesi dibatalkan. Kuota tetap terpakai karena melewati batas pembatalan.');
    }

    /** Reschedule = cancel + pilih slot baru (form booking diprefill). */
    public function bookingReschedule(Request $r, PerusahaanSession $session)
    {
        $employee = $this->resolveEmployee();
        abort_unless($session->employee_id === $employee->id, 403);
        abort_unless($session->status === 'scheduled', 422, 'Hanya sesi terjadwal yang bisa diubah.');

        $r->validate(['schedule_id' => ['required', 'integer', 'exists:schedules,id']]);
        \App\Services\BookingService::reschedule('perusahaan', $employee, $session, $r->integer('schedule_id'));

        return redirect()->route('perusahaan.employee')->with('success', 'Jadwal sesi berhasil diubah.');
    }

    public function bookingStore(Request $r)
    {
        $employee = $this->resolveEmployee();
        $r->validate([
            'konsultan_type' => ['required', 'in:psikolog,nutrisionis,fisioterapis,karir'],
            'consultant_id' => ['nullable', 'integer'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ]);

        $r->validate(['schedule_id' => ['required', 'integer', 'exists:schedules,id']]);
        \App\Services\BookingService::book('perusahaan', $employee, $r->only('schedule_id', 'konsultan_type'));

        return redirect()->route('perusahaan.employee')
            ->with('success', 'Sesi berhasil dijadwalkan! Detail konsultan akan dikirim mendekati jadwal.');
    }
}
