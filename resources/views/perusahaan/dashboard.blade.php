@extends('layouts.app')

@section('content')
@php
    $riskPalette = [
        'Sehat' => '#007648',
        'Perlu Perhatian' => '#e2703a',
        'Berisiko Tinggi' => '#dc2626',
    ];
    $statCards = [
        'karyawan' => ['label' => 'Karyawan terdaftar', 'value' => $registered, 'icon' => 'users', 'tone' => 'slate', 'detail' => $activeEmployeesMonth . ' aktif bulan ini'],
        'selesai' => ['label' => 'Sesi selesai bulan ini', 'value' => $doneMonth, 'icon' => 'check', 'tone' => 'pine', 'detail' => ($deltaDone >= 0 ? '+' : '') . $deltaDone . ' vs bulan lalu'],
        'terjadwal' => ['label' => 'Sesi terjadwal', 'value' => $scheduled, 'icon' => 'calendar', 'tone' => 'terra', 'detail' => $scheduledNext7Days . ' jatuh tempo 7 hari'],
        'rating' => ['label' => 'Rating rata-rata', 'value' => number_format($avgRating, 1) . ' / 5', 'icon' => 'star', 'tone' => 'slate', 'detail' => 'Aggregate after-session'],
        'noshow' => ['label' => 'No-show rate', 'value' => $noShowRate . '%', 'icon' => 'bolt', 'tone' => $noShowRate > 15 ? 'red' : 'pine', 'detail' => 'Sesi tidak dihadiri'],
    ];
    $toneClasses = [
        'pine' => ['chip' => 'bg-pine-100 text-pine-800', 'icon' => 'bg-pine-50 text-pine-700', 'text' => 'text-pine-700', 'soft' => 'bg-pine-50'],
        'terra' => ['chip' => 'bg-terra-100 text-terra-800', 'icon' => 'bg-terra-50 text-terra-600', 'text' => 'text-terra-700', 'soft' => 'bg-terra-50'],
        'red' => ['chip' => 'bg-red-100 text-red-800', 'icon' => 'bg-red-50 text-red-600', 'text' => 'text-red-700', 'soft' => 'bg-red-50'],
        'slate' => ['chip' => 'bg-gray-100 text-gray-700', 'icon' => 'bg-gray-100 text-gray-600', 'text' => 'text-gray-900', 'soft' => 'bg-gray-50'],
    ];
@endphp

<style>
    html { scroll-behavior: smooth; }
    .dash-card {
        border: 1px solid #e7e2d9;
        background: #fff;
        border-radius: 1.25rem;
        box-shadow: 0 1px 2px rgba(12, 59, 46, 0.04), 0 12px 28px rgba(12, 59, 46, 0.04);
    }
    .dash-section-title { font-size: .95rem; font-weight: 600; color: #1a2420; }
    .dash-section-subtitle { margin-top: .25rem; font-size: .75rem; color: #6b7280; }
    .panel-surface {
        border-radius: 1rem;
        border: 1px solid #e7e2d9;
        background: linear-gradient(180deg, #fdfcfa, #f7f4ee);
        padding: 1rem;
    }
    .mini-insight {
        border-radius: .9rem;
        border: 1px solid #e7e2d9;
        background: #fff;
        padding: .85rem .95rem;
    }
    .hero-band {
        border-radius: 1.25rem;
        background: linear-gradient(135deg, #062a20 0%, #0c3b2e 45%, #007648 100%);
        color: white;
        overflow: hidden;
        position: relative;
    }
    .hero-band::after {
        content: "";
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at top right, rgba(255,255,255,0.14), transparent 24%),
            linear-gradient(90deg, transparent, rgba(255,255,255,0.03));
        pointer-events: none;
    }
    .hero-metric {
        border-radius: 1rem;
        border: 1px solid rgba(255,255,255,0.12);
        background: rgba(255,255,255,0.08);
        padding: 1rem;
        position: relative;
        z-index: 1;
    }
    .chart-wrap { position: relative; height: 220px; }
    .chart-wrap--sm { position: relative; height: 155px; }
    .chart-wrap--donut { position: relative; height: 180px; }
    .analytics-grid {
        display: grid;
        gap: .75rem;
        grid-template-columns: minmax(0, 1.35fr) minmax(290px, .8fr);
    }
    .fade-up { animation: fadeUp .55s ease both; }
    .fade-up:nth-child(2) { animation-delay: .04s; }
    .fade-up:nth-child(3) { animation-delay: .08s; }
    .fade-up:nth-child(4) { animation-delay: .12s; }
    .fade-up:nth-child(5) { animation-delay: .16s; }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="space-y-4" x-data="{ modal: null }" @keydown.escape.window="modal = null">
    {{-- Alert strip — kondisi yang butuh perhatian HR --}}
    @if($alerts->count())
    <div class="fade-up space-y-2">
        @foreach($alerts as $alert)
        <div class="flex items-center gap-3 rounded-2xl border px-4 py-3 text-sm
            {{ $alert['tone'] === 'red' ? 'bg-red-50 border-red-200 text-red-800' : ($alert['tone'] === 'terra' ? 'bg-terra-50 border-terra-200 text-terra-800' : 'bg-pine-50 border-pine-200 text-pine-800') }}">
            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full {{ $alert['tone'] === 'red' ? 'bg-red-100' : ($alert['tone'] === 'terra' ? 'bg-terra-100' : 'bg-pine-100') }}">
                @include('perusahaan.partials.icon', ['name' => $alert['tone'] === 'pine' ? 'calendar' : 'bolt'])
            </span>
            {{ $alert['text'] }}
        </div>
        @endforeach
    </div>
    @endif

    {{-- Sesi aktif — HR internal melihat siapa saja yang sedang konsultasi --}}
    @if($activeSessions->count())
    <section class="fade-up dash-card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="dash-section-title">Sesi aktif</h3>
                <p class="dash-section-subtitle">Sesi terjadwal — hijau berarti konsultan sudah mulai (check-in).</p>
            </div>
            <span class="rounded-full bg-pine-50 px-3 py-1 text-xs font-semibold text-pine-700">{{ $activeSessions->count() }} sesi</span>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left text-[11px] uppercase tracking-wider text-gray-400">
                        <th class="pb-2 pr-4 font-medium">Karyawan</th>
                        <th class="pb-2 pr-4 font-medium">Konsultan</th>
                        <th class="pb-2 pr-4 font-medium">Jadwal</th>
                        <th class="pb-2 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($activeSessions as $s)
                    <tr>
                        <td class="py-2.5 pr-4 font-medium text-gray-900">{{ $s->employee?->name ?? '—' }}</td>
                        <td class="py-2.5 pr-4 text-gray-600">{{ $s->consultant?->user?->name ?? 'Belum ditugaskan' }}</td>
                        <td class="py-2.5 pr-4 text-gray-600">{{ $s->scheduled_at?->translatedFormat('l, d M Y H:i') ?? 'Belum dijadwalkan' }}</td>
                        <td class="py-2.5">
                            @if($s->checked_in_at)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-pine-100 px-2.5 py-1 text-xs font-semibold text-pine-700"><span class="h-1.5 w-1.5 rounded-full bg-pine-600"></span>Berlangsung</span>
                            @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-terra-100 px-2.5 py-1 text-xs font-semibold text-terra-700">Menunggu</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    @endif

    <section id="overview" class="fade-up dash-card p-4 md:p-5">
        <div class="hero-band p-5 md:p-6">
            <div class="grid gap-5 xl:grid-cols-[minmax(0,1.25fr),minmax(320px,0.75fr)]">
                <div class="relative z-10">
                    <div class="flex flex-wrap items-center gap-2 text-[11px]">
                        <span class="rounded-full bg-white/12 px-3 py-1 font-semibold uppercase tracking-[0.18em] text-white">Dashboard HR</span>
                        <span class="rounded-full bg-white/10 px-3 py-1 font-medium text-pine-50">Periode {{ now()->translatedFormat('F Y') }}</span>
                        @if($isSuperAdmin)
                            <span class="rounded-full bg-terra-500/30 px-3 py-1 font-semibold text-terra-50">Mode Super Admin</span>
                        @endif
                    </div>
                    <h2 class="mt-3 max-w-2xl text-[1.7rem] md:text-[1.9rem] font-semibold tracking-tight text-white">Executive pulse wellbeing untuk {{ $perusahaan->brand }}</h2>
                    <p class="mt-3 max-w-2xl text-[13px] leading-6 text-pine-50/85">Pantau utilisasi program, distribusi kebutuhan, dan kapasitas layanan dalam satu command center yang rapi untuk evaluasi HR mingguan.</p>

                    <div class="mt-5 grid gap-3 md:grid-cols-3">
                        <div class="hero-metric">
                            <div class="text-[11px] uppercase tracking-[0.16em] text-pine-100/80">Keterlibatan</div>
                            <div class="mt-2 text-2xl font-semibold">{{ $engagementRate }}%</div>
                            <div class="mt-1 text-[13px] text-pine-50/85">karyawan aktif bulan ini</div>
                        </div>
                        <div class="hero-metric">
                            <div class="text-[11px] uppercase tracking-[0.16em] text-pine-100/80">Capacity</div>
                            <div class="mt-2 text-2xl font-semibold">{{ $quotaPct }}%</div>
                            <div class="mt-1 text-[13px] text-pine-50/85">pemakaian kuota bulanan</div>
                        </div>
                        <div class="hero-metric">
                            <div class="text-[11px] uppercase tracking-[0.16em] text-pine-100/80">7 Hari Ke Depan</div>
                            <div class="mt-2 text-2xl font-semibold">{{ $scheduledNext7Days }}</div>
                            <div class="mt-1 text-[13px] text-pine-50/85">sesi sudah terjadwal</div>
                        </div>
                    </div>
                </div>

                <div class="relative z-10 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                    @foreach($benchmarkCards as $card)
                        @php $tone = $toneClasses[$card['tone']] ?? $toneClasses['slate']; @endphp
                        <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3.5 backdrop-blur-sm">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-[11px] uppercase tracking-[0.14em] text-pine-50/72">{{ $card['label'] }}</p>
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $tone['chip'] }}">{{ $card['target'] }}</span>
                            </div>
                            <div class="mt-2 text-lg font-semibold text-white">{{ $card['value'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5">
        @foreach($statCards as $key => $card)
            @php $tone = $toneClasses[$card['tone']]; @endphp
            <button type="button" @click="modal = '{{ $key }}'" class="fade-up dash-card p-3.5 text-left transition hover:shadow-md hover:border-pine-300 cursor-pointer">
                <div class="flex items-start justify-between gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-2xl {{ $tone['icon'] }}">
                        @include('perusahaan.partials.icon', ['name' => $card['icon']])
                    </span>
                    <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $tone['chip'] }}">{{ $card['detail'] }}</span>
                </div>
                <div class="mt-6 text-[1.75rem] font-semibold tracking-tight text-gray-900">{{ $card['value'] }}</div>
                <p class="mt-1 text-[13px] text-gray-500">{{ $card['label'] }} <span class="text-pine-600 font-medium">· detail</span></p>
            </button>
        @endforeach
    </section>

    <section class="fade-up dash-card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="dash-section-title">Funnel keterlibatan</h3>
                <p class="dash-section-subtitle">Dari karyawan terdaftar hingga pengguna aktif bulan ini.</p>
            </div>
            <span class="rounded-full bg-gray-100 px-3 py-1 text-[11px] font-medium text-gray-500">Agregat saja</span>
        </div>
        <div class="mt-4 grid gap-3 md:grid-cols-3">
            @php
                $funnelSteps = [
                    ['label' => 'Terdaftar', 'value' => $funnel['registered'], 'pct' => 100],
                    ['label' => 'Pernah aktivasi', 'value' => $funnel['activated'], 'pct' => $funnel['registered'] > 0 ? round($funnel['activated'] / $funnel['registered'] * 100) : 0],
                    ['label' => 'Aktif bulan ini', 'value' => $funnel['activeMonth'], 'pct' => $funnel['registered'] > 0 ? round($funnel['activeMonth'] / $funnel['registered'] * 100) : 0],
                ];
            @endphp
            @foreach($funnelSteps as $step)
            <div class="panel-surface bg-white">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium text-gray-600">{{ $step['label'] }}</span>
                    <span class="font-semibold text-gray-900">{{ $step['value'] }}</span>
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100">
                    <div class="h-full rounded-full bg-pine-600" style="width: {{ $step['pct'] }}%"></div>
                </div>
                <p class="mt-2 text-[11px] text-gray-400">{{ $step['pct'] }}% dari terdaftar</p>
            </div>
            @endforeach
        </div>
    </section>

    <section class="grid gap-3 xl:grid-cols-[minmax(0,1.35fr),minmax(0,0.95fr)]">
        <div class="fade-up dash-card p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="dash-section-title">Pemakaian kuota PEPM</h3>
                    <p class="dash-section-subtitle">Kapasitas bulanan berdasarkan jumlah karyawan terdaftar dan sesi per karyawan.</p>
                </div>
                <span class="rounded-full bg-pine-50 px-3 py-1 text-xs font-semibold text-pine-700">{{ $quotaUsed }} / {{ $quotaTotal }} sesi</span>
            </div>
            <div class="mt-5 h-3 overflow-hidden rounded-full bg-gray-100">
                <div class="h-full rounded-full bg-gradient-to-r from-pine-700 via-pine-500 to-terra-400" style="width: {{ $quotaPct }}%"></div>
            </div>
            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs">
                <span class="text-gray-500">Utilisasi saat ini {{ $quotaPct }}%</span>
                <span class="{{ $quotaPct >= 80 ? 'text-terra-700' : 'text-pine-700' }} font-semibold">{{ $quotaPct >= 80 ? 'Mendekati batas kapasitas bulanan' : 'Masih dalam band sehat pemakaian' }}</span>
            </div>
        </div>

        <div class="fade-up dash-card p-5">
            <div class="flex items-center justify-between">
                <h3 class="dash-section-title">Area Fokus</h3>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-[11px] font-medium text-gray-500">Agregat saja</span>
            </div>
            <div class="mt-4 space-y-3">
                @foreach($focusAreas as $item)
                    @php $tone = $toneClasses[$item['tone']] ?? $toneClasses['slate']; @endphp
                    <div class="rounded-2xl {{ $tone['soft'] }} px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs uppercase tracking-[0.14em] text-gray-400">{{ $item['title'] }}</p>
                                <div class="mt-1 text-xl font-semibold {{ $tone['text'] }}">{{ $item['value'] }}</div>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $tone['chip'] }}">Review</span>
                        </div>
                        <p class="mt-2 text-xs leading-6 text-gray-500">{{ $item['note'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="wellbeing" class="grid gap-3 xl:grid-cols-[minmax(0,1.12fr),minmax(0,0.88fr)]">
        <div class="fade-up dash-card p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="dash-section-title">Kesejahteraan agregat</h3>
                    <p class="dash-section-subtitle">NPS dan distribusi risiko dipresentasikan anonim pada level perusahaan.</p>
                </div>
                <span class="rounded-full bg-pine-50 px-3 py-1 text-xs font-semibold text-pine-700">NPS {{ number_format($npsScore, 1) }}</span>
            </div>
            <div class="mt-5 grid gap-5 md:grid-cols-[200px,minmax(0,1fr)] md:items-center">
                <div>
                    <div class="chart-wrap--donut">
                        <canvas id="chartNps"></canvas>
                    </div>
                    <p class="mt-3 text-center text-xs text-gray-500">Skor wellbeing agregat</p>
                </div>
                <div class="space-y-4">
                    @if($totalAssessed < 5)
                        <p class="rounded-2xl bg-gray-50 px-4 py-6 text-center text-xs text-gray-400">
                            Distribusi risiko disembunyikan sampai {{ 5 - $totalAssessed }} asesmen lagi terkumpul (aturan anonimitas n≥5).
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
        </div>

        <div class="fade-up dash-card p-5">
            <h3 class="dash-section-title">Konsultan dari Soluusi</h3>
            <p class="dash-section-subtitle">Ditugaskan Soluusi untuk program wellbeing perusahaan ini. Karyawan bisa pilih sendiri saat booking.</p>
            <div class="mt-4 grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
                @forelse($consultantPool as $c)
                <div class="rounded-2xl border border-[#e7e2d9] p-4">
                    <p class="text-sm font-semibold text-gray-900">{{ $c['name'] }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $c['category'] ?: ucfirst($c['type']) }}</p>
                </div>
                @empty
                <p class="text-sm text-gray-500">Belum ada konsultan ditugaskan — hubungi Soluusi.</p>
                @endforelse
            </div>
        </div>

        <section id="ai-insight" class="fade-up dash-card p-5" x-data="aiInsight()">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="dash-section-title">Insight AI &amp; usulan tindakan</h3>
                <p class="dash-section-subtitle">Analisis AI atas data agregat (topik anonim, risiko WHO-5, utilisasi). Tanpa nama individu. Tinjau bersama tim sebelum diterapkan.</p>
            </div>
            <button type="button" @click="generate()" :disabled="processing"
                class="rounded-xl bg-pine-700 px-4 py-2 text-sm font-semibold text-white hover:bg-pine-800 disabled:opacity-60"
                x-text="processing ? 'Sedang menganalisis…' : (state?.stale && state?.insight ? 'Perbarui insight AI' : 'Analisis dengan AI')"></button>
        </div>

        <template x-if="error"><p class="mt-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700" x-text="error"></p></template>
        <template x-if="state && state.error && !processing"><p class="mt-4 rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-800" x-text="state.error"></p></template>

        <div x-show="processing" class="mt-5 flex items-center gap-3 rounded-2xl bg-gray-50 px-4 py-6 text-sm text-gray-500">
            <svg class="h-4 w-4 animate-spin text-pine-700" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.25"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
            AI membaca agregat sesi, topik anonim, dan asesmen. Proses ± 1 menit.
        </div>

        <div x-show="!processing && insight" class="mt-5 space-y-4">
            <div class="panel-surface">
                <p class="text-[11px] uppercase tracking-[0.14em] text-gray-400">Ringkasan kondisi</p>
                <p class="mt-2 text-sm leading-6 text-gray-700" x-text="insight?.summary"></p>
            </div>
            <template x-for="(f, i) in (insight?.findings || [])" :key="i">
                <div class="rounded-2xl border border-[#e7e2d9] p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-gray-900" x-text="f.title"></p>
                        <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold"
                            :class="f.priority === 'prioritas' ? 'bg-red-100 text-red-700' : (f.priority === 'terencana' ? 'bg-terra-100 text-terra-800' : 'bg-gray-100 text-gray-600')"
                            x-text="f.priority === 'prioritas' ? 'Prioritas tindak lanjut' : (f.priority === 'terencana' ? 'Tindak lanjut terencana' : 'Pemantauan')"></span>
                    </div>
                    <p class="mt-2 text-xs leading-6 text-gray-600" x-text="f.detail"></p>
                    <p class="mt-2 text-xs leading-6 text-gray-700"><span class="font-semibold">Usulan tindakan HR:</span> <span x-text="f.hrAction"></span></p>
                    <p class="mt-1 text-xs leading-6 text-gray-500"><span class="font-semibold">Rekomendasi ahli pada sumber:</span> <span x-text="f.expertRecommendation"></span></p>
                </div>
            </template>
            <template x-if="insight && insight.dataGaps && insight.dataGaps.length">
                <div class="rounded-2xl bg-amber-50 px-4 py-3">
                    <p class="text-xs font-semibold text-amber-800">Keterbatasan data</p>
                    <ul class="mt-1 list-disc pl-5 text-xs text-amber-700"><template x-for="(g, i) in insight.dataGaps" :key="i"><li x-text="g"></li></template></ul>
                </div>
            </template>
            <p class="text-[11px] text-gray-400">Dibuat <span x-text="generatedAt || '—'"></span> · model <span x-text="modelName || '—'"></span> · usulan AI, bukan diagnosis. Kutipan divalidasi sistem; tema tetap perlu ditinjau.</p>
        </div>

        <div x-show="!processing && !insight && !error" class="mt-4 rounded-3xl bg-gray-50 px-4 py-8 text-center text-sm text-gray-400">
            Belum ada analisis AI untuk periode ini. Klik "Analisis dengan AI" untuk membuat.
        </div>
    </section>

    <div class="fade-up dash-card p-5">
            <div class="flex items-center justify-between gap-3">
                <h3 class="dash-section-title">Action board</h3>
                <span class="rounded-full bg-pine-50 px-3 py-1 text-[11px] font-semibold text-pine-700">Data-driven</span>
            </div>
            <div class="mt-4 space-y-3">
                @foreach($insights as $item)
                    @php $tone = $toneClasses[$item['tone']] ?? $toneClasses['slate']; @endphp
                    <div class="rounded-2xl border border-[#e7e2d9] p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-gray-900">{{ $item['name'] }}</p>
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $tone['chip'] }}">{{ $item['status'] }}</span>
                        </div>
                        @if(!empty($item['action']))
                        <p class="mt-2 text-xs text-gray-600"><span class="font-semibold">Tindakan:</span> {{ $item['action'] }}</p>
                        @endif
                        <div class="mt-3 flex items-center justify-between text-xs text-gray-500">
                            <span>PIC {{ $item['owner'] }}</span>
                            <span>{{ $item['window'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="mt-4 text-xs leading-6 text-gray-400">Rekomendasi diturunkan otomatis dari agregat utilisasi, partisipasi, dan asesmen.</p>
        </div>
    </section>

    <section id="agenda" class="fade-up dash-card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="dash-section-title">Jadwal dan aktivasi</h3>
                <p class="dash-section-subtitle">Agenda sesi per hari tanpa identitas peserta.</p>
            </div>
            <a href="{{ route('perusahaan.report') }}" class="rounded-full bg-pine-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-pine-800">Laporan bulanan →</a>
        </div>

        <div class="mt-5">
            @if($agenda->count())
                <div class="grid gap-3 lg:grid-cols-2">
                    @foreach($agenda as $a)
                        @php $dt = \Illuminate\Support\Carbon::parse($a->hari); @endphp
                        <div class="panel-surface">
                            <div class="flex items-center gap-4">
                                <div class="grid h-14 w-14 place-items-center rounded-2xl border border-[#e7e2d9] bg-white text-center">
                                    <div>
                                        <div class="text-[10px] uppercase tracking-[0.12em] text-gray-400">{{ $dt->translatedFormat('M') }}</div>
                                        <div class="text-lg font-semibold text-gray-900">{{ $dt->translatedFormat('d') }}</div>
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-900">{{ $dt->translatedFormat('l') }}</p>
                                    <p class="mt-1 text-xs leading-6 text-gray-500">{{ $a->c }} sesi konseling terjadwal pada hari ini.</p>
                                </div>
                                <span class="rounded-full bg-pine-100 px-3 py-1 text-xs font-semibold text-pine-700">{{ $a->c }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="rounded-3xl bg-gray-50 px-4 py-8 text-center text-sm text-gray-400">Belum ada sesi terjadwal pada kalender perusahaan.</p>
            @endif
            <p class="mt-4 text-[11px] text-gray-400">Hanya jumlah per hari yang ditampilkan. Tidak ada identitas peserta atau rincian klinis.</p>
        </div>
    </section>

    <section id="program" class="fade-up dash-card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="dash-section-title">Kelola program</h3>
                <p class="dash-section-subtitle">{{ $registered }} karyawan terdaftar · {{ $perusahaan->sessions_per_employee_month }} sesi per karyawan per bulan.</p>
            </div>
            <span class="rounded-full bg-gray-100 px-3 py-1 text-[11px] font-medium text-gray-500">Operational snapshot</span>
        </div>
        <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            @foreach($programs as $program)
                @php
                    $share = $doneMonth > 0 ? round($program['done'] / $doneMonth * 100) : 0;
                @endphp
                <div class="panel-surface bg-white">
                    <div class="flex items-center justify-between gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-2xl bg-pine-50 text-pine-700">
                            @include('perusahaan.partials.icon', ['name' => 'sparkles'])
                        </span>
                        <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $program['done'] > 0 ? 'bg-pine-100 text-pine-800' : 'bg-gray-100 text-gray-500' }}">{{ $program['done'] > 0 ? 'Aktif dipakai' : 'Belum dipakai' }}</span>
                    </div>
                    <p class="mt-4 text-sm font-semibold text-gray-900">{{ $program['name'] }}</p>
                    <p class="mt-2 text-xs leading-6 text-gray-500">{{ $program['desc'] }}</p>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-pine-600" style="width: {{ $share }}%"></div>
                    </div>
                    <p class="mt-2 text-[11px] text-gray-400">{{ $program['done'] }} sesi selesai ({{ $share }}% dari total) · {{ $program['scheduled'] }} terjadwal</p>
                </div>
            @endforeach
        </div>
    </section>

    <section id="analytics" class="fade-up dash-card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="dash-section-title">Analitik penggunaan program</h3>
                <p class="dash-section-subtitle">Rangkaian chart untuk tren adopsi, tema konsultasi anonim, dan partisipasi lintas departemen.</p>
            </div>
            <div class="flex gap-1 text-xs">
                <button data-tab="aktivitas" class="tab-btn rounded-full bg-pine-700 px-3 py-1.5 font-semibold text-white">Aktivitas</button>
                <button data-tab="topik" class="tab-btn rounded-full px-3 py-1.5 font-semibold text-gray-600 hover:bg-gray-100">Topik Populer</button>
                <button data-tab="partisipasi" class="tab-btn rounded-full px-3 py-1.5 font-semibold text-gray-600 hover:bg-gray-100">Partisipasi</button>
            </div>
        </div>

        <div id="tab-aktivitas" class="tab-panel mt-5">
            <div class="analytics-grid">
                <div class="panel-surface">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Aktivitas bulanan</p>
                            <p class="text-xs text-gray-500">Enam bulan terakhir</p>
                        </div>
                        <span class="rounded-full bg-white px-3 py-1 text-[11px] font-medium text-gray-500 border border-[#e7e2d9]">Line trend</span>
                    </div>
                    <div class="chart-wrap">
                        <canvas id="chartMonthly"></canvas>
                    </div>
                    <div class="mt-4 grid gap-3 md:grid-cols-3">
                        <div class="mini-insight">
                            <div class="text-[11px] uppercase tracking-[0.14em] text-gray-400">Sesi bulan ini</div>
                            <div class="mt-1 text-lg font-semibold text-gray-900">{{ $doneMonth }}</div>
                        </div>
                        <div class="mini-insight">
                            <div class="text-[11px] uppercase tracking-[0.14em] text-gray-400">Delta bulanan</div>
                            <div class="mt-1 text-lg font-semibold {{ $deltaDone >= 0 ? 'text-pine-700' : 'text-red-700' }}">{{ $deltaDone >= 0 ? '+' : '' }}{{ $deltaDone }}</div>
                        </div>
                        <div class="mini-insight">
                            <div class="text-[11px] uppercase tracking-[0.14em] text-gray-400">Karyawan aktif</div>
                            <div class="mt-1 text-lg font-semibold text-gray-900">{{ $activeEmployeesMonth }}</div>
                        </div>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="panel-surface">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Sesi per minggu</p>
                                <p class="text-xs text-gray-500">8 minggu terakhir</p>
                            </div>
                            <span class="rounded-full bg-white px-3 py-1 text-[11px] font-medium text-gray-500 border border-[#e7e2d9]">Weekly bar</span>
                        </div>
                        <div class="chart-wrap--sm">
                            <canvas id="chartWeekly"></canvas>
                        </div>
                    </div>
                    <div class="panel-surface">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Sesi per departemen</p>
                                <p class="text-xs text-gray-500">Aggregate by department</p>
                            </div>
                            <span class="rounded-full bg-white px-3 py-1 text-[11px] font-medium text-gray-500 border border-[#e7e2d9]">{{ $topParticipationDept ?: 'Belum ada data' }}</span>
                        </div>
                        <div class="chart-wrap--sm">
                            <canvas id="chartDept"></canvas>
                        </div>
                    </div>
                    <div class="panel-surface">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Distribusi rating sesi</p>
                                <p class="text-xs text-gray-500">Feedback agregat setelah sesi selesai</p>
                            </div>
                            <span class="rounded-full bg-white px-3 py-1 text-[11px] font-medium text-gray-500 border border-[#e7e2d9]">{{ number_format($avgRating, 1) }} / 5</span>
                        </div>
                        <div class="chart-wrap--sm">
                            <canvas id="chartRating"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-3 grid gap-3 md:grid-cols-3">
                <div class="mini-insight">
                    <div class="text-[11px] uppercase tracking-[0.14em] text-gray-400">No-show bulan ini</div>
                    <div class="mt-1 text-lg font-semibold {{ $noShowRate > 15 ? 'text-red-700' : 'text-gray-900' }}">{{ $noShowRate }}%</div>
                </div>
                <div class="mini-insight">
                    <div class="text-[11px] uppercase tracking-[0.14em] text-gray-400">Asesmen WHO-5 (90 hari)</div>
                    <div class="mt-1 text-lg font-semibold text-gray-900">{{ $totalAssessed }}</div>
                </div>
                <div class="mini-insight">
                    <div class="text-[11px] uppercase tracking-[0.14em] text-gray-400">Belum onboard dari kontrak</div>
                    <div class="mt-1 text-lg font-semibold {{ $contractGap > 0 ? 'text-terra-700' : 'text-gray-900' }}">{{ $contractGap }}</div>
                </div>
            </div>
        </div>

        <div id="tab-topik" class="tab-panel mt-5 hidden">
            <div class="grid gap-3 xl:grid-cols-[minmax(0,0.82fr),minmax(0,1.18fr)]">
                <div class="panel-surface">
                    <div class="chart-wrap">
                        <canvas id="chartTopics"></canvas>
                    </div>
                </div>
                <div class="grid gap-3 content-start">
                    @forelse($topics as $topic => $count)
                        <div class="panel-surface bg-white">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $topic }}</p>
                                    <p class="mt-1 text-xs text-gray-500">Topik anonim tanpa identitas individu</p>
                                </div>
                                <span class="rounded-full bg-pine-100 px-3 py-1 text-xs font-semibold text-pine-700">{{ $count }} sesi</span>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-3xl bg-gray-50 px-4 py-10 text-center text-sm text-gray-400">Belum ada data topik selesai.</p>
                    @endforelse
                    @if($topics->count())
                        <div class="mini-insight">
                            <div class="text-[11px] uppercase tracking-[0.14em] text-gray-400">Topik utama</div>
                            <div class="mt-1 text-lg font-semibold text-gray-900">{{ $topics->keys()->first() }}</div>
                            <div class="mt-1 text-xs text-gray-500">{{ $topics->first() }} sesi anonim paling dominan</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div id="tab-partisipasi" class="tab-panel mt-5 hidden">
            <div class="grid gap-3 xl:grid-cols-[minmax(0,1.2fr),minmax(0,0.8fr)]">
                <div class="panel-surface">
                    <div class="chart-wrap">
                        <canvas id="chartParticipation"></canvas>
                    </div>
                </div>
                <div class="space-y-3">
                    @foreach($chartParticipation['labels'] as $index => $label)
                        @php $value = $chartParticipation['data'][$index] ?? null; @endphp
                        <div class="panel-surface bg-white">
                            <div class="mb-2 flex items-center justify-between text-sm">
                                <span class="font-semibold text-gray-900">{{ $label }}</span>
                                @if($value === null)
                                    <span class="text-xs text-gray-400">Disembunyikan (n<5)</span>
                                @else
                                    <span class="font-semibold {{ $value >= 50 ? 'text-pine-700' : 'text-terra-700' }}">{{ $value }}%</span>
                                @endif
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full {{ $value >= 50 ? 'bg-pine-600' : 'bg-terra-400' }}" style="width: {{ $value ?? 0 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="fade-up dash-card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="dash-section-title">Aksi cepat</h3>
                <p class="dash-section-subtitle">Operasional HR mingguan.</p>
            </div>
        </div>
        <div class="mt-4 grid gap-3 md:grid-cols-2">
            <a href="{{ route('perusahaan.dashboard.export') }}" class="panel-surface bg-white flex items-center justify-between text-sm font-medium text-gray-800 hover:border-pine-300 transition">
                <span class="flex items-center gap-2">
                    @include('perusahaan.partials.icon', ['name' => 'document-arrow-down'])
                    Export laporan agregat
                </span>
                <span class="text-[11px] text-gray-400">CSV</span>
            </a>
            <a href="{{ route('perusahaan.employees') }}" class="panel-surface bg-white flex items-center justify-between text-sm font-medium text-gray-800 hover:border-pine-300 transition">
                <span class="flex items-center gap-2">
                    @include('perusahaan.partials.icon', ['name' => 'users'])
                    Kelola karyawan
                </span>
                <span class="text-[11px] text-gray-400">{{ $registered }} terdaftar</span>
            </a>
            <a href="{{ route('perusahaan.consultants.pool') }}" class="panel-surface bg-white flex items-center justify-between text-sm font-medium text-gray-800 hover:border-pine-300 transition">
                <span class="flex items-center gap-2">
                    @include('perusahaan.partials.icon', ['name' => 'users'])
                    Pool konsultan
                </span>
                <span class="text-[11px] text-gray-400">Aktif/nonaktif</span>
            </a>
        </div>
    </section>

    {{-- Modal: detail per stat card (data agregat, anonim) --}}
    <div x-show="modal" x-cloak x-transition.opacity class="fixed inset-0 z-50 grid place-items-center bg-pine-950/50 p-4">
        <div @click.outside="modal = null" x-show="modal" x-transition.scale class="portal-card w-full max-w-lg p-6">
            <div class="flex items-start justify-between">
                <h3 class="font-semibold text-pine-900" x-text="{
                    karyawan: 'Karyawan terdaftar',
                    selesai: 'Sesi selesai bulan ini',
                    terjadwal: 'Sesi terjadwal',
                    rating: 'Rating rata-rata',
                    noshow: 'No-show rate'
                }[modal]"></h3>
                <button @click="modal = null" class="text-gray-400 hover:text-gray-700">✕</button>
            </div>

            <div x-show="modal === 'karyawan'" class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between rounded-xl bg-pine-50 px-4 py-3"><span class="text-gray-600">Total terdaftar</span><span class="font-semibold">{{ $registered }}</span></div>
                <div class="flex justify-between rounded-xl bg-pine-50 px-4 py-3"><span class="text-gray-600">Aktif bulan ini</span><span class="font-semibold">{{ $activeEmployeesMonth }} ({{ $engagementRate }}%)</span></div>
                <div class="flex justify-between rounded-xl bg-pine-50 px-4 py-3"><span class="text-gray-600">Pernah aktivasi</span><span class="font-semibold">{{ $funnel['activated'] }}</span></div>
                <div class="flex justify-between rounded-xl bg-terra-50 px-4 py-3"><span class="text-gray-600">Belum onboard dari kontrak</span><span class="font-semibold text-terra-700">{{ $contractGap }}</span></div>
                <a href="{{ route('perusahaan.employees') }}" class="block text-center rounded-lg bg-pine-700 text-white text-sm font-semibold px-4 py-2.5 hover:bg-pine-800">Kelola karyawan →</a>
            </div>

            <div x-show="modal === 'selesai'" class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between rounded-xl bg-pine-50 px-4 py-3"><span class="text-gray-600">Bulan ini</span><span class="font-semibold">{{ $doneMonth }} sesi</span></div>
                <div class="flex justify-between rounded-xl bg-pine-50 px-4 py-3"><span class="text-gray-600">Vs bulan lalu</span><span class="font-semibold {{ $deltaDone >= 0 ? 'text-pine-700' : 'text-red-600' }}">{{ $deltaDone >= 0 ? '+' : '' }}{{ $deltaDone }}</span></div>
                <p class="text-xs text-gray-500">Per jenis layanan bulan ini:</p>
                @foreach($byType as $type => $c)
                <div class="flex justify-between rounded-xl border border-[#e7e2d9] px-4 py-2.5"><span class="text-gray-600">{{ ucfirst($type) }}</span><span class="font-semibold">{{ $c }}</span></div>
                @endforeach
                <a href="{{ route('perusahaan.report') }}" class="block text-center rounded-lg bg-pine-700 text-white text-sm font-semibold px-4 py-2.5 hover:bg-pine-800">Laporan lengkap →</a>
            </div>

            <div x-show="modal === 'terjadwal'" class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between rounded-xl bg-pine-50 px-4 py-3"><span class="text-gray-600">Total mendatang</span><span class="font-semibold">{{ $scheduled }}</span></div>
                <div class="flex justify-between rounded-xl bg-pine-50 px-4 py-3"><span class="text-gray-600">7 hari ke depan</span><span class="font-semibold">{{ $scheduledNext7Days }}</span></div>
                @if($agenda->count())
                <p class="text-xs text-gray-500 pt-1">Agenda per hari:</p>
                @foreach($agenda->take(5) as $a)
                <div class="flex justify-between rounded-xl border border-[#e7e2d9] px-4 py-2.5">
                    <span class="text-gray-600">{{ \Illuminate\Support\Carbon::parse($a->hari)->translatedFormat('l, d M') }}</span>
                    <span class="font-semibold">{{ $a->c }} sesi</span>
                </div>
                @endforeach
                @endif
            </div>

            <div x-show="modal === 'rating'" class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between rounded-xl bg-pine-50 px-4 py-3"><span class="text-gray-600">Rata-rata</span><span class="font-semibold">{{ number_format($avgRating, 1) }} / 5</span></div>
                <p class="text-xs text-gray-500">Distribusi rating:</p>
                @foreach($ratingChart['labels'] as $i => $stars)
                @php $c = $ratingChart['data'][$i] ?? 0; $total = max(1, collect($ratingChart['data'])->sum()); @endphp
                <div>
                    <div class="flex justify-between text-xs mb-1"><span class="text-gray-600">{{ $stars }} bintang</span><span class="font-medium">{{ $c }} sesi</span></div>
                    <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-full rounded-full {{ $stars >= 4 ? 'bg-pine-600' : ($stars == 3 ? 'bg-terra-400' : 'bg-red-400') }}" style="width: {{ round($c / $total * 100) }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>

            <div x-show="modal === 'noshow'" class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between rounded-xl {{ $noShowRate > 15 ? 'bg-red-50' : 'bg-pine-50' }} px-4 py-3">
                    <span class="text-gray-600">Bulan ini</span>
                    <span class="font-semibold {{ $noShowRate > 15 ? 'text-red-700' : '' }}">{{ $noShowRate }}%</span>
                </div>
                <div class="flex justify-between rounded-xl border border-[#e7e2d9] px-4 py-3"><span class="text-gray-600">Batas wajar</span><span class="font-semibold">≤ 15%</span></div>
                <p class="text-xs leading-5 text-gray-500">{{ $noShowRate > 15 ? 'Di atas batas wajar — review alur reminder dan konfirmasi kehadiran sesi.' : 'Masih dalam batas sehat. Pertahankan reminder otomatis.' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
<script>
function aiInsight() {
    return {
        state: null, error: '', processing: false, timer: null,
        async init() { await this.load(); },
        async load() {
            try {
                const res = await fetch('{{ route('perusahaan.dashboard.ai-insight') }}', { headers: { 'Accept': 'application/json' } });
                if (res.ok) this.state = await res.json();
            } catch (e) { /* biarkan tombol yang memicu error */ }
            this.pollIfRunning();
        },
        pollIfRunning() {
            if (this.state?.status !== 'running') return;
            this.processing = true;
            clearInterval(this.timer);
            this.timer = setInterval(async () => {
                try {
                    const res = await fetch('{{ route('perusahaan.dashboard.ai-insight') }}', { headers: { 'Accept': 'application/json' } });
                    if (res.ok) { this.state = await res.json(); if (this.state.status !== 'running') { this.processing = false; clearInterval(this.timer); } }
                } catch (e) {}
            }, 5000);
        },
        async generate() {
            this.error = ''; this.processing = true;
            try {
                const res = await fetch('{{ route('perusahaan.dashboard.ai-insight') }}?generate=1', { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}' } });
                const json = await res.json();
                if (!res.ok) throw new Error(json.error || 'Analisis AI belum dapat dibuat.');
                this.state = json;
                this.pollIfRunning();
            } catch (e) {
                this.error = e.message;
            } finally { if (this.state?.status !== 'running') this.processing = false; }
        },
        get insight() { return this.state && !this.state.stale && this.state.status === 'completed' ? this.state.insight : null; },
        get generatedAt() { return this.state && !this.state.stale ? (this.state.generated_at || null) : null; },
        get modelName() { return this.state?.model || null; },
    };
}
</script>
<script>
function pillToggle(btnSel, panelPrefix, key) {
    document.querySelectorAll(btnSel).forEach((btn) => {
        btn.addEventListener('click', () => {
            document.querySelectorAll(btnSel).forEach((node) => {
                node.classList.remove('bg-pine-700', 'text-white');
                node.classList.add('text-gray-600');
            });
            btn.classList.add('bg-pine-700', 'text-white');
            btn.classList.remove('text-gray-600');
            document.querySelectorAll('[id^="' + panelPrefix + '"]').forEach((panel) => panel.classList.add('hidden'));
            document.getElementById(panelPrefix + btn.dataset[key]).classList.remove('hidden');
        });
    });
}

pillToggle('.tab-btn', 'tab-', 'tab');

const chartFont = { family: 'Instrument Sans, sans-serif', size: 11 };
const gridColor = 'rgba(148, 163, 184, 0.18)';
const axisColor = '#64748b';
const PINE = '#007648', PINE_LIGHT = '#1e8a5e', PINE_SOFT = '#4aa37c', TERRA = '#e2703a', RED = '#dc2626';

const nps = @json($npsScore);
new Chart(document.getElementById('chartNps'), {
    type: 'doughnut',
    data: {
        datasets: [{
            data: [nps, Math.max(0, 5 - nps)],
            backgroundColor: [PINE, '#e7e2d9'],
            borderWidth: 0,
            cutout: '72%',
        }],
    },
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
        datasets: [{
            data: monthly.data,
            borderColor: PINE,
            backgroundColor: 'rgba(0, 118, 72, 0.12)',
            fill: true,
            pointRadius: 3,
            pointHoverRadius: 4,
            pointBackgroundColor: PINE,
            tension: 0.36,
        }],
    },
    options: {
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color: axisColor, font: chartFont }, grid: { display: false } },
            y: { beginAtZero: true, ticks: { color: axisColor, font: chartFont, precision: 0 }, grid: { color: gridColor } },
        },
    },
});

const weekly = @json($chartWeekly);
new Chart(document.getElementById('chartWeekly'), {
    type: 'bar',
    data: {
        labels: weekly.labels,
        datasets: [{
            data: weekly.data,
            backgroundColor: [PINE, '#0d7a5f', PINE_LIGHT, PINE_SOFT, '#7fc1a0', PINE_SOFT, PINE_LIGHT, '#0d7a5f'],
            borderRadius: 999,
            borderSkipped: false,
        }],
    },
    options: {
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color: axisColor, font: chartFont }, grid: { display: false } },
            y: { beginAtZero: true, ticks: { color: axisColor, font: chartFont, precision: 0 }, grid: { color: gridColor } },
        },
    },
});

const dept = @json($chartDept);
new Chart(document.getElementById('chartDept'), {
    type: 'bar',
    data: {
        labels: dept.labels,
        datasets: [{ data: dept.data, backgroundColor: PINE_LIGHT, borderRadius: 999, borderSkipped: false }],
    },
    options: {
        indexAxis: 'y',
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { beginAtZero: true, ticks: { color: axisColor, font: chartFont, precision: 0 }, grid: { color: gridColor } },
            y: { ticks: { color: axisColor, font: chartFont }, grid: { display: false } },
        },
    },
});

const topics = @json($topics);
new Chart(document.getElementById('chartTopics'), {
    type: 'doughnut',
    data: {
        labels: Object.keys(topics),
        datasets: [{
            data: Object.values(topics),
            backgroundColor: [PINE, PINE_LIGHT, TERRA, '#0c3b2e', RED, '#94a3b8'],
            borderWidth: 0,
        }],
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 10, color: axisColor, font: chartFont } },
        },
    },
});

const ratingDist = @json($ratingChart);
new Chart(document.getElementById('chartRating'), {
    type: 'bar',
    data: {
        labels: ratingDist.labels,
        datasets: [{
            data: ratingDist.data,
            backgroundColor: ratingDist.data.map((_, i) => i >= 3 ? PINE : RED),
            borderRadius: 999,
            borderSkipped: false,
        }],
    },
    options: {
        indexAxis: 'y',
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { beginAtZero: true, ticks: { color: axisColor, font: chartFont, precision: 0 }, grid: { color: gridColor } },
            y: { ticks: { color: axisColor, font: chartFont }, grid: { display: false } },
        },
    },
});

const participation = @json($chartParticipation);
new Chart(document.getElementById('chartParticipation'), {
    type: 'bar',
    data: {
        labels: participation.labels,
        datasets: [{
            data: participation.data,
            backgroundColor: participation.data.map((value) => value >= 50 ? PINE : TERRA),
            borderRadius: 999,
            borderSkipped: false,
        }],
    },
    options: {
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color: axisColor, font: chartFont }, grid: { display: false } },
            y: {
                beginAtZero: true,
                max: 100,
                ticks: { color: axisColor, font: chartFont, callback: (value) => value + '%' },
                grid: { color: gridColor },
            },
        },
    },
});
</script>
@endpush
