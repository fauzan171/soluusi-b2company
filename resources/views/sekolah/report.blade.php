@extends('layouts.app')
@section('title', 'Laporan wellbeing — '.$sekolah->brand)
@section('content')
@php
    $riskColors = [
        'Sehat' => 'bg-pine-500',
        'Perlu Perhatian' => 'bg-terra-400',
        'Berisiko Tinggi' => 'bg-red-500',
    ];
    $riskChips = [
        'Sehat' => 'bg-pine-100 text-pine-800',
        'Perlu Perhatian' => 'bg-terra-100 text-terra-800',
        'Berisiko Tinggi' => 'bg-red-100 text-red-800',
    ];
@endphp

<div class="space-y-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-pine-900">Laporan Wellbeing</h1>
            <p class="text-sm text-gray-500">Periode {{ $month }} · {{ $sekolah->brand }}</p>
        </div>
        <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 rounded-xl bg-pine-700 px-4 py-2 text-sm font-semibold text-white hover:bg-pine-800 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
            Cetak / PDF
        </button>
    </div>

    <form method="GET" class="portal-card flex flex-wrap items-end gap-3 p-4">
        <label class="text-sm font-medium text-gray-700">Bulan
            <input type="month" name="month" value="{{ $month }}" required class="mt-1 block w-full rounded-xl border border-[#e7e2d9] bg-white px-3 py-2 text-sm focus:border-pine-400 focus:ring-1 focus:ring-pine-200">
        </label>
        <button class="rounded-xl bg-pine-700 px-4 py-2 text-sm font-semibold text-white hover:bg-pine-800 transition">Tampilkan</button>
    </form>

    <p class="rounded-xl border border-[#e7e2d9] bg-[#f7f4ee] px-4 py-3 text-xs text-gray-600">Data agregat berdasarkan orang unik. Breakdown dan data pelengkap disembunyikan jika kontributor nonzero kurang dari lima; bukan jaminan anonimitas absolut.</p>

    @if($suppressed)
        <div class="rounded-xl border border-terra-200 bg-terra-50 px-4 py-3 text-sm text-terra-800">Data sesi tidak tersedia untuk perlindungan privasi.</div>
    @endif

    <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <div class="dash-card p-4">
            <p class="text-[11px] uppercase tracking-[0.14em] text-gray-400">Siswa terdaftar</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $registered }}</p>
        </div>
        <div class="dash-card p-4">
            <p class="text-[11px] uppercase tracking-[0.14em] text-gray-400">Sesi selesai</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $done ?? '—' }}</p>
        </div>
        <div class="dash-card p-4">
            <p class="text-[11px] uppercase tracking-[0.14em] text-gray-400">Peserta unik</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $participants ?? '—' }}</p>
        </div>
        <div class="dash-card p-4">
            <p class="text-[11px] uppercase tracking-[0.14em] text-gray-400">Partisipasi</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $participation === null ? '—' : $participation.'%' }}</p>
        </div>
        <div class="dash-card p-4">
            <p class="text-[11px] uppercase tracking-[0.14em] text-gray-400">No-show</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $noShow ?? '—' }}</p>
        </div>
        <div class="dash-card p-4">
            <p class="text-[11px] uppercase tracking-[0.14em] text-gray-400">Rating rata-rata</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $avgRating ?? '—' }}</p>
        </div>
    </section>

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="dash-card p-5">
            <h2 class="text-sm font-semibold text-gray-900">Kelas</h2>
            <p class="mt-0.5 text-xs text-gray-500">Partisipasi per kelas bulan ini.</p>
            <div class="mt-4 space-y-3">
                @forelse($groups as $group)
                    @php $pct = $group['participants'] !== null && $group['registered'] > 0 ? round($group['participants'] / $group['registered'] * 100) : 0; @endphp
                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700">{{ $group['name'] }}</span>
                            <span class="text-xs text-gray-500">{{ $group['participants'] === null ? 'Data disembunyikan (n<5)' : $group['participants'].' / '.$group['registered'].' siswa ('.$pct.'%)' }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-pine-600" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="rounded-xl bg-gray-50 px-4 py-6 text-center text-sm text-gray-400">Belum ada data kelas.</p>
                @endforelse
            </div>
        </section>

        <section class="dash-card p-5">
            <h2 class="text-sm font-semibold text-gray-900">Topik</h2>
            <p class="mt-0.5 text-xs text-gray-500">Topik konsultasi anonim bulan ini.</p>
            <div class="mt-4 space-y-2">
                @forelse($topics as $topic => $count)
                    <div class="flex items-center justify-between rounded-xl border border-[#e7e2d9] bg-white px-4 py-2.5 text-sm">
                        <span class="font-medium text-gray-700">{{ $topic }}</span>
                        <span class="rounded-full bg-pine-100 px-2.5 py-0.5 text-xs font-semibold text-pine-800">{{ $count }} sesi</span>
                    </div>
                @empty
                    <p class="rounded-xl bg-gray-50 px-4 py-6 text-center text-sm text-gray-400">Belum ada data topik.</p>
                @endforelse
            </div>
        </section>

        <section class="dash-card p-5">
            <h2 class="text-sm font-semibold text-gray-900">Jenis layanan</h2>
            <p class="mt-0.5 text-xs text-gray-500">Distribusi pemakaian layanan selesai.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                @forelse($byType as $type => $count)
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-[#e7e2d9] bg-white px-3 py-1.5 text-sm">
                        <span class="font-medium text-gray-700">{{ ucfirst($type) }}</span>
                        <span class="rounded-full bg-pine-100 px-2 py-0.5 text-xs font-semibold text-pine-800">{{ $count }}</span>
                    </span>
                @empty
                    <p class="rounded-xl bg-gray-50 px-4 py-6 text-center text-sm text-gray-400 w-full">Belum ada pemakaian layanan.</p>
                @endforelse
            </div>
        </section>

        <section class="dash-card p-5">
            <h2 class="text-sm font-semibold text-gray-900">WHO-5</h2>
            <p class="mt-0.5 text-xs text-gray-500">Distribusi risiko agregat dari asesmen terbaru.</p>
            @if($riskDist === null)
                <p class="mt-4 rounded-xl bg-gray-50 px-4 py-6 text-center text-sm text-gray-400">Data asesmen tidak tersedia.</p>
            @else
                <div class="mt-4 space-y-3">
                    @foreach($riskDist as $band => $count)
                        @php
                            $total = max(1, array_sum($riskDist));
                            $pct = round($count / $total * 100);
                            $bar = $riskColors[$band] ?? 'bg-gray-400';
                            $chip = $riskChips[$band] ?? 'bg-gray-100 text-gray-700';
                        @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-medium text-gray-700">{{ $band }}</span>
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $chip }}">{{ $count }} ({{ $pct }}%)</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full {{ $bar }}" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
