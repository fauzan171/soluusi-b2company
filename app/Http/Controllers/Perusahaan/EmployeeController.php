<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Models\Consultant;
use App\Models\ConsultantPool;
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
            if (!request()->isMethod('GET') && !request()->routeIs('perusahaan.booking.cancel') && !request()->routeIs('perusahaan.consultants.start-chat')) {
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

    /** Halodoc-style: pilih konsultan, langsung chat tanpa jadwal. */
    public function consultants(Request $request)
    {
        $employee = $this->resolveEmployee();
        abort_unless($employee, 403);

        if (! $employee->consent_at) {
            return redirect()->route('perusahaan.employee')->with('error', 'Berikan persetujuan dulu.');
        }

        $perusahaan = $employee->perusahaan;
        abort_unless($perusahaan, 404);

        $q = $request->string('q')->trim()->toString();
        $category = $request->string('category')->trim()->toString();

        $pool = ConsultantPool::poolFor('perusahaan', $perusahaan->id);
        $consultants = $pool
            ->when($q, fn ($c) => $c->filter(fn ($item) => str_contains(strtolower($item['name']), strtolower($q))))
            ->when($category, fn ($c) => $c->filter(fn ($item) => str_contains($item['category'], $category)))
            ->values();

        $categories = $pool->pluck('category')->filter()->unique()->sort()->values();

        return view('perusahaan.consultants', [
            'perusahaan' => $perusahaan,
            'employee' => $employee,
            'consultants' => $consultants,
            'categories' => $categories,
            'q' => $q,
            'activeCategory' => $category,
        ]);
    }

    /** Gate jadwal: tanpa slot + ACC konsultan, chat tidak bisa dibuka. Arahkan ke form booking. */
    public function startChat(Request $request, Consultant $consultant)
    {
        $employee = $this->resolveEmployee();
        abort_unless($employee, 403);

        if (! $employee->consent_at) {
            return redirect()->route('perusahaan.employee')->with('error', 'Berikan persetujuan dulu.');
        }

        $perusahaan = $employee->perusahaan;
        abort_unless($perusahaan, 404);

        // Sesi aktif dengan konsultan yang sama → langsung ke room (read-only sampai di-ACC).
        $existing = $employee->sessions()
            ->where('consultant_id', $consultant->id)
            ->whereIn('status', ['scheduled', 'pending', 'confirmed'])
            ->first();

        if ($existing) {
            return redirect()->route('perusahaan.chat', $existing);
        }

        return redirect()->route('perusahaan.booking')
            ->with('error', 'Pilih jadwal konsultasi dulu sebelum chat.');
    }

    /** Minta konsultasi tanpa pilih slot: assign 1 konsultan pool terdekat beban. */
    public function requestConsultation(Request $r)
    {
        $employee = $this->resolveEmployee();
        $r->validate([
            'konsultan_type' => ['required', 'in:psikolog,nutrisionis,fisioterapis,karir'],
            'topic_category' => ['nullable', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $perusahaan = $employee->perusahaan;
        $monthStart = now()->startOfMonth();
        $usedMonth = $employee->sessions()
            ->whereIn('status', ['scheduled', 'done'])
            ->where('scheduled_at', '>=', $monthStart)->count();
        abort_if($usedMonth >= $perusahaan->sessions_per_employee_month, 422, 'Kuota bulan ini habis.');

        // Assign konsultan dari pool: tipe cocok, beban scheduled terendah.
        $pool = ConsultantPool::poolFor('perusahaan', $perusahaan->id);
        $candidates = $pool->where('type', $r->konsultan_type);
        abort_if($candidates->isEmpty(), 422, 'Tidak ada konsultan tipe ini di program kamu.');

        $assigned = $candidates->sortBy(fn ($c) => PerusahaanSession::where('consultant_id', $c['id'])
            ->where('status', 'scheduled')
            ->count())->first();

        // Gate ACC: status 'scheduled' + scheduled_at NULL = menunggu konsultan set jadwal
        // (nilai 'pending' tidak lolos CHECK constraint status — lihat migrasi 2026_10_21).
        $session = PerusahaanSession::create([
            'perusahaan_id' => $perusahaan->id,
            'employee_id' => $employee->id,
            'departement_id' => $employee->departement_id,
            'consultant_id' => $assigned['id'],
            'konsultan_type' => $r->konsultan_type,
            'topic_category' => $r->topic_category,
            'status' => 'scheduled',
            'scheduled_at' => null,
        ]);

        return redirect()->route('perusahaan.chat', $session)
            ->with('success', 'Permintaan terkirim. Chat aktif setelah konsultan menyetujui jadwal.');
    }
}
