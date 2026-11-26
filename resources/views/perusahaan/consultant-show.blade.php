@extends('layouts.app')

@php($title = 'Profil Konsultan')

@section('content')
<div class="max-w-3xl">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div class="flex items-start gap-4">
            @if($consultant->photo_url)
            <img src="{{ $consultant->photo_url }}" alt="" class="w-16 h-16 rounded-full object-cover bg-pine-100 shrink-0"
                onerror="this.replaceWith(Object.assign(document.createElement('div'),{className:'w-16 h-16 rounded-full bg-pine-100 flex items-center justify-center text-pine-700 font-bold text-xl shrink-0',textContent:'{{ strtoupper(substr($consultant->user?->firstname ?? 'K', 0, 1)) }}'}))">
            @else
            <div class="w-16 h-16 rounded-full bg-pine-100 flex items-center justify-center text-pine-700 font-bold text-xl shrink-0">
                {{ strtoupper(substr($consultant->user?->firstname ?? 'K', 0, 1)) }}
            </div>
            @endif
            <div>
                <h1 class="text-xl font-bold text-pine-900">{{ $consultant->user?->firstname }} {{ $consultant->user?->lastname }}</h1>
                @if($consultant->headline)
                <p class="text-sm text-pine-700 font-medium mt-0.5">{{ $consultant->headline }}</p>
                @endif
                <p class="text-sm text-gray-500 mt-1">
                    {{ $consultant->categories->pluck('name')->implode(', ') ?: 'Umum' }}
                </p>
            </div>
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

        @php($profile = $consultant->profile ?? [])
        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mt-4 mb-2">Pendidikan</h3>
        @if($consultant->educations->count())
        <ul class="text-sm text-gray-600 space-y-1">
            @foreach($consultant->educations as $edu)
            <li>{{ $edu->degree ?? '' }} — {{ $edu->education?->name ?? '' }} {{ $edu->end_date ?? '' }}</li>
            @endforeach
        </ul>
        @elseif(!empty($profile['education']))
        <ul class="text-sm text-gray-600 space-y-1">
            @foreach($profile['education'] as $edu)
            <li>{{ $edu['degree'] ?? '' }} — {{ $edu['institution'] ?? '' }}{{ $edu['department'] ? ' · '.$edu['department'] : '' }} {{ $edu['end'] ?? '' }}</li>
            @endforeach
        </ul>
        @else
        <p class="text-sm text-gray-400">—</p>
        @endif

        @if(!empty($profile['certificates']))
        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mt-4 mb-2">Sertifikasi</h3>
        <ul class="text-sm text-gray-600 space-y-1">
            @foreach($profile['certificates'] as $cert)
            <li>{{ $cert['name'] }} <span class="text-gray-400 text-xs">({{ $cert['year'] }})</span></li>
            @endforeach
        </ul>
        @endif

        @if(!empty($profile['experience']))
        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mt-4 mb-2">Pengalaman</h3>
        <ul class="text-sm text-gray-600 space-y-1">
            @foreach($profile['experience'] as $exp)
            <li>{{ $exp['position'] }} — {{ $exp['company'] }} <span class="text-gray-400 text-xs">({{ substr((string) $exp['start'], 0, 4) }}–{{ $exp['end'] ? substr((string) $exp['end'], 0, 4) : 'sekarang' }})</span></li>
            @endforeach
        </ul>
        @endif
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
        <a href="{{ route('perusahaan.booking', ['consultant_id' => $consultant->id]) }}"
           class="mt-4 inline-block rounded-lg bg-pine-700 text-white text-sm font-semibold px-4 py-2.5 hover:bg-pine-800 transition">
            Booking slot
        </a>
        @endif
    </div>
</div>
@endsection
