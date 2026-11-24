<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            // Suspend = blok penuh (GET+POST). is_active/consent = blok POST saja.
            abort_if($employee->perusahaan?->status === 'suspended', 403, 'Akun organisasi Anda sedang disuspend. Hubungi Soluusi.');
            if (!request()->isMethod('GET') && !request()->routeIs('perusahaan.booking.cancel') && !request()->routeIs('perusahaan.consultants.start-chat')) {
                abort_unless($employee->perusahaan?->is_active && $employee->consent_at, 403, 'Program/consent belum aktif. Hubungi HR.');
            }
            abort_unless($employee, 403, 'Akun belum terhubung ke data karyawan.');
            return $employee;
        }

        // QA1-MED: preview lintas tenant hanya untuk super admin — jangan
        // fallback ke branch ini untuk role non-employee lain (hr_admin,
        // consultant, dst) kalau routing middleware berubah di masa depan.
        abort_unless(optional($user->role)->name === 'admin', 403, 'Akses ditolak.');
        abort_unless(request()->isMethod('GET'), 403, 'Preview read-only.');
        // Super admin: preview experience karyawan.
        $employee = PerusahaanEmployee::find(request('employee_id'))
            ?: PerusahaanEmployee::whereHas('user')
                ->where('perusahaan_id', \App\Support\PerusahaanContext::current()?->id)
                ->first();
        abort_unless($employee, 403, 'Belum ada karyawan dengan akun untuk preview.');

        // Audit: admin membaca data karyawan lain.
        // ponytail: path + id saja, bukan fullUrl — query string bisa bawa param sensitif.
        \App\Models\AdminAction::record($user->id, 'employee.preview', $employee, [
            'url' => request()->path(),
            'employee_id' => $employee->id,
        ]);

        return $employee;
    }

    /** Resolver di atas, dipakai controller assessment baru (W4) — tidak duplikasi logika. */
    public function resolveEmployeePublic(): PerusahaanEmployee
    {
        return $this->resolveEmployee();
    }

    public function index()
    {
        $employee = $this->resolveEmployee();
        $perusahaan = $employee->perusahaan;
        $monthStart = now()->startOfMonth();

        $usedMonth = $employee->sessions()->where('status', 'done')
            ->where('completed_at', '>=', $monthStart)->count();
        // D.3: sisa kuota = kuota - monthQuotaUsed (termasuk request pending review).
        $scheduledMonth = max(0, \App\Services\BookingService::monthQuotaUsed($employee) - $usedMonth);
        $quota = $perusahaan->sessions_per_employee_month;
        // W2: quota via package/ledger (fallback legacy flat tenant quota).
        $packageLabel = \App\Services\QuotaService::packageLabel($employee);
        $quotaLeft = \App\Services\QuotaService::balanceFor($employee);
        $history = $employee->sessions()->with('consultant.user')->orderByDesc('id')->limit(10)->get();

        // W6: "Sesi Saya" — upcoming scheduled milik employee ini saja (row scoping by
        // employee_id; query sudah lewat relasi sessions() milik PerusahaanEmployee).
        $mySessions = $employee->sessions()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->with('room', 'consultant.user')
            ->orderBy('scheduled_at')
            ->get()
            ->filter(fn ($s) => $s->scheduled_at->isFuture() || ($s->ended_at && $s->ended_at->isFuture()))
            ->values();

        $latest = $employee->assessments()->where('instrument_code', 'who5')->latest('taken_at')->first();
        $trend = $employee->assessments()->where('instrument_code', 'who5')->orderBy('taken_at')->limit(6)->get()
            ->map(fn ($a) => ['label' => $a->taken_at->translatedFormat('d M'), 'score' => $a->score]);

        // F1.3: pending slot proposals dari konsultan, menunggu pilihan karyawan.
        $pendingProposals = \App\Models\SessionSlotProposal::where('status', 'pending')
            ->whereHas('session', fn ($q) => $q->where('employee_id', $employee->id)
                ->where('status', 'scheduled')->whereNull('scheduled_at'))
            ->with('session.consultant.user')
            ->latest('proposed_at')
            ->get();

        // T5.3: permintaan yang masih menunggu review HR / konfirmasi konsultan.
        $pendingRequest = $employee->sessions()->with('consultant.user')
            ->where('status', 'scheduled')->whereNull('scheduled_at')
            ->latest('id')->first();

        // Saran AI per sesi (karyawan lihat miliknya sendiri saja).
        $sessionIds = $history->pluck('id');
        $advices = \App\Models\SessionSummary::whereIn('perusahaan_session_id', $sessionIds)
            ->where('status', 'completed')
            ->get(['perusahaan_session_id', 'client_advice'])
            ->keyBy('perusahaan_session_id');

        return view('perusahaan.employee', [
            'employee' => $employee,
            'perusahaan' => $perusahaan,
            'isPreview' => optional(auth()->user()->role)->name !== 'employee',
            'requestEnabled' => (bool) $perusahaan->consultant_request_enabled,
            'quota' => $quota,
            'quotaLeft' => $quotaLeft,
            'packageLabel' => $packageLabel,
            'usedMonth' => $usedMonth,
            'scheduledMonth' => $scheduledMonth,
            'history' => $history,
            'mySessions' => $mySessions,
            'assessment' => $latest,
            'assessmentTrend' => $trend,
            'pendingProposals' => $pendingProposals,
            'pendingRequest' => $pendingRequest,
            'advices' => $advices,
        ]);
    }

    /**
     * T5.3: batalkan permintaan konsultasi yang masih menunggu review/ACC.
     * Kuota kembali penuh — sesi belum terjadwal, tidak ada cutoff.
     */
    public function cancelPendingRequest(PerusahaanSession $session)
    {
        $employee = $this->resolveEmployee();
        abort_unless($session->employee_id === $employee->id, 403);
        abort_unless($session->status === 'scheduled' && $session->scheduled_at === null,
            422, 'Hanya permintaan yang belum terjadwal bisa dibatalkan di sini.');

        DB::transaction(function () use ($session) {
            \App\Support\SessionStateMachine::cancel($session, 'Dibatalkan oleh karyawan sebelum terjadwal');
            // FIX (review wb REV#2): release ledger reserve requestConsultation —
            // UI bilang "Kuota kamu kembali", ledger-nya sekarang juga (WB kontrak-level).
            if (\App\Services\WellbeingQuotaService::reservedLedgerFor($session)) {
                \App\Services\WellbeingQuotaService::release($session, 'employee cancel pending');
            } else {
                \App\Services\QuotaService::release($session, 'employee cancel pending');
            }
            // D.2: proposal slot yang masih pending tidak boleh menggantung.
            \App\Models\SessionSlotProposal::where('session_id', $session->id)
                ->where('status', 'pending')->update(['status' => 'rejected']);
        });

        return redirect()->route('perusahaan.employee')
            ->with('success', 'Permintaan konsultasi dibatalkan. Kuota kamu kembali.');
    }

    /** F1.3: karyawan pilih 1 slot dari proposal → sesi ter-book, proposal lain di-reject. */
    public function confirmSlot(Request $r, \App\Models\SessionSlotProposal $proposal)
    {
        $employee = $this->resolveEmployee();
        $session = $proposal->session;
        abort_unless($session && $session->employee_id === $employee->id, 403);
        abort_unless($proposal->status === 'pending'
            && $session->status === 'scheduled' && $session->scheduled_at === null,
            422, 'Slot sudah tidak berlaku.');

        $data = $r->validate([
            'slot_index' => ['required', 'integer', 'min:0', 'max:' . (count($proposal->slots) - 1)],
        ]);

        $slot = $proposal->slots[$data['slot_index']];

        DB::transaction(function () use ($proposal, $session, $slot) {
            // Re-fetch + lock: mencegah double-book race vs booking lain konsultan.
            $lockedSession = PerusahaanSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedSession->status === 'scheduled' && $lockedSession->scheduled_at === null,
                422, 'Slot sudah tidak berlaku.');

            $start = \Illuminate\Support\Carbon::parse($slot['start']);
            $end = \Illuminate\Support\Carbon::parse($slot['end']);

            // Slot di masa lalu tidak bisa di-book (stale proposal).
            abort_unless($start->isFuture(), 422, 'Slot sudah lewat. Minta konsultan mengirim opsi baru.');

            // Re-check overlap: konsultan mungkin sudah ke-book antara propose dan confirm.
            abort_if(\App\Support\SlotOverlap::exists($lockedSession->consultant_id, $start, $end),
                422, 'Slot bentrok dengan jadwal konsultan. Minta konsultan mengirim opsi baru.');

            \App\Support\SessionStateMachine::schedule($lockedSession, $start, $end);
            $proposal->update(['status' => 'accepted']);
            // Auto-reject proposal lain yang masih pending untuk sesi ini.
            \App\Models\SessionSlotProposal::where('session_id', $session->id)
                ->where('id', '!=', $proposal->id)
                ->where('status', 'pending')
                ->update(['status' => 'rejected']);
        });

        // ponytail: notifikasi ke konsultan belum ada — tambah saat notification channel diputuskan.

        return redirect()->route('perusahaan.employee')
            ->with('success', 'Jadwal dipilih. Sampai jumpa di sesi konsultasi!');
    }

    // B1 (D8): assessmentForm/assessmentStore legacy WHO-5 dihapus —
    // NULL instrument_code bug mati; route lama redirect 301 ke
    // perusahaan.assessment.instruments. WHO5_ITEMS dipertahankan
    // (dipakai Sekolah sampai B3). Engine baru: AssessmentController.

    public function bookingForm(Request $request)
    {
        $employee = $this->resolveEmployee();
        $perusahaan = $employee->perusahaan;

        $quota = \App\Services\QuotaService::packageLabel($employee)['monthly_quota'];
        $usedMonth = \App\Services\BookingService::monthQuotaUsed($employee);

        $pool = \App\Models\ConsultantPool::poolFor('perusahaan', $perusahaan->id);

        $q = $request->string('q')->trim()->toString();
        $category = $request->string('category')->trim()->toString();
        $consultantId = $request->integer('consultant_id') ?: null;

        $selectedConsultant = null;
        if ($consultantId) {
            $selectedConsultant = $pool->firstWhere('id', $consultantId);
            abort_unless($selectedConsultant, 404);
        }

        $filteredPool = $pool
            ->when($q, fn ($c) => $c->filter(fn ($item) => str_contains(strtolower($item['name']), strtolower($q))))
            ->when($category, fn ($c) => $c->filter(fn ($item) => str_contains($item['category'], $category)))
            ->values();

        $slots = \App\Services\BookingService::available('perusahaan', $perusahaan->id);
        $visibleSlots = $selectedConsultant
            ? $slots->where('consultant_id', $selectedConsultant['id'])->values()
            : $slots;

        return view('perusahaan.booking', [
            'employee' => $employee,
            'perusahaan' => $perusahaan,
            'isPreview' => optional(auth()->user()->role)->name !== 'employee',
            'types' => ['psikolog' => 'Psikolog', 'nutrisionis' => 'Nutrisionis', 'fisioterapis' => 'Fisioterapis', 'karir' => 'Konselor Karir'],
            'consultationTypes' => \App\Services\RoomService::TYPES,
            'consultants' => $filteredPool,
            'categories' => $pool->pluck('category')->filter()->unique()->sort()->values(),
            'q' => $q,
            'activeCategory' => $category,
            'selectedConsultant' => $selectedConsultant,
            'slots' => $visibleSlots,
            'quotaLeft' => max(0, $quota - $usedMonth),
        ]);
    }

    /** Batalkan sesi terjadwal milik sendiri; kuota mengikuti cutoff. */
    public function bookingCancel(PerusahaanSession $session)
    {
        $employee = $this->resolveEmployee();
        abort_unless($session->employee_id === $employee->id, 403);
        abort_unless($session->status === 'scheduled', 422, 'Hanya sesi terjadwal yang bisa dibatalkan.');
        // D.2: baris pending-review dibatalkan lewat jalur yang mengembalikan kuota.
        if ($session->scheduled_at === null) {
            return $this->cancelPendingRequest($session);
        }

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
        // D.2: reschedule tidak boleh bypass review HR untuk permintaan pending.
        abort_if($session->scheduled_at === null, 422, 'Permintaan masih menunggu review.');

        // ADR 001: max 3 jadwal total per sesi. Enforced di perusahaan flow;
        // ponytail: sekolah/BookingService belum — samakan saat reschedule mereka dimigrasi.
        $max = (int) config('booking.max_schedules_per_session', 3);
        abort_if($session->schedule_version >= $max, 422,
            "Maksimal {$max} jadwal per sesi tercapai. Batalkan sesi dan buat yang baru.");

        $r->validate(['schedule_id' => ['required', 'integer', 'exists:schedules,id']]);
        \App\Services\BookingService::reschedule('perusahaan', $employee, $session, $r->integer('schedule_id'));

        return redirect()->route('perusahaan.employee')->with('success', 'Jadwal sesi berhasil diubah.');
    }

    public function bookingStore(Request $r)
    {
        $employee = $this->resolveEmployee();
        $r->validate([
            'konsultan_type' => ['required', 'in:psikolog,nutrisionis,fisioterapis,karir'],
            // W5 (K9): tipe konsultasi — TERPISAH dari jenis profesional.
            'consultation_type' => ['nullable', 'in:online_meeting,chat,one_on_one'],
            'consultant_id' => ['nullable', 'integer'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ]);

        $r->validate(['schedule_id' => ['required', 'integer', 'exists:schedules,id']]);
        \App\Services\BookingService::book('perusahaan', $employee, $r->only('schedule_id', 'konsultan_type', 'consultation_type'));

        return redirect()->route('perusahaan.employee')
            ->with('success', 'Sesi berhasil dijadwalkan! Detail konsultan akan dikirim mendekati jadwal.');
    }

    /** Lihat konsultan program, link ke form booking. */
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

    /**
     * W3: detail konsultan (direktori employee-facing) — profil, kategorisasi,
     * bahasa/skills, tipe konsultasi yang didukung, slot 7 hari, rating agregat.
     * Tenant-scoped: konsultan harus di pool perusahaan sendiri.
     */
    public function consultantShow(Consultant $consultant)
    {
        $employee = $this->resolveEmployee();
        abort_unless($employee && $employee->consent_at, 403);
        $perusahaan = $employee->perusahaan;
        abort_unless($perusahaan, 404);

        // Tenant isolation (K15): konsultan harus ter-assign di pool perusahaan ini.
        abort_unless(ConsultantPool::where('perusahaan_id', $perusahaan->id)
            ->where('consultant_id', $consultant->id)
            ->where('status', 'active')->exists(), 403);

        $slots = \App\Services\AvailabilityService::slotsFor(
            $consultant, $perusahaan, now(), now()->addDays(7)
        );

        // W7: rating agregat dari consultation_reviews perusahaan ini (komentar
        // individu tidak pernah keluar — hanya avg + count; rating tidak dibagikan ke HR).
        $aggregate = PerusahaanSession::ratingAggregate($consultant->id, $perusahaan->id);

        $pool = ConsultantPool::where('perusahaan_id', $perusahaan->id)
            ->where('consultant_id', $consultant->id)->with('workHours')->first();

        return view('perusahaan.consultant-show', [
            'perusahaan' => $perusahaan,
            'employee' => $employee,
            'consultant' => $consultant->load(['user', 'categories', 'educations', 'certificates']),
            'skills' => $consultant->skills()->pluck('name'),
            'slots' => $slots,
            'ratingAvg' => $aggregate['avg'],
            'ratingCount' => $aggregate['count'],
            'workHours' => $pool?->workHours ?? collect(),
            'requestEnabled' => (bool) $perusahaan->consultant_request_enabled,
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
            ->where('status', 'scheduled')
            ->first();

        if ($existing) {
            return redirect()->route('perusahaan.chat', $existing);
        }

        // W2: tutup quota hole (recon-R3 startChat:232) — guard saldo server-side.
        // WB: routing — program wellbeing aktif → kuota kontrak bucket expert_chat.
        if (\App\Services\WellbeingQuotaService::activeProgram($perusahaan)) {
            $program = \App\Services\WellbeingQuotaService::activeProgram($perusahaan);
            if (\App\Services\WellbeingQuotaService::bucketBalance($program, \App\Services\WellbeingQuotaService::BUCKET_EXPERT_CHAT) < 1) {
                return redirect()->route('perusahaan.employee')
                    ->with('error', 'Kuota chat program wellbeing sudah habis. Hubungi HR untuk info program.');
            }
        } elseif (\App\Services\QuotaService::balanceFor($employee) < 1) {
            $label = \App\Services\QuotaService::packageLabel($employee);
            return redirect()->route('perusahaan.employee')
                ->with('error', "Kuota bulan ini habis (paket {$label['name']} {$label['monthly_quota']}x). Hubungi HR untuk penambahan kuota.");
        }

        return redirect()->route('perusahaan.booking')
            ->with('error', 'Pilih jadwal konsultasi dulu sebelum chat.');
    }

    /** Minta konsultasi tanpa pilih slot: masuk antrean review HR (F1.1), bukan auto-assign. */
    public function requestConsultation(Request $r)
    {
        $employee = $this->resolveEmployee();
        // K8: guard server-side — flag per perusahaan, default OFF.
        \App\Support\Guards::ensureRequestEnabled($employee->perusahaan);
        $validated = $r->validate([
            'konsultan_type' => ['required', 'in:psikolog,nutrisionis,fisioterapis,karir'],
            'topic_category' => ['nullable', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:2000'],
            'mode' => ['required', 'in:online,offline'],
        ]);

        $perusahaan = $employee->perusahaan;
        abort_if($employee->sessions()->where('status', 'scheduled')->whereNull('scheduled_at')->exists(),
            422, 'Masih ada permintaan menunggu review.');

        // W2: guard kuota server-side via QuotaService (entrypoint tunggal).
        // WB: routing — program wellbeing aktif → kuota kontrak bucket expert_chat
        // (requestConsultation = sesi chat; 1-on-1 lewat booking).
        if (\App\Services\WellbeingQuotaService::activeProgram($perusahaan)) {
            $program = \App\Services\WellbeingQuotaService::activeProgram($perusahaan);
            if (\App\Services\WellbeingQuotaService::bucketBalance($program, \App\Services\WellbeingQuotaService::BUCKET_EXPERT_CHAT) < 1) {
                abort(422, 'Kuota chat program wellbeing sudah habis. Hubungi HR untuk info program.');
            }
        } elseif (\App\Services\QuotaService::balanceFor($employee) < 1) {
            $label = \App\Services\QuotaService::packageLabel($employee);
            abort(422, "Kuota bulan ini habis (paket {$label['name']} {$label['monthly_quota']}x). Hubungi HR untuk penambahan kuota.");
        }

        // Gate review HR: 'scheduled' + scheduled_at NULL + consultant_id NULL
        // (nilai 'pending' tidak lolos CHECK constraint status — lihat migrasi 2026_10_21).
        // HR approve → consultant di-assign; HR reject → 'cancelled' + alasan.
        // W2: reserve kuota dalam satu transaction dengan insert sesi.
        $session = \Illuminate\Support\Facades\DB::transaction(function () use ($perusahaan, $employee, $r, $validated) {
            $session = PerusahaanSession::create([
                'perusahaan_id' => $perusahaan->id,
                'employee_id' => $employee->id,
                'departement_id' => $employee->departement_id,
                'consultant_id' => null,
                'konsultan_type' => $r->konsultan_type,
                'topic_category' => $r->topic_category,
                'status' => 'scheduled',
                'scheduled_at' => null,
                'request_notes' => $validated['body'],
                'mode' => $validated['mode'],
            ]);
            // WB: routing reserve — program aktif → bucket expert_chat (sesi chat);
            // else QuotaService bulanan legacy.
            if (\App\Services\WellbeingQuotaService::activeProgram($perusahaan)) {
                \App\Services\WellbeingQuotaService::reserve($employee, $session,
                    \App\Services\WellbeingQuotaService::BUCKET_EXPERT_CHAT);
            } else {
                \App\Services\QuotaService::reserve($employee, $session);
            }
            return $session;
        });

        return redirect()->route('perusahaan.employee')
            ->with('success', 'Permintaan terkirim. Menunggu review HR — kamu dapat kabar setelah disetujui.');
    }
}
