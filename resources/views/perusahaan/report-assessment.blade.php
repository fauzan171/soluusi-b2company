@extends('layouts.app')

@php
    $title = 'Agregat Asesmen';
@endphp

@section('content')
<div class="max-w-5xl">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-xl font-bold">Agregat Asesmen Kesejahteraan</h1>
            <p class="text-sm text-gray-500">Distribusi band per instrumen — data individu tidak pernah ditampilkan (n&lt;10 disembunyikan).</p>
        </div>
        <a href="{{ route('perusahaan.report') }}" class="text-sm text-pine-700 hover:underline">← Laporan utama</a>
    </div>

    <div class="space-y-4">
        @foreach($instruments as $row)
        @php
            $instrument = $row['instrument'];
            $agg = $row['aggregate'];
        @endphp
        <div class="dash-card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-[0.95rem] font-semibold text-gray-900">{{ $instrument->name }}</h3>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $agg['assessed'] }} karyawan terasesmen · {{ $instrument->interpretation }}</p>
                </div>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-[11px] font-medium text-gray-500">Agregat saja</span>
            </div>
            <div class="mt-4 space-y-3">
                @if($agg['bands'] === null)
                    <p class="rounded-3xl bg-gray-50 px-4 py-6 text-center text-sm text-gray-400">Belum cukup data (minimum 10 responden) — agregat disembunyikan untuk menjaga privasi.</p>
                @else
                    @foreach($agg['bands'] as $band => $count)
                    <div class="panel-surface bg-white">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700">{{ $band }}</span>
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold bg-gray-100 text-gray-700">{{ $count }}</span>
                        </div>
                    </div>
                    @endforeach
                    <div class="flex items-center justify-between text-sm pt-2 border-t border-gray-100">
                        <span class="text-gray-500">Butuh eskalasi (item khusus)</span>
                        <span class="font-semibold {{ $agg['escalatedCount'] > 0 ? 'text-red-600' : 'text-gray-700' }}">Eskalasi: {{ $agg['escalatedCount'] }}</span>
                    </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
