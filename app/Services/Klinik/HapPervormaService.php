<?php

namespace App\Services\Klinik;

use App\Models\ConsultationSession;
use App\Models\Klinik\HapPervorma;
use App\Models\Klinik\Klinik;
use Carbon\Carbon;

/**
 * HPA 3-stage pervorma engine (Build-NEW #7, M6, Spec §3; PDF Doc 3).
 * Trigger: sesi completed-AND-verified per klinik crossing 5/25/40. Window
 * 90 hari berjalan sejak MoU klinik — Stage 1 hanya dari sesi dalam window;
 * Stage 2/3 tidak hangus (sesi setelah 90 hari tetap dihitung).
 * Stage1 = flat; Stage2 = 1/3 × (Rate − Stage1); Stage3 = 2/3 sisa.
 * Idempotent (unique klinik_id+stage).
 */
class HapPervormaService
{
    public static function evaluate(ConsultationSession $session): array
    {
        $klinik = $session->klinik;
        if (!$klinik) {
            return [];
        }

        $cfg = config('klinik.hap');
        $counts = self::completedCounts($klinik);

        $rate = $klinik->total_rate ?: $cfg['total_rate'];
        $stages = [
            1 => ['trigger' => $cfg['stage1_trigger'], 'amount' => $cfg['stage1_flat']],
            2 => ['trigger' => $cfg['stage2_trigger'], 'amount' => (int) round(($rate - $cfg['stage1_flat']) / 3)],
            3 => ['trigger' => $cfg['stage3_trigger'], 'amount' => $rate - $cfg['stage1_flat'] - (int) round(($rate - $cfg['stage1_flat']) / 3)],
        ];

        // Tag sesi ini dengan stage yang ia bantu capai (brief: "tandai karena
        // dia dapat cost dari sana"). Sesi ke-N dengan N <= trigger stage X →
        // tagged stage X. Sesi di atas trigger stage-3 → tidak di-tag (tidak
        // kontribusi ke payout manapun).
        // Hitung ordinal sesi ini, bukan total klinik saat ini. Dengan begitu
        // re-evaluate/backfill sesi lama tidak salah menandai semuanya sebagai
        // stage terakhir (atau null setelah sesi ke-40).
        $sessionOrdinal = ConsultationSession::where('klinik_id', $klinik->id)
            ->whereNotNull('completion_verified_at')
            ->where('completion_verified_at', '>=', $counts['windowStart'])
            ->where('payment_status', '!=', ConsultationSession::PAYMENT_STATUS_REFUNDED)
            ->where(function ($query) use ($session) {
                $query->where('completion_verified_at', '<', $session->completion_verified_at)
                    ->orWhere(function ($sameTime) use ($session) {
                        $sameTime->where('completion_verified_at', $session->completion_verified_at)
                            ->where('id', '<=', $session->id);
                    });
            })
            ->count();

        $session->hap_pervorma_stage = self::stageForOrdinal($sessionOrdinal, $stages);
        $session->save();

        $created = [];
        foreach ($stages as $stage => $def) {
            $count = $stage === 1 ? $counts['inWindow'] : $counts['sinceMou'];
            if ($count < $def['trigger']) {
                continue;
            }
            // firstOrCreate → idempotent, satu stage cair sekali per klinik.
            $pervorma = HapPervorma::firstOrCreate(
                ['klinik_id' => $klinik->id, 'stage' => $stage],
                [
                    'associate_id' => $klinik->associate_id,
                    'amount' => $def['amount'],
                    'status' => 'due',
                    'due_at' => Carbon::now()->addWeekdays(7), // SLA payout 7 hari kerja
                ]
            );
            if ($pervorma->wasRecentlyCreated) {
                $created[] = $pervorma;
            }
        }

        return $created;
    }

    /**
     * Hitungan sesi completed-verified per klinik — SATU definisi untuk payout
     * engine, dashboard HAP, dan progress dashboard klinik (sebelumnya tiga
     * salinan yang bisa saling berbeda setelah edit).
     *
     * Refund dikecualikan (completion_verified_at tidak direset saat refund),
     * sesi pra-MoU tidak dihitung, fallback window = created_at klinik.
     */
    public static function completedCounts(Klinik $klinik): array
    {
        $cfg = config('klinik.hap');
        $mouAt = $klinik->mou_at ? Carbon::parse($klinik->mou_at) : null;
        // Data lama/demo bisa belum punya mou_at. created_at klinik stabil;
        // created_at sesi yang dievaluasi bikin hitungan reset tiap sesi baru.
        $windowStart = $mouAt ?? $klinik->created_at ?? Carbon::now();

        $base = ConsultationSession::where('klinik_id', $klinik->id)
            ->whereNotNull('completion_verified_at')
            ->where('completion_verified_at', '>=', $windowStart)
            ->where('payment_status', '!=', ConsultationSession::PAYMENT_STATUS_REFUNDED);

        return [
            'windowStart' => $windowStart,
            // Stage 2/3: semua sesi sejak MoU, tidak hangus.
            'sinceMou' => (clone $base)->count(),
            // Stage 1: hanya sesi dalam 90 hari sejak MoU (anti-fraud activation).
            'inWindow' => $mouAt
                ? (clone $base)->where('completion_verified_at', '<=', $mouAt->copy()->addDays($cfg['window_days']))->count()
                : (clone $base)->count(),
        ];
    }

    /**
     * Stage mana yang dikontribusikan sesi ke-ordinal N. Sesi 1-5 → stage 1,
     * 6-25 → stage 2, 26-40 → stage 3, >40 → null.
     */
    protected static function stageForOrdinal(int $ordinal, array $stages): ?int
    {
        foreach ([1, 2, 3] as $stage) {
            if ($ordinal <= $stages[$stage]['trigger']) {
                return $stage;
            }
        }
        return null;
    }
}
