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

    @if($dimensions)
    <div class="portal-card p-6 mt-4">
        <h2 class="text-sm font-bold mb-4">Rincian per Dimensi</h2>
        <div class="space-y-3">
            @foreach($dimensions as $dim)
            <div class="flex items-center justify-between border-b border-gray-100 pb-3 last:border-0 last:pb-0">
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ $dim['label'] }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $dim['band']['note'] }}</p>
                </div>
                <div class="text-right">
                    <span class="text-lg font-bold {{ $dim['band']['label'] === 'Normal' || $dim['band']['label'] === 'Rendah' || $dim['band']['label'] === 'Baik' ? 'text-pine-700' : ($dim['band']['label'] === 'Ringan' || $dim['band']['label'] === 'Sedang' ? 'text-terra-500' : 'text-red-600') }}">{{ $dim['score'] }}</span>
                    <span class="block text-xs font-semibold text-gray-500">{{ $dim['band']['label'] }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="portal-card p-4 mt-4">
        <h2 class="text-sm font-bold mb-2">Rekomendasi untuk Kamu</h2>
        <ul class="space-y-1.5 text-sm text-gray-700">
            @foreach($dimensions as $dim)
                @php($domainKey = $instrument?->dimensionDefinition($dim['key'])['domain'] ?? $instrument?->domain)
                @if(in_array($dim['band']['label'], ['Sedang', 'Berat', 'Sangat Berat', 'Tinggi', 'Perlu Perhatian', 'Perlu Intervensi']))
                <li class="flex gap-2"><span class="text-terra-500">•</span>
                    <span><b>{{ $dim['label'] }} ({{ $dim['band']['label'] }})</b> — {{ \App\Support\DomainRecommendation::for($domainKey ?? 'mh', $dim['label']) }}</span>
                </li>
                @endif
            @endforeach
            @if(! collect($dimensions)->pluck('band.label')->intersect(['Sedang', 'Berat', 'Sangat Berat', 'Tinggi', 'Perlu Perhatian', 'Perlu Intervensi'])->count())
                <li class="flex gap-2"><span class="text-pine-500">•</span>
                    <span>Semua dimensi dalam rentang baik — pertahankan kebiasaan dan rutinitas positif kamu.</span>
                </li>
            @endif
        </ul>
    </div>
    @endif

    @if($assessment->notes)
    <div class="portal-card p-4 mt-4">
        <h2 class="text-sm font-bold mb-2">Catatan Diskusi Kamu</h2>
        <p class="text-sm text-gray-700">{{ $assessment->notes }}</p>
        <p class="text-xs text-gray-400 mt-2">Topik ini hanya kamu dan konsultan yang melihatnya saat sesi dimulai.</p>
    </div>
    @endif

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
