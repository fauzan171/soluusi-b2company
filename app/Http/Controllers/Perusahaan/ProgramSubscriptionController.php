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

        // T2 helper: saldo bucket kontrak-level (snapshot - outflow ledger).
        $balance = fn (string $bucket, int $snapshot) => [
            'used' => max(0, $snapshot - \App\Services\WellbeingQuotaService::bucketBalance($program, $bucket)),
            'total' => $snapshot,
        ];

        $webinarDone = $program->quotaLedgers()
            ->where('bucket', 'webinar')
            ->whereIn('event', ['used', 'forfeited'])
            ->count();

        $daysLeft = max(0, (int) today()->diffInDays($program->ends_at, false));

        $chat = $balance('expert_chat', (int) $program->expert_chat_quota);
        $deep = $balance('deep_support', (int) $program->deep_support_quota);

        $buckets = [
            [
                'label' => 'Chat Expert', 'icon' => 'chat',
                'used' => $chat['used'], 'total' => $chat['total'], 'unit' => 'sesi',
            ],
            [
                'label' => 'Deep Support', 'icon' => 'hand-heart',
                'used' => $deep['used'], 'total' => $deep['total'], 'unit' => 'sesi',
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
