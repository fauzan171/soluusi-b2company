@extends('layouts.app')

@section('title', 'Kesejahteraan agregat — '.$perusahaan->brand)

@section('content')
@php
    $riskPalette = [
        'Sehat' => '#007648',
        'Perlu Perhatian' => '#e2703a',
        'Berisiko Tinggi' => '#dc2626',
    ];
@endphp

<div class="space-y-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-pine-900">Kesejahteraan agregat — detail</h1>
            <p class="text-sm text-gray-500 mt-1">Semua chart dan distribusi wellbeing perusahaan {{ $perusahaan->brand }} dalam satu halaman. Data agregat &amp; anonim.</p>
        </div>
        <a href="{{ route('perusahaan.dashboard') }}" class="text-sm text-pine-700 font-medium hover:underline">&larr; Dashboard</a>
    </div>

    {{-- Ringkasan NPS + risk --}}
    <section class="dash-card p-5">
        <div class="grid gap-5 md:grid-cols-[200px,minmax(0,1fr)] md:items-center">
            <div>
                <div class="chart-wrap--donut">
                    <canvas id="chartNps"></canvas>
                </div>
                <p class="mt-3 text-center text-xs text-gray-500">Skor wellbeing agregat</p>
            </div>
            <div class="space-y-4">
                @if($riskSuppressed || $totalAssessed < 5)
                <p class="rounded-2xl bg-gray-50 px-4 py-6 text-center text-xs text-gray-400">
                    Distribusi risiko disembunyikan sampai {{ max(5 - (int) $totalAssessed, 0) }} asesmen lagi terkumpul (aturan anonimitas n&ge;5).
                </p>
                @else
                @foreach($riskPalette as $label => $color)
                @php $value = $riskDist[$label] ?? 0; @endphp
                <div>
                    <div class="mb-1.5 flex items-center justify-between text-xs">
                        <span class="font-medium text-gray-600">{{ $label }}</span>
                        <span class="font-semibold text-gray-800">{{ $value }}%</span>
                    </div>
                    <div class="h-2.5 overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full" style="width: {{ $value }}%; background-color: {{ $color }}"></div>
                    </div>
                </div>
                @endforeach
                <p class="text-[11px] text-gray-400">Berdasarkan skor WHO-5 terbaru {{ $totalAssessed }} karyawan (90 hari terakhir).</p>
                @endif

                <div class="panel-surface">
                    <p class="text-[11px] uppercase tracking-[0.16em] text-gray-400">Campuran layanan</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @forelse($serviceShare as $type => $share)
                        <span class="rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 border border-[#e7e2d9]">{{ ucfirst($type) }} {{ $share }}%</span>
                        @empty
                        <span class="text-xs text-gray-400">Belum ada pemakaian layanan selesai.</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Tren aktivitas --}}
    <section class="dash-card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="dash-section-title">Aktivitas bulanan</h3>
                <p class="dash-section-subtitle">Enam bulan terakhir — total sesi per bulan.</p>
            </div>
            <span class="rounded-full bg-pine-50 px-3 py-1 text-xs font-semibold text-pine-700">{{ $doneMonth }} sesi bulan ini</span>
        </div>
        <div class="mt-5 chart-wrap">
            <canvas id="chartMonthly"></canvas>
        </div>
    </section>

    <section class="grid gap-3 xl:grid-cols-2">
        <div class="dash-card p-5">
            <h3 class="dash-section-title">Sesi per minggu</h3>
            <p class="dash-section-subtitle">8 minggu terakhir.</p>
            <div class="mt-5 chart-wrap">
                <canvas id="chartWeekly"></canvas>
            </div>
        </div>
        <div class="dash-card p-5">
            <h3 class="dash-section-title">Sesi per departemen</h3>
            <p class="dash-section-subtitle">Agregat bulan berjalan.</p>
            <div class="mt-5 chart-wrap">
                <canvas id="chartDept"></canvas>
            </div>
        </div>
    </section>

    <section class="grid gap-3 xl:grid-cols-2">
        <div class="dash-card p-5">
            <h3 class="dash-section-title">Distribusi rating sesi</h3>
            <p class="dash-section-subtitle">Feedback agregat setelah sesi selesai.</p>
            <div class="mt-5 chart-wrap">
                <canvas id="chartRating"></canvas>
            </div>
        </div>
        <div class="dash-card p-5">
            <h3 class="dash-section-title">Partisipasi per departemen</h3>
            <p class="dash-section-subtitle">Peserta unik done / terdaftar per departemen.</p>
            <div class="mt-5 chart-wrap">
                <canvas id="chartParticipation"></canvas>
            </div>
        </div>
    </section>

    {{-- Topik populer --}}
    <section class="dash-card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="dash-section-title">Topik konsultasi anonim</h3>
                <p class="dash-section-subtitle">Distribusi topik sesi selesai bulan ini.</p>
            </div>
        </div>
        @if($topics->count())
        <div class="mt-5 grid gap-5 md:grid-cols-[minmax(0,0.9fr),minmax(0,1.1fr)] md:items-center">
            <div class="chart-wrap">
                <canvas id="chartTopics"></canvas>
            </div>
            <div class="space-y-3">
                @foreach($topics as $topic => $count)
                <div class="panel-surface bg-white">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-gray-900">{{ $topic }}</p>
                        <span class="rounded-full bg-pine-100 px-3 py-1 text-xs font-semibold text-pine-700">{{ $count }} sesi</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @else
        <p class="mt-4 rounded-2xl bg-gray-50 px-4 py-8 text-center text-sm text-gray-400">Belum ada data topik selesai bulan ini.</p>
        @endif
    </section>

    {{-- Per-departemen tabel --}}
    @if($groups->count())
    <section class="dash-card p-5">
        <h3 class="dash-section-title">Partisipasi per departemen</h3>
        <p class="dash-section-subtitle">Nilai disembunyikan untuk departemen dengan peserta &lt; 5 (anonimitas).</p>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-[11px] uppercase tracking-wider text-gray-400">
                        <th class="pb-2 pr-4 font-medium">Departemen</th>
                        <th class="pb-2 pr-4 font-medium">Terdaftar</th>
                        <th class="pb-2 pr-4 font-medium">Peserta</th>
                        <th class="pb-2 font-medium">Sesi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($groups as $g)
                    <tr>
                        <td class="py-2.5 pr-4 font-medium text-gray-900">{{ $g['name'] }}</td>
                        <td class="py-2.5 pr-4 text-gray-600">{{ $g['registered'] }}</td>
                        <td class="py-2.5 pr-4 text-gray-600">{{ $g['participants'] ?? '—' }}</td>
                        <td class="py-2.5 text-gray-600">{{ $g['sessions'] ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
const chartFont = { family: 'Instrument Sans, sans-serif', size: 11 };
const gridColor = 'rgba(148, 163, 184, 0.18)';
const axisColor = '#64748b';
const PINE = '#007648', PINE_LIGHT = '#1e8a5e', PINE_SOFT = '#4aa37c', TERRA = '#e2703a', RED = '#dc2626';

const nps = @json($npsScore);
new Chart(document.getElementById('chartNps'), {
    type: 'doughnut',
    data: { datasets: [{ data: [nps, Math.max(0, 5 - nps)], backgroundColor: [PINE, '#e7e2d9'], borderWidth: 0, cutout: '72%' }] },
    options: { plugins: { legend: { display: false }, tooltip: { enabled: false } } },
    plugins: [{
        id: 'centerLabel',
        afterDraw(chart) {
            const { ctx } = chart;
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = PINE;
            ctx.font = '700 26px Instrument Sans, sans-serif';
            ctx.fillText(nps.toFixed(1), chart.width / 2, chart.height / 2 - 4);
            ctx.fillStyle = '#94a3b8';
            ctx.font = '500 11px Instrument Sans, sans-serif';
            ctx.fillText('out of 5', chart.width / 2, chart.height / 2 + 18);
            ctx.restore();
        },
    }],
});

const monthly = @json($chartMonthly);
new Chart(document.getElementById('chartMonthly'), {
    type: 'line',
    data: {
        labels: monthly.labels,
        datasets: [{ data: monthly.data, borderColor: PINE, backgroundColor: 'rgba(0, 118, 72, 0.12)', fill: true, pointRadius: 3, pointHoverRadius: 4, pointBackgroundColor: PINE, tension: 0.36 }],
    },
    options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { color: axisColor, font: chartFont }, grid: { display: false } }, y: { beginAtZero: true, ticks: { color: axisColor, font: chartFont, precision: 0 }, grid: { color: gridColor } } } },
});

const weekly = @json($chartWeekly);
new Chart(document.getElementById('chartWeekly'), {
    type: 'bar',
    data: {
        labels: weekly.labels,
        datasets: [{ data: weekly.data, backgroundColor: [PINE, '#0d7a5f', PINE_LIGHT, PINE_SOFT, '#7fc1a0', PINE_SOFT, PINE_LIGHT, '#0d7a5f'], borderRadius: 999, borderSkipped: false }],
    },
    options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { color: axisColor, font: chartFont }, grid: { display: false } }, y: { beginAtZero: true, ticks: { color: axisColor, font: chartFont, precision: 0 }, grid: { color: gridColor } } } },
});

const dept = @json($chartDept);
new Chart(document.getElementById('chartDept'), {
    type: 'bar',
    data: { labels: dept.labels, datasets: [{ data: dept.data, backgroundColor: PINE_LIGHT, borderRadius: 999, borderSkipped: false }] },
    options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { color: axisColor, font: chartFont, precision: 0 }, grid: { color: gridColor } }, y: { ticks: { color: axisColor, font: chartFont }, grid: { display: false } } } },
});

const ratingDist = @json($ratingChart);
new Chart(document.getElementById('chartRating'), {
    type: 'bar',
    data: {
        labels: ratingDist.labels,
        datasets: [{ data: ratingDist.data, backgroundColor: ratingDist.data.map((_, i) => i >= 3 ? PINE : RED), borderRadius: 999, borderSkipped: false }],
    },
    options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { color: axisColor, font: chartFont, precision: 0 }, grid: { color: gridColor } }, y: { ticks: { color: axisColor, font: chartFont }, grid: { display: false } } } },
});

const participation = @json($chartParticipation);
new Chart(document.getElementById('chartParticipation'), {
    type: 'bar',
    data: {
        labels: participation.labels,
        datasets: [{ data: participation.data, backgroundColor: participation.data.map((value) => value >= 50 ? PINE : TERRA), borderRadius: 999, borderSkipped: false }],
    },
    options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { color: axisColor, font: chartFont }, grid: { display: false } }, y: { beginAtZero: true, max: 100, ticks: { color: axisColor, font: chartFont, callback: (value) => value + '%' }, grid: { color: gridColor } } } },
});

@if($topics->count())
const topics = @json($topics->map(fn($c) => $c));
new Chart(document.getElementById('chartTopics'), {
    type: 'doughnut',
    data: {
        labels: Object.keys(topics),
        datasets: [{ data: Object.values(topics), backgroundColor: [PINE, PINE_LIGHT, TERRA, '#0c3b2e', RED, '#94a3b8'], borderWidth: 0 }],
    },
    options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 10, color: axisColor, font: chartFont } } } },
});
@endif
</script>
@endpush
