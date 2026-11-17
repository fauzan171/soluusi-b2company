<?php

namespace App\Services;

use App\Contracts\AIService;
use App\Services\AI\TokengatewayAIService;
use App\Support\AggregatePrivacy;
use Illuminate\Support\Facades\Log;

/**
 * AI Insight untuk HR/BK — port dari TVS kiosk `functions/_lib/ai-insights.ts`.
 *
 * Seam (T6.1):
 * - Per-tenant flag `$tenant->ai_enabled` diperiksa PERTAMA; false → status
 *   `not_configured` (dashboard fallback ke InsightEngine rule-based) dan
 *   tidak ada panggilan HTTP.
 * - Provider di-resolve dari `config('ai.provider')` via `App\Contracts\AIService`.
 *   `null` → NullAIService (juga short-circuit ke `not_configured`).
 * - Implementasi HTTP Tokengateway tinggal di `App\Services\AI\TokengatewayAIService`.
 *
 * Cache per (vertical, tenant, input_hash) di tabel ai_insights; perubahan
 * data → stale → generate ulang.
 */
class AiInsightService
{
    /** Model dibaca dari config (env AI_INSIGHT_MODEL). Const dipertahankan sebagai fallback. */
    public const MODEL = TokengatewayAIService::MODEL;

    protected static function model(): string
    {
        return TokengatewayAIService::model();
    }

    /** True jika tenant punya flag AI on DAN provider aktif bukan 'null'. */
    protected static function seamActive($tenant): bool
    {
        if (empty($tenant->ai_enabled)) {
            return false;
        }
        $providerKey = (string) config('ai.provider', 'null');
        if ($providerKey === '' || $providerKey === 'null') {
            return false;
        }
        $map = (array) config('ai.providers', []);
        return isset($map[$providerKey]);
    }

    /** Cache rows: vertical, tenant_id, input_hash, scope(fixed 'tenant'), status, result_json, model, generated_at. */
    public static function get($tenant, string $vertical, string $contextJson, bool $generate = false): array
    {
        $hash = hash('sha256', $contextJson);

        // Feature flag — cek duluan sebelum sentuh cache/HTTP.
        if (!self::seamActive($tenant)) {
            return ['status' => 'not_configured', 'stale' => true, 'insight' => null,
                'generated_at' => null, 'model' => null,
                'error' => 'Insight AI belum aktif untuk tenant ini.'];
        }

        $row = \Illuminate\Support\Facades\DB::table('ai_insights')
            ->where('vertical', $vertical)->where('tenant_id', $tenant->id)
            ->orderByDesc('id')->first();

        $stale = !$row || $row->input_hash !== $hash;
        $status = $stale ? 'stale' : ($row->status === 'running'
            ? (time() - strtotime($row->started_at) > 180 ? 'failed' : 'running')
            : ($row->status === 'completed' ? 'completed' : 'failed'));

        if (!$generate) {
            return [
                'status' => $status,
                'stale' => $stale,
                'insight' => (!$stale && $row?->status === 'completed') ? json_decode($row->result_json, true) : null,
                'generated_at' => $stale ? null : $row?->generated_at,
                'model' => $row?->model,
                'error' => null,
            ];
        }

        // Fresh cache hit — tanpa biaya.
        if (!$stale && $row?->status === 'completed' && $row->result_json) {
            return ['status' => 'completed', 'stale' => false, 'insight' => json_decode($row->result_json, true),
                'generated_at' => $row->generated_at, 'model' => $row->model, 'error' => null, 'cached' => true];
        }

        $key = trim((string) config('services.ai_insight.key'));
        if ($key === '') {
            return ['status' => 'not_configured', 'stale' => $stale, 'insight' => null,
                'generated_at' => null, 'model' => null, 'error' => 'Insight AI belum dikonfigurasi di server.'];
        }

        // Lease: satu generate aktif per tenant, expired lease diambil alih.
        $active = \Illuminate\Support\Facades\DB::table('ai_insights')
            ->where('vertical', $vertical)->where('tenant_id', $tenant->id)
            ->where('status', 'running')->where('started_at', '>', now()->subMinutes(3))
            ->count();
        if ($active > 0 && $row?->status === 'running') {
            return ['status' => 'running', 'stale' => $stale, 'insight' => null,
                'generated_at' => null, 'model' => null, 'error' => null];
        }

        \Illuminate\Support\Facades\DB::table('ai_insights')->updateOrInsert(
            ['vertical' => $vertical, 'tenant_id' => $tenant->id],
            [
                'input_hash' => $hash, 'scope' => 'tenant', 'status' => 'running',
                'result_json' => null, 'error_code' => null,
                'model' => self::model(), 'started_at' => now(),
                'generated_at' => null, 'created_at' => now(), 'updated_at' => now(),
            ]
        );

        try {
            $insight = self::generate($contextJson);
            \Illuminate\Support\Facades\DB::table('ai_insights')
                ->where('vertical', $vertical)->where('tenant_id', $tenant->id)
                ->update(['status' => 'completed', 'result_json' => json_encode($insight, JSON_UNESCAPED_UNICODE),
                    'model' => self::model(), 'generated_at' => now(), 'updated_at' => now(), 'error_code' => null]);
            return ['status' => 'completed', 'stale' => false, 'insight' => $insight,
                'generated_at' => now()->toDateTimeString(), 'model' => self::model(), 'error' => null];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::table('ai_insights')
                ->where('vertical', $vertical)->where('tenant_id', $tenant->id)
                ->update(['status' => 'failed', 'error_code' => substr($e->getMessage(), 0, 200), 'updated_at' => now()]);
            Log::error('AI insight gagal: ' . $e->getMessage());
            return ['status' => 'failed', 'stale' => $stale, 'insight' => null,
                'generated_at' => null, 'model' => self::model(),
                'error' => 'Analisis AI belum berhasil. Coba lagi sebentar.'];
        }
    }

    protected static function generate(string $contextJson): array
    {
        /** @var AIService $provider */
        $provider = app(AIService::class);
        return $provider->generateInsight(['context_json' => $contextJson]);
    }

    /**
     * Bangun konteks agregat dari data tenant — TANPA identitas individu.
     * Catatan konsultan di-redaksi (nama/email/telepon/pola nama) sebelum masuk.
     */
    public static function buildContext($tenant, string $vertical): string
    {
        $school = $vertical === 'sekolah';
        $person = $school ? 'student_id' : 'employee_id';
        $quotaPer = $school ? $tenant->sessions_per_student_month : $tenant->sessions_per_employee_month;
        [$month, $start, $end] = AggregatePrivacy::month();

        $sessions = $tenant->sessions()->where('scheduled_at', '>=', $start)->where('scheduled_at', '<', $end)->get();
        $done = $sessions->where('status', 'done');
        $members = ($school ? $tenant->students() : $tenant->employees())->count();

        $topics = $done->whereNotNull('topic_category')->groupBy('topic_category')->map->count();
        $types = $done->groupBy('konsultan_type')->map->count();
        $noShow = $sessions->where('status', 'no_show')->count();

        $assessmentModel = $school ? \App\Models\Sekolah\SekolahAssessment::class : \App\Models\Perusahaan\PerusahaanAssessment::class;
        $assessed = $assessmentModel::where($vertical . '_id', $tenant->id)
            ->where('taken_at', '>=', now()->copy()->subDays(90))->when($school, fn ($q) => $q->studentRoster())->get()
            ->groupBy($person)->map(fn ($rows) => $rows->sortByDesc('taken_at')->first());

        $risk = [
            'sehat_75_plus' => $assessed->where('score', '>=', 75)->count(),
            'perlu_perhatian_50_74' => $assessed->filter(fn ($a) => $a->score >= 50 && $a->score < 75)->count(),
            'berisiko_dibawah_50' => $assessed->where('score', '<', 50)->count(),
        ];

        // Catatan klinis konsultan (confidential, aggregate audience tidak boleh
        // lihat di UI — masuk konteks AI hanya setelah redaksi PII).
        $notes = $done->whereNotNull('note')->take(12)->map(function ($s) {
            return self::redact(mb_substr((string) $s->note, 0, 300));
        })->values()->all();

        $context = [
            'tipe_organisasi' => $school ? 'sekolah' : 'perusahaan',
            'periode' => $month,
            'jumlah_' . ($school ? 'siswa' : 'karyawan') . '_terdaftar' => $members,
            'kuota_sesi_per_orang_per_bulan' => $quotaPer,
            'sesi_bulan_ini' => [
                'selesai' => $done->count(),
                'terjadwal' => $sessions->where('status', 'scheduled')->count(),
                'tidak_hadir' => $noShow,
            ],
            'topik_anonim' => $topics->all(),
            'jenis_layanan' => $types->all(),
            'asesmen_90_hari' => [
                'total_orang_diases' => $assessed->count(),
                'distribusi' => $risk,
                'skor_rendah' => 'WHO-5: skor kecil = kondisi kurang sejahtera, perlu perhatian',
            ],
            'catatan_konsultan_terredaksi' => $notes,
        ];

        return json_encode($context, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Redaksi PII — paritas sourceRedactor TVS.
     * ponytail: deteksi nama HANYA ber-anchor honorific (Pak|Bu|Ibu|...).
     * Regex Title-Case polos dihapus — over-redact frasa legit ("Paket Klinik
     * Dasar", "Transfer Bank BCA") dan merusak konteks AI (QA blocker).
     * Konsekuensi: nama orang TANPA honorific bisa lolos ke konteks AI.
     * Upgrade path: NER model atau tokenisasi nama dari master data.
     */
    public static function redact(string $text): string
    {
        $text = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[email dihapus]', $text) ?? $text;
        $text = preg_replace('/(?:\+62|08)[\d\s-]{7,}\d/', '[kontak dihapus]', $text) ?? $text;
        // Panggilan umum diikuti nama (Pak Budi, Bu Siti, Saudara Andi, dst).
        $text = preg_replace('/\b(?:Pak|Bu|Ibu|Bapak|Mas|Mbak|Bang|Kak|Sdr|Saudara|Tn|Ny)\.?\s+[A-Z][a-zA-Z.]+(?:\s+[A-Z][a-zA-Z.]+){0,2}/u', '[nama dihapus]', $text) ?? $text;
        return $text;
    }
}
