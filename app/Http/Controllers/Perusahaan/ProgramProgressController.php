<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Models\Perusahaan\PerusahaanAssessment;
use App\Support\PerusahaanContext;

/**
 * wv4: HR "Progres Program" — kuota terpakai %, completion asesmen %,
 * webinar, dan pre/post (feature C, Impact only — bukan klaim kausal).
 */
class ProgramProgressController extends Controller
{
    public function index()
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan && $perusahaan->is_active, 403);

        $program = \App\Services\WellbeingQuotaService::activeProgram($perusahaan);
        if (! $program) {
            return view('perusahaan.program-progress', [
                'perusahaan' => $perusahaan,
                'program' => null,
            ]);
        }

        $balance = fn (string $bucket, int $snapshot) => [
            'used' => max(0, $snapshot - \App\Services\WellbeingQuotaService::bucketBalance($program, $bucket)),
            'total' => $snapshot,
        ];

        $chat = $balance('expert_chat', (int) $program->expert_chat_quota);
        $deep = $balance('deep_support', (int) $program->deep_support_quota);
        $webinarDone = $program->quotaLedgers()
            ->where('bucket', 'webinar')
            ->whereIn('event', ['used', 'forfeited'])
            ->count();

        // Assessment completion: peserta unik vs max participants (agregat).
        $assessedPeople = PerusahaanAssessment::where('perusahaan_id', $perusahaan->id)
            ->whereBetween('taken_at', [$program->starts_at, $program->ends_at])
            ->distinct('employee_id')
            ->count('employee_id');
        $maxParticipants = (int) $program->assessment_max_participants;

        // feature C: pre/post — asesmen pertama vs terakhir per orang dalam
        // window program, rata-rata selisih skor. Bukan klaim kausal.
        $prePost = null;
        if ($program->wellbeingPackage?->code === 'impact') {
            $prePost = $this->prePost($perusahaan->id, $program);
        }

        return view('perusahaan.program-progress', [
            'perusahaan' => $perusahaan,
            'program' => $program,
            'progressRows' => [
                ['label' => 'Chat Expert', 'used' => $chat['used'], 'total' => $chat['total'], 'unit' => 'sesi'],
                ['label' => 'Deep Support', 'used' => $deep['used'], 'total' => $deep['total'], 'unit' => 'sesi'],
                ['label' => 'Webinar', 'used' => $webinarDone, 'total' => (int) $program->webinar_quota, 'unit' => 'webinar'],
                ['label' => 'Asesmen', 'used' => $assessedPeople, 'total' => $maxParticipants, 'unit' => 'peserta'],
            ],
            'prePost' => $prePost,
        ]);
    }

    private function prePost(int $perusahaanId, $program): ?array
    {
        $rows = PerusahaanAssessment::where('perusahaan_id', $perusahaanId)
            ->whereBetween('taken_at', [$program->starts_at, $program->ends_at])
            ->whereNotNull('employee_id')
            ->orderBy('taken_at')
            ->get()
            ->groupBy('employee_id')
            ->filter(fn ($r) => $r->count() >= 2)
            ->map(fn ($r) => round($r->last()->score - $r->first()->score, 1));

        // R10: pre/post agregat min 10 orang, else suppressed.
        if ($rows->count() < 10) {
            return ['n' => $rows->count(), 'avgDelta' => null, 'suppressed' => true];
        }

        return ['n' => $rows->count(), 'avgDelta' => round($rows->avg(), 1), 'suppressed' => false];
    }
}
