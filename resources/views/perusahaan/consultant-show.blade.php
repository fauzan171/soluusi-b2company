@extends('layouts.app')

@php($title = 'Profil Konsultan')

@section('content')
<div class="max-w-3xl">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <h1 class="text-xl font-bold text-pine-900">{{ $consultant->user?->firstname }} {{ $consultant->user?->lastname }}</h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ $consultant->categories->pluck('name')->implode(', ') ?: 'Umum' }}
            </p>
        </div>
        <a href="{{ route('perusahaan.consultants') }}" class="text-sm text-pine-700 font-medium hover:underline">&larr; Direktori konsultan</a>
    </div>

    <div class="portal-card p-6 mb-6">
        <h2 class="text-sm font-semibold text-ink mb-2">Tentang konsultan</h2>
        <p class="text-sm text-gray-600">{{ $consultant->about }}</p>

        <div class="grid sm:grid-cols-2 gap-4 mt-4 text-sm">
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Kategorisasi</h3>
                <p class="text-gray-700">{{ $consultant->categories->pluck('name')->implode(', ') ?: '—' }}</p>
            </div>
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Bahasa / keahlian</h3>
                <p class="text-gray-700">{{ $skills->implode(', ') ?: '—' }}</p>
            </div>
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Tipe konsultasi</h3>
                <p class="text-gray-700">
                    @foreach($workHours as $wh)
                        @if($wh->consultation_types)
                            {{ implode(', ', $wh->consultation_types) }}
                        @else
                            Chat, Online meeting, One-on-one
                        @endif
                    @endforeach
                    @if($workHours->isEmpty()) — @endif
                </p>
            </div>
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Rating</h3>
                <p class="text-gray-700">
                    @if($ratingAvg)
                        {{ number_format($ratingAvg, 1) }} / 5
                        <span class="text-xs text-gray-400">· {{ $ratingCount }} ulasan dari sesi selesai</span>
                    @else
                        Belum ada rating
                    @endif
                </p>
            </div>
        </div>

        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mt-4 mb-2">Pendidikan</h3>
        <ul class="text-sm text-gray-600 space-y-1">
            @forelse($consultant->educations as $edu)
            <li>{{ $edu->degree ?? '' }} — {{ $edu->institution ?? '' }} {{ $edu->end_date ?? '' }}</li>
            @empty
            <li class="text-gray-400">—</li>
            @endforelse
        </ul>
    </div>

    <div class="portal-card p-6">
        <h2 class="text-sm font-semibold text-ink mb-3">Jadwal 7 hari ke depan</h2>
        @if($slots->isEmpty())
        <p class="text-sm text-gray-400">Belum ada slot terbuka minggu ini.</p>
        @else
        <ul class="divide-y divide-[#f1ede4]">
            @foreach($slots as $s)
            <li class="py-2.5 flex items-center justify-between gap-3 text-sm">
                <span class="text-ink font-medium">{{ \Illuminate\Support\Carbon::parse($s->start)->translatedFormat('l, d M Y') }}</span>
                <span class="text-gray-600">{{ \Illuminate\Support\Carbon::parse($s->start)->format('H.i') }}–{{ \Illuminate\Support\Carbon::parse($s->end)->format('H.i') }} WIB</span>
            </li>
            @endforeach
        </ul>
        <a href="{{ route('perusahaan.booking') }}"
           class="mt-4 inline-block rounded-lg bg-pine-700 text-white text-sm font-semibold px-4 py-2.5 hover:bg-pine-800 transition">
            Booking slot
        </a>
        @endif
    </div>
</div>
@endsection
