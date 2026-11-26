<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Models\ConsultantPool;
use App\Support\PerusahaanContext;

/**
 * wv4: HR "Utilisasi Expert" — tabel per konsultan pool (sesi, rating,
 * kuota) — agregat saja, tanpa identitas karyawan.
 */
class ExpertUtilizationController extends Controller
{
    public function index()
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan && $perusahaan->is_active, 403);

        $sessions = $perusahaan->sessions()->with('consultant:id')->get();

        $program = \App\Services\WellbeingQuotaService::activeProgram($perusahaan);
        $chatTotal = $program ? (int) $program->expert_chat_quota : 0;
        $deepTotal = $program ? (int) $program->deep_support_quota : 0;
        $chatUsed = $sessions->where('konsultan_type', 'chat')->where('status', 'done')->count();
        $deepUsed = $sessions->whereIn('konsultan_type', ['one_on_one', 'online_meeting'])->where('status', 'done')->count();

        $rows = ConsultantPool::poolFor('perusahaan', $perusahaan->id)->map(function ($c) use ($sessions) {
            $mine = $sessions->where('consultant_id', $c['id']);
            $done = $mine->where('status', 'done');

            return [
                'name' => $c['name'],
                'category' => $c['category'],
                'type' => $c['type'],
                'sessions' => $done->count(),
                'scheduled' => $mine->where('status', 'scheduled')->count(),
                'avgRating' => $done->whereNotNull('rating')->isNotEmpty()
                    ? round($done->whereNotNull('rating')->avg('rating'), 1)
                    : null,
            ];
        });

        return view('perusahaan.expert-utilization', [
            'perusahaan' => $perusahaan,
            'rows' => $rows,
            'quotaChat' => ['used' => $chatUsed, 'total' => $chatTotal],
            'quotaDeep' => ['used' => $deepUsed, 'total' => $deepTotal],
        ]);
    }
}
