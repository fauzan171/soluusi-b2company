<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Models\ConsultantPool;
use App\Services\DashboardMetrics;
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
        $metrics = DashboardMetrics::build($perusahaan, 'perusahaan');
        $monthly = ProgramAggregate::monthly($perusahaan, 'perusahaan');
        $metrics['totalAssessed'] = $monthly['totalAssessed'] ?? 0;
        $metrics['byType'] = $monthly['byType'] ?? collect();

        // Stat cards + benchmark + focus areas + alert strip — turunan angka.
        $metrics['activeSessions'] = $perusahaan->sessions()
            ->with(['employee', 'schedule', 'consultant.user'])
            ->where('status', 'scheduled')
            ->orderByDesc('checked_in_at')->orderBy('scheduled_at')
            ->limit(10)->get();
        $metrics['statCards'] = [
            'karyawan' => ['label' => 'Karyawan terdaftar', 'value' => $metrics['registered'], 'icon' => 'users', 'tone' => 'slate', 'detail' => $metrics['activeMonthPeople'] . ' aktif bulan ini'],
            'selesai' => ['label' => 'Sesi selesai bulan ini', 'value' => $metrics['doneMonth'], 'icon' => 'check', 'tone' => 'pine', 'detail' => ($metrics['deltaDone'] >= 0 ? '+' : '') . $metrics['deltaDone'] . ' vs bulan lalu'],
            'terjadwal' => ['label' => 'Sesi terjadwal', 'value' => $metrics['scheduled'], 'icon' => 'calendar', 'tone' => 'terra', 'detail' => $metrics['scheduledNext7Days'] . ' jatuh tempo 7 hari'],
            'rating' => ['label' => 'Rating rata-rata', 'value' => number_format($metrics['avgRating'], 1) . ' / 5', 'icon' => 'star', 'tone' => 'slate', 'detail' => 'Agregat pasca sesi'],
            'noshow' => ['label' => 'No-show rate', 'value' => $metrics['noShowRate'] . '%', 'icon' => 'bolt', 'tone' => $metrics['noShowRate'] > 15 ? 'red' : 'pine', 'detail' => 'Sesi tidak dihadiri'],
        ];
        $metrics['benchmarkCards'] = [
            ['label' => 'Target partisipasi', 'value' => ($metrics['engagementRate'] > 0 ? $metrics['engagementRate'] : 0) . '%', 'target' => '≥ 40%', 'tone' => $metrics['engagementRate'] >= 40 ? 'pine' : 'terra'],
            ['label' => 'Completion asesmen', 'value' => $metrics['totalAssessed'] . ' karyawan', 'target' => '≥ 60%', 'tone' => 'slate'],
            ['label' => 'Pemakaian kuota', 'value' => $metrics['quotaPct'] . '%', 'target' => 'band sehat', 'tone' => $metrics['quotaPct'] >= 80 ? 'terra' : 'pine'],
        ];
        $metrics['focusAreas'] = array_values(array_filter([
            $metrics['contractGap'] > 0 ? ['title' => 'Onboarding tersisa', 'value' => $metrics['contractGap'] . ' karyawan', 'note' => 'Belum terdaftar meski masuk kuota kontrak PEPM.', 'tone' => 'terra'] : null,
            $metrics['noShowRate'] > 15 ? ['title' => 'No-show', 'value' => $metrics['noShowRate'] . '%', 'note' => 'Di atas batas wajar — review reminder & konfirmasi.', 'tone' => 'red'] : null,
            ['title' => 'Aktivasi program', 'value' => $metrics['activatedTotal'] . ' karyawan', 'note' => 'Pernah menyelesaikan minimal satu sesi sepanjang program.', 'tone' => 'pine'],
        ]));
        $metrics['alerts'] = collect(array_values(array_filter([
            $metrics['scheduledNext7Days'] > 0 ? ['tone' => 'pine', 'text' => $metrics['scheduledNext7Days'] . ' sesi terjadwal 7 hari ke depan — pastikan reminder terkirim.'] : null,
            $metrics['noShowRate'] > 15 ? ['tone' => 'red', 'text' => 'No-show ' . $metrics['noShowRate'] . '% bulan ini — di atas batas wajar 15%.'] : null,
            $metrics['contractGap'] > 0 ? ['tone' => 'terra', 'text' => $metrics['contractGap'] . ' karyawan kontrak belum onboard ke roster.'] : null,
        ])));
        $metrics['consultantPool'] = ConsultantPool::poolFor('perusahaan', $perusahaan->id)->take(4)->all();
        $metrics['programs'] = [
            ['name' => 'Psikolog', 'desc' => 'Konseling psikologi untuk stress, kecemasan, dan relasi.', 'done' => $metrics['byType']->get('psikolog', 0), 'scheduled' => 0],
            ['name' => 'Nutrisionis', 'desc' => 'Pendampingan gizi dan gaya hidup sehat.', 'done' => $metrics['byType']->get('nutrisionis', 0), 'scheduled' => 0],
            ['name' => 'Fisioterapis', 'desc' => 'Rehabilitasi dan ergonomi kerja.', 'done' => $metrics['byType']->get('fisioterapis', 0), 'scheduled' => 0],
            ['name' => 'Konselor Karir', 'desc' => 'Arah karir dan pengembangan profesional.', 'done' => $metrics['byType']->get('karir', 0), 'scheduled' => 0],
        ];
        $metrics['serviceShare'] = $metrics['byType']->map(fn ($c, $t) => round($c / max(1, $metrics['doneMonth']) * 100))->all();
        $metrics['funnel'] = [
            'registered' => $metrics['registered'],
            'activated' => $metrics['activatedTotal'],
            'activeMonth' => $metrics['activeMonthPeople'],
        ];
        $metrics['isSuperAdmin'] = PerusahaanContext::canSwitch(auth()->user());
        $metrics['activeEmployeesMonth'] = $metrics['activeMonthPeople'];

        return view('perusahaan.dashboard', ['perusahaan' => $perusahaan] + ProgramAggregate::monthly($perusahaan, 'perusahaan') + $metrics);
    }

    /** Generate / baca insight AI untuk tenant ini (agregat, tanpa identitas). */
    public function aiInsight()
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan && $perusahaan->is_active, 403);

        // Endpoint JSON untuk fetch dashboard — akses browser langsung di-redirect (F10).
        if (!request()->expectsJson()) {
            return redirect()->route('perusahaan.dashboard');
        }

        $context = \App\Services\AiInsightService::buildContext($perusahaan, 'perusahaan');
        $state = \App\Services\AiInsightService::get($perusahaan, 'perusahaan', $context, request()->boolean('generate'));

        return response()->json($state);
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
