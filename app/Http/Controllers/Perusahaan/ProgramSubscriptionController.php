<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Models\CompanyWellbeingProgram;
use App\Support\PerusahaanContext;

/**
 * wb-T5: HR "Program & Subscription" — read-only snapshot kontrak Wellbeing
 * aktif + saldo bucket kuota (D1 kontrak-level, tidak reset bulanan).
 * Aggregate-only: tidak ada data individual karyawan di halaman ini.
 */
class ProgramSubscriptionController extends Controller
{
    public function index()
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan && $perusahaan->is_active, 403);

        // 1 aktif per perusahaan (guard app-level K8); window = umur kontrak.
        $program = $perusahaan->programs()
            ->where('status', 'active')
            ->whereDate('starts_at', '<=', today())
            ->whereDate('ends_at', '>=', today())
            ->with('wellbeingPackage')
            ->latest('id')
            ->first();

        if (! $program) {
            return view('perusahaan.program-subscription', [
                'perusahaan' => $perusahaan,
                'program' => null,
            ]);
        }

        // ponytail: saldo inline (snapshot - distinct sesi reserved/used/forfeited
        // + released/refunded) — T2 WellbeingQuotaService milik worktree lain,
        // jangan import. Kalau helper T2 sudah merge, ganti dengan
        // WellbeingQuotaService::bucketBalance(). Rows tanpa session_id
        // (mis. webinar) dihitung per-row.
        $spent = fn (string $bucket) => (int) \App\Models\WellbeingQuotaLedger::where('program_id', $program->id)
            ->where('bucket', $bucket)
            ->whereIn('event', ['reserved', 'used', 'forfeited'])
            ->whereNotNull('perusahaan_session_id')
            ->distinct('perusahaan_session_id')
            ->count('perusahaan_session_id')
            + (int) \App\Models\WellbeingQuotaLedger::where('program_id', $program->id)
                ->where('bucket', $bucket)
                ->whereNull('perusahaan_session_id')
                ->whereIn('event', ['reserved', 'used', 'forfeited'])
                ->count();

        $webinarDone = $program->quotaLedgers()
            ->where('bucket', 'webinar')
            ->whereIn('event', ['used', 'forfeited'])
            ->count();

        $daysLeft = max(0, (int) today()->diffInDays($program->ends_at, false));

        $buckets = [
            [
                'label' => 'Chat Expert', 'icon' => 'chat',
                'used' => $spent('expert_chat'), 'total' => (int) $program->expert_chat_quota, 'unit' => 'sesi',
            ],
            [
                'label' => 'Deep Support', 'icon' => 'hand-heart',
                'used' => $spent('deep_support'), 'total' => (int) $program->deep_support_quota, 'unit' => 'sesi',
            ],
            [
                'label' => 'Webinar', 'icon' => 'calendar',
                'used' => $webinarDone, 'total' => (int) $program->webinar_quota, 'unit' => '',
            ],
        ];

        return view('perusahaan.program-subscription', [
            'perusahaan' => $perusahaan,
            'program' => $program,
            'buckets' => $buckets,
            'daysLeft' => $daysLeft,
            'nearExpiry' => $daysLeft <= 30,
        ]);
    }
}
