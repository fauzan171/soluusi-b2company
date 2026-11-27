@extends('layouts.app')

@php($title = 'Hasil '.$instrument?->name.' — Cetak')

@section('content')
<div class="max-w-2xl mx-auto">
    {{-- T3: print-friendly via @media print (public/build prebuilt — jangan
         andalkan utility class Tailwind baru; CSS inline). --}}
    <style>
        @media print {
            .print-hide { display: none !important; }
            body { background: #fff !important; }
        }
    </style>

    <div class="flex items-center justify-between mb-4 print-hide">
        <h1 class="text-xl font-bold">Hasil {{ $instrument?->name ?? 'Asesmen' }}</h1>
        <button type="button" onclick="window.print()"
                class="bg-pine-700 text-white text-sm font-semibold rounded px-4 py-2.5 hover:bg-pine-800">Unduh / Print PDF</button>
    </div>

    <div class="portal-card p-6">
        <div class="flex items-baseline justify-between">
            <div>
                <p class="text-sm font-bold">{{ $instrument?->name ?? 'Asesmen' }}</p>
                <p class="text-xs text-gray-500">{{ $employee->perusahaan?->brand ?? '' }}</p>
            </div>
            <span class="text-xs text-gray-400">{{ $assessment->taken_at->translatedFormat('d F Y') }}</span>
        </div>

        <div class="mt-4 text-center">
            <div class="text-4xl font-bold">{{ $assessment->score }}</div>
            <div class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-semibold bg-gray-100 text-gray-700">{{ $bandLabel }}</div>
            @if($bandNote)<p class="mt-3 text-sm text-gray-600">{{ $bandNote }}</p>@endif
        </div>

        @if($assessment->free_text)
        <div class="mt-4 border-t border-gray-100 pt-4">
            <p class="text-xs font-semibold text-gray-500 mb-1">Apa yang paling menekan Anda saat ini</p>
            <p class="text-sm text-gray-700">{{ $assessment->free_text }}</p>
        </div>
        @endif

        <p class="mt-4 border-t border-gray-100 pt-3 text-[11px] text-gray-400">
            Ini skrining awal, <b>bukan diagnosis</b>. Hasil ini milik Anda — HR hanya melihat agregat anonim.
        </p>
    </div>
</div>
@endsection
