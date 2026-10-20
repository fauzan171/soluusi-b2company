<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Services\ProgramAggregate;
use App\Support\AggregatePrivacy;
use App\Support\PerusahaanContext;

class DashboardController extends Controller
{
    public function index()
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan, 403);
        if (!$perusahaan->is_active) {
            return view('perusahaan.pending', ['perusahaan' => $perusahaan, 'isSuperAdmin' => PerusahaanContext::canSwitch(auth()->user())]);
        }
        return view('perusahaan.report', ['perusahaan' => $perusahaan] + ProgramAggregate::monthly($perusahaan, 'perusahaan'));
    }

    public function export()
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan && $perusahaan->is_active, 403);
        $data = ProgramAggregate::monthly($perusahaan, 'perusahaan');
        $stream = fopen('php://temp', 'r+');
        $rows = [
            ['Metrik', 'Nilai'], ['Perusahaan', $perusahaan->brand], ['Periode', $data['month']],
            ['Karyawan terdaftar', $data['registered']], ['Sesi selesai', $data['done'] ?? 'Tidak tersedia'],
            ['Peserta unik', $data['participants'] ?? 'Tidak tersedia'], ['No-show', $data['noShow'] ?? 'Tidak tersedia'],
        ];
        foreach ($data['groups'] as $group) {
            $rows[] = [$group['name'], $group['participants']];
        }
        foreach ($data['topics'] as $topic => $count) {
            $rows[] = [$topic, $count];
        }
        foreach ($rows as $row) {
            fputcsv($stream, array_map(fn ($cell) => is_string($cell) ? AggregatePrivacy::csvText($cell) : $cell, $row), ',', '"', '');
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="laporan-wellbeing-'.$data['month'].'.csv"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
