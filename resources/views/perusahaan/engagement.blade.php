@extends('layouts.app')

@php
    $title = 'Keterlibatan Karyawan';
@endphp

@section('content')
<div class="max-w-5xl">
    <div class="mb-4">
        <h1 class="text-xl font-bold">Keterlibatan Karyawan</h1>
        <p class="text-sm text-gray-500">Agregat partisipasi bulan {{ $month ?: '-' }} — data individual tidak ditampilkan (n&lt;10 disembunyikan).</p>
    </div>

    @if($suppressed)
        <div class="dash-card p-6 text-center">
            <p class="text-sm text-gray-400">Belum cukup data (minimum 10 peserta) — agregat disembunyikan untuk menjaga privasi karyawan.</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-4">
            <div class="dash-card p-5">
                <p class="text-xs uppercase tracking-wide text-gray-500">Karyawan terdaftar</p>
                <p class="mt-1 text-2xl font-bold text-pine-900">{{ $registered }}</p>
            </div>
            <div class="dash-card p-5">
                <p class="text-xs uppercase tracking-wide text-gray-500">Tingkat partisipasi</p>
                <p class="mt-1 text-2xl font-bold text-pine-900">{{ $participation }}%</p>
            </div>
            <div class="dash-card p-5">
                <p class="text-xs uppercase tracking-wide text-gray-500">Sesi selesai (bulan ini)</p>
                <p class="mt-1 text-2xl font-bold text-pine-900">{{ $doneMonth }}</p>
            </div>
            <div class="dash-card p-5">
                <p class="text-xs uppercase tracking-wide text-gray-500">No-show</p>
                <p class="mt-1 text-2xl font-bold text-pine-900">{{ $noShow }}</p>
            </div>
        </div>
        <div class="dash-card p-5 mt-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">Rating rata-rata</p>
            <p class="mt-1 text-2xl font-bold text-pine-900">{{ $avgRating ?? '—' }}</p>
        </div>
    @endif

    <div class="dash-card p-5 mt-6">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-[0.95rem] font-semibold text-gray-900">Campaign terbaru</h2>
            <a href="{{ route('perusahaan.campaigns.index') }}" class="text-sm text-pine-700 hover:underline">Kelola campaign →</a>
        </div>
        @if($campaigns->isEmpty())
            <p class="text-sm text-gray-400">Belum ada campaign.</p>
        @else
            <div class="space-y-2">
                @foreach($campaigns as $c)
                <div class="panel-surface bg-white flex items-center justify-between text-sm">
                    <div class="min-w-0">
                        <p class="font-medium text-gray-700 truncate">{{ $c->title }}</p>
                        <p class="text-xs text-gray-400">{{ $c->channel === 'email' ? 'Email' : 'Pengumuman' }} · {{ optional($c->sent_at ?? $c->scheduled_at)->format('d M Y') }}</p>
                    </div>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $c->sent_at ? 'bg-pine-100 text-pine-800' : 'bg-gray-100 text-gray-500' }}">{{ $c->sent_at ? 'Terkirim' : 'Terjadwal' }}</span>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
