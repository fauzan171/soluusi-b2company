@extends('layouts.app')

@section('title', 'Laporan wellbeing — '.$perusahaan->brand)

@section('content')
@php
    $monthLabel = \Illuminate\Support\Carbon::parse($month.'-01')->translatedFormat('F Y');
    $riskPalette = [
        'Sehat' => '#007648',
        'Perlu Perhatian' => '#e2703a',
        'Berisiko Tinggi' => '#dc2626',
    ];
    $statCards = [
        ['label' => 'Karyawan terdaftar', 'value' => $registered, 'icon' => 'users', 'tone' => 'slate'],
        ['label' => 'Sesi selesai', 'value' => $done ?? '—', 'icon' => 'check', 'tone' => 'pine'],
        ['label' => 'Peserta unik', 'value' => $participants ?? '—', 'icon' => 'heart', 'tone' => 'pine'],
        ['label' => 'Partisipasi', 'value' => $participation === null ? '—' : $participation.'%', 'icon' => 'chart', 'tone' => 'terra'],
        ['label' => 'No-show', 'value' => $noShow ?? '—', 'icon' => 'bolt', 'tone' => ($noShow ?? 0) > 0 ? 'terra' : 'slate'],
        ['label' => 'Rating rata-rata', 'value' => $avgRating ?? '—', 'icon' => 'star', 'tone' => 'slate'],
    ];
    $toneClasses = [
        'pine' => ['chip' => 'bg-pine-100 text-pine-800', 'icon' => 'bg-pine-50 text-pine-700'],
        'terra' => ['chip' => 'bg-terra-100 text-terra-800', 'icon' => 'bg-terra-50 text-terra-600'],
        'slate' => ['chip' => 'bg-gray-100 text-gray-700', 'icon' => 'bg-gray-100 text-gray-600'],
    ];
@endphp

<style>
    .dash-card {
        border: 1px solid #e7e2d9;
        background: #fff;
        border-radius: 1.25rem;
        box-shadow: 0 1px 2px rgba(12, 59, 46, 0.04), 0 12px 28px rgba(12, 59, 46, 0.04);
    }
    .panel-surface {
        border-radius: 1rem;
        border: 1px solid #e7e2d9;
        background: linear-gradient(180deg, #fdfcfa, #f7f4ee);
        padding: 1rem;
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
    .fade-up { animation: fadeUp .55s ease both; }
    .fade-up:nth-child(2) { animation-delay: .04s; }
    .fade-up:nth-child(3) { animation-delay: .08s; }
    .fade-up:nth-child(4) { animation-delay: .12s; }
    .fade-up:nth-child(5) { animation-delay: .16s; }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @media print {
        aside, .no-print { display: none !important; }
        .dash-card { box-shadow: none; border: 1px solid #ccc; }
    }
</style>

<div class="space-y-4">
    {{-- Hero header --}}
    <section class="fade-up dash-card p-4 md:p-5">
        <div class="hero-band p-5 md:p-6">
            <div class="relative z-10">
                <div class="flex flex-wrap items-center gap-2 text-[11px]">
                    <span class="rounded-full bg-white/12 px-3 py-1 font-semibold uppercase tracking-[0.18em] text-white">Laporan HR</span>
                    <span class="rounded-full bg-white/10 px-3 py-1 font-medium text-pine-50">Periode {{ $monthLabel }}</span>
                </div>
                <h2 class="mt-3 max-w-2xl text-[1.7rem] md:text-[1.9rem] font-semibold tracking-tight text-white">Laporan wellbeing {{ $perusahaan->brand }}</h2>
                <p class="mt-3 max-w-2xl text-[13px] leading-6 text-pine-50/85">Ringkasan agregat utilisasi program, partisipasi karyawan, dan distribusi kebutuhan untuk periode yang dipilih. Semua data disajikan anonim tanpa identitas individu.</p>

                <div class="mt-5 grid gap-3 md:grid-cols-3">
                    <div class="hero-metric">
                        <div class="text-[11px] uppercase tracking-[0.16em] text-pine-100/80">Karyawan terdaftar</div>
                        <div class="mt-2 text-2xl font-semibold">{{ $registered }}</div>
                        <div class="mt-1 text-[13px] text-pine-50/85">total dalam program</div>
                    </div>
                    <div class="hero-metric">
                        <div class="text-[11px] uppercase tracking-[0.16em] text-pine-100/80">Sesi selesai</div>
                        <div class="mt-2 text-2xl font-semibold">{{ $done ?? '—' }}</div>
                        <div class="mt-1 text-[13px] text-pine-50/85">bulan {{ $monthLabel }}</div>
                    </div>
                    <div class="hero-metric">
                        <div class="text-[11px] uppercase tracking-[0.16em] text-pine-100/80">Partisipasi</div>
                        <div class="mt-2 text-2xl font-semibold">{{ $participation === null ? '—' : $participation.'%' }}</div>
                        <div class="mt-1 text-[13px] text-pine-50/85">karyawan aktif menggunakan layanan</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Filter periode --}}
    <section class="fade-up dash-card p-4 md:p-5 no-print">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="month" class="block text-xs font-medium text-gray-500">Periode bulan</label>
                <input type="month" id="month" name="month" value="{{ $month }}" required
                    class="mt-1 rounded-xl border border-[#e7e2d9] bg-white px-3 py-2 text-sm text-gray-900 focus:border-pine-500 focus:outline-none focus:ring-1 focus:ring-pine-500">
            </div>
            <button type="submit" class="rounded-xl bg-pine-700 px-4 py-2 text-sm font-semibold text-white hover:bg-pine-800 transition">Tampilkan</button>
            <button type="button" onclick="window.print()" class="rounded-xl border border-[#e7e2d9] bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">Cetak / PDF</button>
        </form>
        <p class="mt-3 text-[11px] text-gray-400">Data agregat berdasarkan orang unik. Breakdown dan data pelengkap disembunyikan jika contributor nonzero kurang dari lima; bukan jaminan anonimitas absolut.</p>
    </section>

    {{-- Stat cards --}}
    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        @foreach($statCards as $card)
            @php $tone = $toneClasses[$card['tone']]; @endphp
            <div class="fade-up dash-card p-3.5">
                <div class="flex items-start justify-between gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-2xl {{ $tone['icon'] }}">
                        @include('perusahaan.partials.icon', ['name' => $card['icon']])
                    </span>
                </div>
                <div class="mt-4 text-[1.6rem] font-semibold tracking-tight text-gray-900">{{ $card['value'] }}</div>
                <p class="mt-1 text-[13px] text-gray-500">{{ $card['label'] }}</p>
            </div>
        @endforeach
    </section>

    @if($suppressed)
    <div class="fade-up rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        Data sesi tidak tersedia untuk perlindungan privasi (aturan anonimitas n≥5 belum terpenuhi).
    </div>
    @endif

    {{-- Breakdown grid --}}
    <section class="grid gap-3 lg:grid-cols-2">
        {{-- Departemen --}}
        <div class="fade-up dash-card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-[0.95rem] font-semibold text-gray-900">Departemen</h3>
                    <p class="mt-0.5 text-xs text-gray-500">Partisipasi per departemen dalam periode ini.</p>
                </div>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-[11px] font-medium text-gray-500">Agregat saja</span>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($groups as $group)
                    @php
                        $pct = $group['participants'] !== null && $group['registered'] > 0 ? round($group['participants'] / $group['registered'] * 100) : 0;
                    @endphp
                    <div class="panel-surface bg-white">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700">{{ $group['name'] }}</span>
                            <span class="font-semibold text-gray-900">{{ $group['participants'] ?? '—' }} / {{ $group['registered'] }}</span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-pine-600" style="width: {{ $pct }}%"></div>
                        </div>
                        <p class="mt-1.5 text-[11px] text-gray-400">{{ $group['participants'] === null ? 'Data disembunyikan untuk privasi (n<5).' : $pct.'% karyawan departemen berpartisipasi' }}</p>
                    </div>
                @empty
                    <p class="rounded-3xl bg-gray-50 px-4 py-8 text-center text-sm text-gray-400">Belum ada data departemen.</p>
                @endforelse
            </div>
        </div>

        {{-- Topik --}}
        <div class="fade-up dash-card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-[0.95rem] font-semibold text-gray-900">Topik konsultasi</h3>
                    <p class="mt-0.5 text-xs text-gray-500">Tema yang muncul dalam sesi, anonim.</p>
                </div>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-[11px] font-medium text-gray-500">Agregat saja</span>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($topics as $topic => $count)
                    @php
                        $max = $topics->max();
                        $pct = $max > 0 ? round($count / $max * 100) : 0;
                    @endphp
                    <div class="panel-surface bg-white">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700">{{ $topic }}</span>
                            <span class="rounded-full bg-pine-100 px-2.5 py-0.5 text-xs font-semibold text-pine-700">{{ $count }}</span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-pine-600" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="rounded-3xl bg-gray-50 px-4 py-8 text-center text-sm text-gray-400">Belum ada data topik.</p>
                @endforelse
            </div>
        </div>

        {{-- Jenis layanan --}}
        <div class="fade-up dash-card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-[0.95rem] font-semibold text-gray-900">Jenis layanan</h3>
                    <p class="mt-0.5 text-xs text-gray-500">Distribusi sesi selesai per jenis layanan.</p>
                </div>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-[11px] font-medium text-gray-500">Agregat saja</span>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($byType as $type => $count)
                    @php
                        $max = $byType->max();
                        $pct = $max > 0 ? round($count / $max * 100) : 0;
                    @endphp
                    <div class="panel-surface bg-white">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700">{{ ucfirst($type) }}</span>
                            <span class="rounded-full bg-terra-100 px-2.5 py-0.5 text-xs font-semibold text-terra-700">{{ $count }}</span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-terra-400" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="rounded-3xl bg-gray-50 px-4 py-8 text-center text-sm text-gray-400">Belum ada data layanan.</p>
                @endforelse
            </div>
        </div>

        {{-- WHO-5 --}}
        <div class="fade-up dash-card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-[0.95rem] font-semibold text-gray-900">Distribusi WHO-5</h3>
                    <p class="mt-0.5 text-xs text-gray-500">Skor kesejahteraan agregat perusahaan. <a href="{{ route('perusahaan.report.assessment') }}" class="text-pine-700 hover:underline font-medium">Semua instrumen →</a></p>
                </div>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-[11px] font-medium text-gray-500">Agregat saja</span>
            </div>
            <div class="mt-4 space-y-3">
                @if($riskDist === null)
                    <p class="rounded-3xl bg-gray-50 px-4 py-8 text-center text-sm text-gray-400">Data asesmen tidak tersedia.</p>
                @else
                    @foreach($riskDist as $band => $count)
                        @php
                            $color = $riskPalette[$band] ?? '#6b7280';
                            $max = collect($riskDist)->max();
                            $pct = $max > 0 ? round($count / $max * 100) : 0;
                        @endphp
                        <div class="panel-surface bg-white">
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium text-gray-700">{{ $band }}</span>
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold" style="background-color: {{ $color }}20; color: {{ $color }}">{{ $count }}</span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full" style="width: {{ $pct }}%; background-color: {{ $color }}"></div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </section>

    {{-- Footer aksi --}}
    <section class="fade-up dash-card p-5 no-print">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-[0.95rem] font-semibold text-gray-900">Aksi lanjutan</h3>
                <p class="mt-0.5 text-xs text-gray-500">Export atau navigasi ke halaman terkait.</p>
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
            <a href="{{ route('perusahaan.dashboard') }}" class="panel-surface bg-white flex items-center justify-between text-sm font-medium text-gray-800 hover:border-pine-300 transition">
                <span class="flex items-center gap-2">
                    @include('perusahaan.partials.icon', ['name' => 'chart'])
                    Kembali ke dashboard
                </span>
                <span class="text-[11px] text-gray-400">Ikhtisar</span>
            </a>
        </div>
    </section>
</div>
@endsection
