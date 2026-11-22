@extends('layouts.app')

@php($title = 'Hasil Asesmen')

@section('content')
<div class="max-w-3xl">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-xl font-bold">Hasil {{ $instrument?->name ?? 'Asesmen' }}</h1>
            <p class="text-sm text-gray-500">{{ $assessment->taken_at->translatedFormat('d F Y') }}</p>
        </div>
        <a href="{{ route('perusahaan.assessment.instruments') }}" class="text-sm text-pine-700 hover:underline">← Kembali</a>
    </div>

    <div class="portal-card p-6 text-center">
        <div class="text-5xl font-bold {{ $bandLabel === 'Sehat' || $bandLabel === 'Minimal' || $bandLabel === 'Rendah' ? 'text-pine-700' : ($bandLabel === 'Perlu Perhatian' || $bandLabel === 'Ringan' || $bandLabel === 'Sedang' ? 'text-terra-500' : 'text-red-600') }}">{{ $assessment->score }}</div>
        <div class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $bandLabel === 'Sehat' || $bandLabel === 'Minimal' || $bandLabel === 'Rendah' ? 'bg-pine-100 text-pine-800' : ($bandLabel === 'Perlu Perhatian' || $bandLabel === 'Ringan' || $bandLabel === 'Sedang' ? 'bg-terra-100 text-terra-800' : 'bg-red-100 text-red-800') }}">{{ $bandLabel }}</div>
        @if($bandNote)<p class="mt-3 text-sm text-gray-600">{{ $bandNote }}</p>@endif
    </div>

    @if($escalated)
    <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
        <p class="font-semibold">Penting: salah satu jawaban kamu menandakan kesulitan serius.</p>
        <p class="mt-1">Kami sangat menyarankan kamu bicara dengan profesional sesegera mungkin. Hubungi konsultan lewat fitur <a href="{{ route('perusahaan.booking') }}" class="underline font-semibold">booking</a> atau hotline krisis <b>119 ext. 8</b> (24 jam). Kamu tidak sendirian.</p>
    </div>
    @endif

    <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs text-blue-800">
        Ini skrining awal, <b>bukan diagnosis</b>. Hasil hanya untuk kamu — HR melihat agregat anonim. {{ $instrument?->interpretation }}
    </div>

    <div class="mt-4 flex gap-3">
        <a href="{{ route('perusahaan.assessment.instruments') }}" class="flex-1 text-center bg-pine-700 text-white text-sm font-semibold rounded px-4 py-2.5 hover:bg-pine-800">Kerjakan instrumen lain</a>
        <a href="{{ route('perusahaan.employee') }}" class="flex-1 text-center portal-card text-sm font-semibold text-pine-700 rounded px-4 py-2.5">Kembali ke beranda</a>
    </div>
</div>
@endsection
