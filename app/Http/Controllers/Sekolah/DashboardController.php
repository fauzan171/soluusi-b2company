<?php

namespace App\Http\Controllers\Sekolah;

use App\Http\Controllers\Controller;
use App\Services\ProgramAggregate;
use App\Support\AggregatePrivacy;
use App\Support\SekolahContext;

class DashboardController extends Controller
{
    public function index()
    {
        $sekolah = SekolahContext::current();
        abort_unless($sekolah, 403);
        if (!$sekolah->is_active) {
            return view('sekolah.pending', ['sekolah' => $sekolah, 'isSuperAdmin' => SekolahContext::canSwitch(auth()->user())]);
        }
        return view('sekolah.dashboard', ['sekolah' => $sekolah] + ProgramAggregate::monthly($sekolah, 'sekolah'));
    }

    /** Paritas dengan perusahaan.dashboard.export — agregat yang sama, istilah sekolah. */
    public function export()
    {
        $sekolah = SekolahContext::current();
        abort_unless($sekolah && $sekolah->is_active, 403);
        $data = ProgramAggregate::monthly($sekolah, 'sekolah');
        $stream = fopen('php://temp', 'r+');
        $rows = [
            ['Metrik', 'Nilai'], ['Sekolah', $sekolah->brand], ['Periode', $data['month']],
            ['Siswa terdaftar', $data['registered']], ['Sesi selesai', $data['done'] ?? 'Tidak tersedia'],
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
            'Content-Disposition' => 'attachment; filename="laporan-bk-'.$data['month'].'.csv"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}