@extends('layouts.app')

@php
    $title = 'Progres Program';
@endphp

@section('content')
<div class="max-w-4xl">
    <div class="mb-4">
        <h1 class="text-xl font-bold">Progres Program</h1>
        <p class="text-sm text-gray-500">Pemanfaatan program aktif — {{ $program?->wellbeingPackage?->name ?? '-' }}, berakhir {{ optional($program?->ends_at)->translatedFormat('d M Y') ?? '-' }}.</p>
    </div>

    @if(!$program)
        <div class="dash-card p-6 text-center">
            <p class="text-sm text-gray-500">Belum ada program Wellbeing aktif.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($progressRows as $row)
            @php
                $pct = $row['total'] > 0 ? min(100, (int) round($row['used'] / $row['total'] * 100)) : 0;
            @endphp
            <div class="dash-card p-5">
                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="font-medium text-gray-700">{{ $row['label'] }}</span>
                    <span class="text-gray-500">{{ $row['used'] }} / {{ $row['total'] }} {{ $row['unit'] }} ({{ $pct }}%)</span>
                </div>
                <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                    <div class="h-full rounded-full bg-pine-600" style="width: {{ $pct }}%"></div>
                </div>
            </div>
            @endforeach

            @if(count($assignmentRows) > 0)
                <div class="dash-card p-5">
                    <h2 class="text-[0.95rem] font-semibold text-gray-900">Asesmen per assignment</h2>
                    <p class="mt-1 text-xs text-gray-400">Penyelesaian per assignment vs jumlah karyawan terdaftar — bukan data kesehatan.</p>
                    <div class="mt-4 space-y-4">
                        @foreach($assignmentRows as $row)
                            <div>
                                <div class="mb-1 flex items-center justify-between text-sm">
                                    <span class="font-medium text-gray-700">{{ $row['instrument'] }}@if($row['round'] > 1) · Round {{ $row['round'] }}@endif</span>
                                    <span class="text-gray-500">{{ $row['completers'] }} / {{ $row['registered'] }} karyawan ({{ $row['pct'] }}%)</span>
                                </div>
                                <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                                    <div class="h-full rounded-full bg-pine-600" style="width: {{ min(100, $row['pct']) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        @if($prePost !== null)
        <div class="dash-card p-5 mt-6">
            <h2 class="text-[0.95rem] font-semibold text-gray-900">Perbandingan pre/post asesmen</h2>
            <p class="mt-1 text-xs text-gray-400">Paket Impact — perbandingan deskriptif skor asesmen pertama vs terakhir per karyawan. Bukan klaim kausal.</p>
            <div class="mt-3 text-sm">
                @if($prePost['suppressed'])
                    <p class="text-gray-400">Belum cukup data (minimum 10 karyawan dengan ≥2 asesmen) — agregat disembunyikan untuk menjaga privasi.</p>
                @else
                    <p>Rata-rata perubahan skor: <strong class="{{ $prePost['avgDelta'] >= 0 ? 'text-pine-700' : 'text-red-600' }}">{{ $prePost['avgDelta'] >= 0 ? '+' : '' }}{{ $prePost['avgDelta'] }}</strong> (n={{ $prePost['n'] }} karyawan, deskriptif — bukan klaim kausal).</p>
                @endif
            </div>
        </div>
        @endif
    @endif
</div>
@endsection
