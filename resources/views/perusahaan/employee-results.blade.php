@extends('layouts.app')

@php
    $title = 'Hasilku';
@endphp

@section('content')
<div class="max-w-3xl">
    <div class="mb-4">
        <h1 class="text-xl font-bold">Hasilku</h1>
        <p class="text-sm text-gray-500">Riwayat hasil asesmen wellbeing Anda.</p>
    </div>

    <div class="dash-card p-5">
        @if($results->isEmpty())
            <p class="text-sm text-gray-400">Belum ada hasil asesmen. Mulai dari menu Asesmenku.</p>
        @else
            <div class="space-y-2">
                @foreach($results as $r)
                <div class="panel-surface bg-white flex items-center justify-between text-sm">
                    <div class="min-w-0">
                        <p class="font-medium text-gray-700 truncate">{{ $r['instrument'] }}</p>
                        <p class="text-xs text-gray-400">{{ optional($r['taken_at'])->format('d M Y, H:i') ?? '-' }}</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold bg-gray-100 text-gray-700">{{ $r['band'] }}</span>
                        <a href="{{ route('perusahaan.assessment.result', $r['id']) }}" class="text-xs text-pine-700 hover:underline">Detail</a>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
