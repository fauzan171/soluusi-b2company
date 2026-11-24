@extends('layouts.app')

@php
    $title = 'Agregat Asesmen';
@endphp

@section('content')
<div class="max-w-5xl">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-xl font-bold">Agregat Asesmen Kesejahteraan</h1>
            <p class="text-sm text-gray-500">Per assignment {{ $program?->wellbeingPackage?->name ?? 'perusahaan' }} — data individu tidak pernah ditampilkan (n&lt;10 disembunyikan).</p>
        </div>
        <a href="{{ route('perusahaan.report') }}" class="text-sm text-pine-700 hover:underline">← Laporan utama</a>
    </div>

    @if($sections->isEmpty())
        <div class="dash-card p-6 text-center">
            <p class="text-sm text-gray-500">Belum ada assignment asesmen aktif.</p>
        </div>
    @endif

    <div class="space-y-4">
        @foreach($sections as $section)
        @php
            $instrument = $section['instrument'];
            $assignment = $section['assignment'];
        @endphp
        <div class="dash-card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-[0.95rem] font-semibold text-gray-900">{{ $instrument->name }}</h3>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $instrument->interpretation }}</p>
                </div>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-[11px] font-medium text-gray-500">Agregat saja</span>
            </div>

            {{-- Completion vs headcount: bukan data kesehatan — selalu tampil. --}}
            <div class="mt-4 flex items-center justify-between text-sm">
                <span class="text-gray-500">Penyelesaian (karyawan unik)</span>
                <span class="font-semibold text-gray-900">{{ $section['completers'] }} / {{ $section['registered'] }} ({{ $section['pct'] }}%)</span>
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100">
                <div class="h-full rounded-full bg-pine-600" style="width: {{ min(100, $section['pct']) }}%"></div>
            </div>

            @if(count($section['rounds']) > 1)
                <p class="mt-3 text-xs text-gray-400">{{ count($section['rounds']) }} putaran · periode {{ optional($assignment->opens_at)->translatedFormat('d M Y') ?? '-' }} — {{ optional($assignment->closes_at)->translatedFormat('d M Y') ?? 'berjalan' }}</p>
            @endif

            <div class="mt-4 space-y-3">
                @foreach($section['rounds'] as $row)
                @php
                    $agg = $row['aggregate'];
                    $roundLabel = count($section['rounds']) > 1 ? ' · Round '.$row['round'] : '';
                @endphp
                    @if($agg['bands'] === null)
                        <p class="rounded-3xl bg-gray-50 px-4 py-6 text-center text-sm text-gray-400{{ $roundLabel }}">Data belum cukup untuk ditampilkan{{ $roundLabel }}</p>
                    @else
                        @foreach($agg['bands'] as $band => $count)
                        <div class="panel-surface bg-white">
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium text-gray-700">{{ $band }}{{ $roundLabel }}</span>
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold bg-gray-100 text-gray-700">{{ $count }}</span>
                            </div>
                        </div>
                        @endforeach
                        <div class="flex items-center justify-between text-sm pt-2 border-t border-gray-100">
                            <span class="text-gray-500">Butuh eskalasi (item khusus){{ $roundLabel }}</span>
                            <span class="font-semibold {{ $agg['escalatedCount'] > 0 ? 'text-red-600' : 'text-gray-700' }}">Eskalasi: {{ $agg['escalatedCount'] ?? '<10 (disembunyikan)' }}</span>
                        </div>
                    @endif
                @endforeach

                @if($section['trend'] !== null)
                    @php
                        $trendText = collect($section['trend'])->map(fn ($p) => 'Round '.$p['round'].': '.$p['mean'])->implode(' → ');
                    @endphp
                    <div class="rounded-3xl bg-pine-50 px-4 py-3 text-sm">
                        <p class="font-medium text-pine-900">Rata-rata skor per round:</p>
                        <p class="mt-1 text-pine-800">{{ $trendText }}</p>
                        <p class="mt-1 text-xs text-gray-500">Perbedaan skor antarperiode tidak otomatis menunjukkan bahwa program menyebabkan perubahan.</p>
                    </div>
                @elseif($section['trendSuppressed'])
                    <p class="rounded-3xl bg-gray-50 px-4 py-3 text-center text-sm text-gray-400">Tren antar-round disembunyikan — tiap round butuh minimal 10 responden.</p>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
