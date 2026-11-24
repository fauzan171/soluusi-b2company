<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\AssessmentAssignment;
use App\Models\CompanyWellbeingProgram;
use App\Models\Perusahaan\Perusahaan;
use App\Models\WellbeingPackage;
use App\Models\WellbeingPricingBand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * wb-T4 — Assign Program wizard 3-step superadmin (ux §3c/§3d, bizrules R2/R4/R6/R9/§3/§4).
 * Step = form POST step-by-step (input old()) bukan session state machine; step 3
 * POST final create: snapshot kuota+harga+band+domains dalam 1 DB::transaction +
 * AdminAction::record. Guard 1 program aktif per perusahaan (app-level, K8).
 */
class WellbeingProgramController extends Controller
{
    public function create(Request $request)
    {
        $packages = WellbeingPackage::where('is_active', true)->orderBy('id')->get();
        $perusahaans = Perusahaan::where('is_active', true)->orderBy('brand')->get(['id', 'brand', 'name', 'employee_count']);

        $step = (int) $request->query('step', 1);
        if ($step < 1 || $step > 3) {
            $step = 1;
        }

        // Step 2/3 butuh konteks perusahaan + paket terpilih (dari query old-form).
        $perusahaan = $request->filled('perusahaan_id')
            ? Perusahaan::find($request->query('perusahaan_id'))
            : null;
        $package = $request->filled('package_code')
            ? $packages->firstWhere('code', $request->query('package_code'))
            : null;
        if ($step > 1 && (!$perusahaan || !$package)) {
            $step = 1; // konteks hilang → mulai dari awal
        }

        $bands = collect();
        $selectedBand = null;
        $suggestedBandMin = null;
        if ($package && !$package->is_free) {
            $bands = WellbeingPricingBand::where('wellbeing_package_id', $package->id)
                ->orderBy('band_min')->get();
            $suggestedBandMin = $this->suggestedBandMin($bands, $perusahaan?->employee_count ?? 0);
            $selectedBand = $bands->firstWhere('band_min', (int) ($request->query('band_min', $suggestedBandMin ?? 0)));
        }

        return view('admin.wellbeing-programs.create', [
            'packages' => $packages,
            'perusahaans' => $perusahaans,
            'step' => $step,
            'perusahaan' => $perusahaan,
            'package' => $package,
            'bands' => $bands,
            'selectedBand' => $selectedBand,
            'suggestedBandMin' => $suggestedBandMin,
            'programs' => CompanyWellbeingProgram::with(['perusahaan', 'wellbeingPackage', 'pricingBand'])
                ->orderByDesc('id')->limit(20)->get(),
        ]);
    }

    public function store(Request $request)
    {
        // Fix REV#1: UI kirim string "12,15,19" — normalisasi ke array sebelum validasi.
        if (is_string($request->input('selected_employee_ids'))) {
            $ids = array_values(array_filter(
                explode(',', (string) $request->input('selected_employee_ids')),
                fn ($v) => $v !== '' && is_numeric(trim($v))
            ));
            $request->merge(['selected_employee_ids' => array_map('intval', $ids)]);
        }

        // B2/D3-D4: step instrumen wizard — core paket + extras MH + add-on.
        // required_if sesuai paket (B2 spec; direct POST tanpa field → 422, bukan default diam-diam).
        $instrumentRules = [
            'discovery_instrument' => ['required_if:package_code,discovery', 'string', 'in:who5,dass21'],
            'foundation_domain' => ['required_if:package_code,foundation', 'string', 'in:mh,nutrition'],
            'mh_extras' => ['nullable', 'array'],
            'mh_extras.*' => ['string', 'in:who5,pss10'],
            'addons' => ['nullable', 'array'],
            'addons.*.code' => ['required', 'string', 'distinct',
                Rule::in(['bigfive', 'disc', 'ergonomic', 'phq9', 'gad7', 'cbi'])],
            'addons.*.price' => ['required', 'numeric', 'min:0', 'max:999999999'],
        ];

        $data = $request->validate(array_merge([
            'perusahaan_id' => ['required', 'integer', 'exists:perusahaans,id'],
            'package_code' => ['required', 'string', Rule::in(['discovery', 'foundation', 'growth', 'impact'])],
            'band_min' => ['nullable', 'integer'],
            'deep_support_mode' => ['required', 'string', 'in:mhcu,one_on_one'],
            'starts_at' => ['required', 'date', 'after_or_equal:today'],
            'confirm' => ['required', 'accepted'],
            'selected_employee_ids' => ['nullable', 'array'],
            'selected_employee_ids.*' => ['integer'],
        ], $instrumentRules));

        // D3: pilihan instrumen wajib sesuai paket.
        if ($data['package_code'] === 'discovery') {
            $data['discovery_instrument'] = $data['discovery_instrument'] ?? 'who5';
        }
        if ($data['package_code'] === 'foundation' && !in_array($data['foundation_domain'] ?? null, ['mh', 'nutrition'], true)) {
            throw ValidationException::withMessages([
                'foundation_domain' => 'Pilih domain assessment (Mental Health atau Nutrition) untuk paket Foundation.',
            ]);
        }

        $perusahaan = Perusahaan::findOrFail($data['perusahaan_id']);
        $package = WellbeingPackage::where('code', $data['package_code'])->firstOrFail();

        // §4: guard 1 program aktif per perusahaan (app-level, K8).
        if ($perusahaan->programs()->where('status', 'active')->exists()) {
            throw ValidationException::withMessages([
                'perusahaan_id' => "Perusahaan {$perusahaan->brand} masih ada program aktif. Akhiri program berjalan sebelum assign kontrak baru.",
            ]);
        }

        $band = null;
        if (!$package->is_free) {
            $band = $this->resolveBand($package, (int) $data['band_min']);
            // §3: validasi employee_count masuk band (admin boleh override band, tapi
            // employee_count harus >= 50 dan tidak boleh di luar semua band).
            if ((int) $perusahaan->employee_count < 50) {
                throw ValidationException::withMessages([
                    'band_min' => "Jumlah karyawan terdaftar {$perusahaan->employee_count} belum memenuhi band minimum 50 (paket berbayar). Gunakan paket Discovery atau perbarui data karyawan.",
                ]);
            }
            if (!$band) {
                throw ValidationException::withMessages([
                    'band_min' => "Band tidak ditemukan untuk paket {$package->name}.",
                ]);
            }
            // di luar semua band (mis. 1200 karyawan) → error jelas (biz §3).
            $allBands = WellbeingPricingBand::where('wellbeing_package_id', $package->id)->get();
            $covered = $allBands->contains(fn ($b) => $perusahaan->employee_count >= $b->band_min && $perusahaan->employee_count <= $b->band_max);
            if (!$covered) {
                $top = $allBands->max('band_max');
                throw ValidationException::withMessages([
                    'band_min' => "Jumlah karyawan terdaftar {$perusahaan->employee_count} di luar semua band harga (maksimum {$top}). Hubungi Finance untuk kontrak custom.",
                ]);
            }
        } else {
            // R2: discovery/foundation mode default paket — form menonaktifkan pilihan,
            // nilai dikoreksi ke default katalog agar enum tetap valid.
            $data['deep_support_mode'] = $package->deep_support_mode ?: 'one_on_one';
        }

        // wv2 §6: expert-count gate — pool konsultan perusahaan harus >=
        // expert_chat_experts katalog (Foundation 1, Growth/Impact 2, Discovery 0).
        // Dijalankan SETELAH validasi band supaya error band tetap diprioritaskan.
        $requiredExperts = (int) $package->expert_chat_experts;
        if ($requiredExperts > 0) {
            $poolCount = \App\Models\ConsultantPool::where('perusahaan_id', $perusahaan->id)->count();
            if ($poolCount < $requiredExperts) {
                throw ValidationException::withMessages([
                    'perusahaan_id' => "Assign minimal {$requiredExperts} expert melalui Consultant Assignment sebelum aktifasi program {$package->name} (pool saat ini: {$poolCount}).",
                ]);
            }
        }

        // R4: discovery — max 8 karyawan terpilih, employee milik perusahaan.
        if ($package->code === 'discovery') {
            if (!empty($data['selected_employee_ids'])) {
                $valid = $perusahaan->employees()
                    ->whereIn('id', $data['selected_employee_ids'])->pluck('id');
                if ($valid->count() !== count($data['selected_employee_ids']) || count($data['selected_employee_ids']) > 8) {
                    throw ValidationException::withMessages([
                        'selected_employee_ids' => 'Karyawan terpilih maksimal 8 dan harus terdaftar di perusahaan ini.',
                    ]);
                }
            }
        }

        // Snapshot: deep_support_quota — band MHCU jika option A; default katalog jika B/discovery.
        $deepSupportQuota = (int) $package->deep_support_quota;
        if ($band && $data['deep_support_mode'] === 'mhcu' && $band->mhcu_quota !== null) {
            $deepSupportQuota = (int) $band->mhcu_quota;
        }

        $startsAt = \Illuminate\Support\Carbon::parse($data['starts_at']);

        // D3: snapshot domain per paket (bukan lagi domain katalog).
        $assessmentDomains = match ($data['package_code']) {
            'discovery' => ['mh'],
            'foundation' => [$data['foundation_domain']],
            default => ['mh', 'nutrition'], // growth/impact
        };

        $program = DB::transaction(function () use ($data, $perusahaan, $package, $band, $deepSupportQuota, $startsAt, $assessmentDomains) {
            $program = CompanyWellbeingProgram::create([
                'perusahaan_id' => $perusahaan->id,
                'wellbeing_package_id' => $package->id,
                'pricing_band_id' => $band?->id,
                'employee_band_size' => (int) $perusahaan->employee_count,
                'deep_support_mode' => $data['deep_support_mode'],
                'starts_at' => $startsAt->toDateString(),
                'ends_at' => $startsAt->copy()->addDays((int) $package->duration_days)->subDay()->toDateString(),
                'expert_chat_quota' => (int) $package->expert_chat_quota,
                'deep_support_quota' => $deepSupportQuota,
                'webinar_quota' => (int) $package->webinar_count,
                'assessment_count' => (int) $package->assessment_count,
                'assessment_max_participants' => $package->assessment_max_participants,
                'monthly_price' => $band?->monthly_price,
                'assessment_domains' => $assessmentDomains,
                'selected_employee_ids' => $data['selected_employee_ids'] ?? null,
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);

            AdminAction::record(auth()->id(), 'wellbeing_program.activated', $program, [
                'perusahaan' => $perusahaan->brand,
                'package' => $package->code,
                'band' => $band?->only(['band_min', 'band_max', 'monthly_price']),
                'deep_support_mode' => $data['deep_support_mode'],
                'starts_at' => $program->starts_at->toDateString(),
                'ends_at' => $program->ends_at->toDateString(),
            ]);

            // wv2 §3: Impact (quarterly_review) → 4 rows review kuartalan,
            // due_at = starts_at + 3/6/9/12 bulan (same transaction).
            if ($package->quarterly_review) {
                foreach (range(1, 4) as $periodNo) {
                    \App\Models\WellbeingProgramReview::create([
                        'program_id' => $program->id,
                        'period_no' => $periodNo,
                        'due_at' => $startsAt->copy()->addMonths(3 * $periodNo)->toDateString(),
                        'status' => \App\Models\WellbeingProgramReview::STATUS_UPCOMING,
                    ]);
                }
            }

            // B2 (D3/D4/D6): assignment instrumen dibuat bersama program
            // (transaksi sama) — core, extras MH, dan add-on.
            $this->createAssignments($program, $data);

            return $program;
        });

        return redirect()
            ->route('admin.wellbeing-programs.create')
            ->with('success', "Program {$package->name} untuk {$perusahaan->brand} diaktifkan ({$program->starts_at->format('d M Y')} – {$program->ends_at->format('d M Y')}).");
    }

    /** wv3: Program Order printable — snapshot kontrak (dokumen = baris program). */
    public function order(CompanyWellbeingProgram $program)
    {
        $program->load(['perusahaan', 'wellbeingPackage', 'pricingBand']);

        return view('admin.wellbeing-programs.order', ['program' => $program]);
    }

    /** R6: 30-day notice — program tetap aktif sampai tanggal efektif. */
    public function requestTermination(Request $request, CompanyWellbeingProgram $program)
    {
        $data = $request->validate([
            'effective_date' => [
                'required', 'date',
                'after_or_equal:' . now()->addDays(30)->toDateString(),
            ],
        ]);

        abort_if($program->status !== 'active', 422, 'Program sudah berakhir.');

        $program->update([
            'notice_requested_at' => now(),
            'terminated_at' => $data['effective_date'],
        ]);

        AdminAction::record(auth()->id(), 'wellbeing_program.termination_requested', $program, [
            'effective_date' => $data['effective_date'],
        ]);

        $efektif = \Illuminate\Support\Carbon::parse($data['effective_date'])->format('d M Y');

        return back()->with('success', "Terminasi program {$program->perusahaan->brand} diajukan, efektif {$efektif} (program tetap aktif sampai tanggal efektif).");
    }

    private function resolveBand(WellbeingPackage $package, int $bandMin): ?WellbeingPricingBand
    {
        return WellbeingPricingBand::where('wellbeing_package_id', $package->id)
            ->where('band_min', $bandMin)->first();
    }

    /** §3: band auto-suggest dari perusahaans.employee_count (override manual boleh). */
    private function suggestedBandMin($bands, int $employeeCount): ?int
    {
        foreach ($bands as $band) {
            if ($employeeCount >= $band->band_min && $employeeCount <= $band->band_max) {
                return $band->band_min;
            }
        }

        return null; // di luar semua band (mis. 1200) → step 2 tampil error jelas
    }

    /**
     * B2 (D3/D4/D6): buat assignment core (+ extras MH) + add-on untuk program
     * baru. Dipanggil DALAM transaksi pembuatan program. MCMI-3 sudah
     * diblokir validasi addons (never mass-assignable, D5).
     */
    private function createAssignments(CompanyWellbeingProgram $program, array $data): void
    {
        $packageCode = $data['package_code'];
        $hasMh = in_array('mh', $program->assessment_domains ?? [], true);

        // D3: core instruments per package.
        $core = match ($packageCode) {
            'discovery' => [$data['discovery_instrument'] ?? 'who5'],
            'foundation' => [$data['foundation_domain'] === 'nutrition' ? 'nutrition' : 'dass21'],
            default => ['dass21', 'nutrition'], // growth/impact
        };

        // D3: extras "included in MH domain" — gratis, hanya saat program MH.
        $extras = $hasMh ? array_intersect($data['mh_extras'] ?? [], ['who5', 'pss10']) : [];

        foreach (array_merge($core, $extras) as $code) {
            foreach ($this->windowsFor($packageCode, $program->starts_at, $program->ends_at) as [$round, $opens, $closes]) {
                $this->createAssignment($program, $code, AssessmentAssignment::SOURCE_CORE, null, $round, $opens, $closes);
            }
        }

        // D4: add-on berbayar — 1 round, seluruh periode program.
        foreach ($data['addons'] ?? [] as $addon) {
            $this->createAssignment(
                $program,
                $addon['code'],
                AssessmentAssignment::SOURCE_ADDON,
                $addon['price'],
                1,
                $program->starts_at->toDateString(),
                $program->ends_at->toDateString(),
            );
        }
    }

    private function createAssignment(
        CompanyWellbeingProgram $program,
        string $code,
        string $source,
        ?float $addonPrice,
        int $round,
        string $opens,
        string $closes,
    ): void {
        $assignment = AssessmentAssignment::create([
            'assignable_type' => $program->getMorphClass(),
            'assignable_id' => $program->id,
            'instrument_code' => $code,
            'audience' => AssessmentAssignment::AUDIENCE_EMPLOYEE,
            'source' => $source,
            'addon_price' => $addonPrice,
            'round' => $round,
            'opens_at' => $opens,
            'closes_at' => $closes,
            'status' => 'active',
            'assigned_by' => auth()->id(),
        ]);

        AdminAction::record(auth()->id(), 'assessment_assignment.created', $assignment, [
            'instrument_code' => $code,
            'round' => $round,
            'source' => $source, // no prices, no people (D5/PII rule)
        ]);
    }

    /**
     * D6: window per round. [round, opens, closes] per entry.
     *   discovery/foundation (core): 1 round = seluruh periode.
     *   growth: R1 = starts..+29d (baseline), R2 = ends-29d..ends.
     *   impact: Rn opens = starts + 3*(n-1) bulan, closes = +29 hari
     *           (aligned dengan 4 review kuartalan).
     */
    private function windowsFor(string $packageCode, $startsAt, $endsAt): array
    {
        if (in_array($packageCode, ['discovery', 'foundation'], true)) {
            return [[1, $startsAt->toDateString(), $endsAt->toDateString()]];
        }

        if ($packageCode === 'growth') {
            return [
                [1, $startsAt->toDateString(), $startsAt->copy()->addDays(29)->toDateString()],
                [2, $endsAt->copy()->subDays(29)->toDateString(), $endsAt->toDateString()],
            ];
        }

        // impact — 4 rounds kuartalan.
        $rounds = [];
        foreach (range(1, 4) as $n) {
            $opens = $startsAt->copy()->addMonths(3 * ($n - 1));
            $rounds[] = [$n, $opens->toDateString(), $opens->copy()->addDays(29)->toDateString()];
        }

        return $rounds;
    }
}
