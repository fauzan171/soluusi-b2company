<?php

namespace App\Http\Controllers\Sekolah;

use App\Http\Controllers\Controller;
use App\Models\ConsultantPool;
use App\Services\DashboardMetrics;
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

        $metrics = DashboardMetrics::build($sekolah, 'sekolah');
        $monthly = ProgramAggregate::monthly($sekolah, 'sekolah');
        $metrics['totalAssessed'] = $monthly['totalAssessed'] ?? 0;
        $metrics['byType'] = $monthly['byType'] ?? collect();

        $metrics['statCards'] = [
            'siswa' => ['label' => 'Siswa terdaftar', 'value' => $metrics['registered'], 'icon' => 'users', 'tone' => 'slate', 'detail' => $metrics['activeEmployeesMonth'] . ' aktif bulan ini'],
            'selesai' => ['label' => 'Sesi selesai bulan ini', 'value' => $metrics['doneMonth'], 'icon' => 'check', 'tone' => 'pine', 'detail' => ($metrics['deltaDone'] >= 0 ? '+' : '') . $metrics['deltaDone'] . ' vs bulan lalu'],
            'terjadwal' => ['label' => 'Sesi terjadwal', 'value' => $metrics['scheduled'], 'icon' => 'calendar', 'tone' => 'terra', 'detail' => $metrics['scheduledNext7Days'] . ' jatuh tempo 7 hari'],
            'rating' => ['label' => 'Rating rata-rata', 'value' => number_format($metrics['avgRating'], 1) . ' / 5', 'icon' => 'star', 'tone' => 'slate', 'detail' => 'Aggregate after-session'],
            'noshow' => ['label' => 'No-show rate', 'value' => $metrics['noShowRate'] . '%', 'icon' => 'bolt', 'tone' => $metrics['noShowRate'] > 15 ? 'red' : 'pine', 'detail' => 'Sesi tidak dihadiri'],
        ];
        $metrics['benchmarkCards'] = [
            ['label' => 'Target partisipasi', 'value' => ($metrics['engagementRate'] > 0 ? $metrics['engagementRate'] : 0) . '%', 'target' => '≥ 40%', 'tone' => $metrics['engagementRate'] >= 40 ? 'pine' : 'terra'],
            ['label' => 'Completion asesmen', 'value' => $metrics['totalAssessed'] . ' siswa', 'target' => '≥ 60%', 'tone' => 'slate'],
            ['label' => 'Pemakaian kuota', 'value' => $metrics['quotaPct'] . '%', 'target' => 'band sehat', 'tone' => $metrics['quotaPct'] >= 80 ? 'terra' : 'pine'],
        ];
        $metrics['focusAreas'] = array_values(array_filter([
            $metrics['contractGap'] > 0 ? ['title' => 'Onboarding tersisa', 'value' => $metrics['contractGap'] . ' siswa', 'note' => 'Belum terdaftar meski masuk kuota program sekolah.', 'tone' => 'terra'] : null,
            $metrics['noShowRate'] > 15 ? ['title' => 'No-show', 'value' => $metrics['noShowRate'] . '%', 'note' => 'Di atas batas wajar — review reminder & konfirmasi.', 'tone' => 'red'] : null,
            ['title' => 'Aktivasi program', 'value' => $metrics['activatedTotal'] . ' siswa', 'note' => 'Pernah menyelesaikan minimal satu sesi sepanjang program.', 'tone' => 'pine'],
        ]));
        $metrics['alerts'] = collect(array_values(array_filter([
            $metrics['scheduledNext7Days'] > 0 ? ['tone' => 'pine', 'text' => $metrics['scheduledNext7Days'] . ' sesi terjadwal 7 hari ke depan — pastikan reminder terkirim.'] : null,
            $metrics['noShowRate'] > 15 ? ['tone' => 'red', 'text' => 'No-show ' . $metrics['noShowRate'] . '% bulan ini — di atas batas wajar 15%.'] : null,
            $metrics['contractGap'] > 0 ? ['tone' => 'terra', 'text' => $metrics['contractGap'] . ' siswa kontrak belum onboard ke roster.'] : null,
        ])));
        $metrics['consultantPool'] = ConsultantPool::poolFor('sekolah', $sekolah->id)->take(4)->all();
        $metrics['programs'] = [
            ['name' => 'Psikolog Anak & Remaja', 'desc' => 'Konseling untuk stress akademik, kecemasan, dan relasi sebaya.', 'done' => $metrics['byType']->get('psikolog', 0), 'scheduled' => 0],
            ['name' => 'Konselor Karir', 'desc' => 'Arah studi, minat-bakat, dan persiapan lanjutan.', 'done' => $metrics['byType']->get('karir', 0), 'scheduled' => 0],
        ];
        $metrics['serviceShare'] = $metrics['byType']->map(fn ($c) => round($c / max(1, $metrics['doneMonth']) * 100))->all();
        $metrics['funnel'] = [
            'registered' => $metrics['registered'],
            'activated' => $metrics['activatedTotal'],
            'activeMonth' => $metrics['activeEmployeesMonth'],
        ];
        $metrics['isSuperAdmin'] = SekolahContext::canSwitch(auth()->user());

        return view('sekolah.dashboard', ['sekolah' => $sekolah] + $monthly + $metrics);
    }

    /** Generate / baca insight AI untuk tenant ini (agregat, tanpa identitas). */
    public function aiInsight()
    {
        $sekolah = SekolahContext::current();
        abort_unless($sekolah && $sekolah->is_active, 403);

        $context = \App\Services\AiInsightService::buildContext($sekolah, 'sekolah');
        $state = \App\Services\AiInsightService::get($sekolah, 'sekolah', $context, request()->boolean('generate'));

        return response()->json($state);
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