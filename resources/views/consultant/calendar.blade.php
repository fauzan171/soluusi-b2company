@extends('layouts.app')

@php($title = 'Kalender Saya')

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-xl font-bold text-pine-900">Kalender Saya</h1>
        <p class="text-sm text-gray-500 mt-1">Slot terbuka + sesi terjadwal lintas perusahaan, 14 hari ke depan.</p>
    </div>
    <a href="{{ route('consultant.sessions') }}" class="text-sm text-pine-700 font-medium hover:underline">&larr; Sesi Saya</a>
</div>

{{-- Filter per perusahaan --}}
@if($perusahaans->count() > 1)
<form method="GET" action="{{ route('consultant.calendar') }}" class="mb-6">
    <select name="perusahaan_id" class="rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white" onchange="this.form.submit()">
        <option value="">Semua perusahaan</option>
        @foreach($perusahaans as $p)
        <option value="{{ $p->id }}" @selected($selected?->id === $p->id)>{{ $p->brand }}</option>
        @endforeach
    </select>
</form>
@endif

<div class="grid lg:grid-cols-2 gap-6">
    <section class="portal-card p-6">
        <h2 class="text-sm font-semibold text-ink mb-3">Slot terbuka</h2>
        @if($slots->isEmpty())
        <p class="text-sm text-gray-400">Tidak ada slot terbuka (atau template jam kerja belum diatur admin).</p>
        @else
        <ul class="divide-y divide-[#f1ede4]">
            @foreach($slots as $s)
            <li class="py-2.5 flex items-center justify-between gap-3 text-sm">
                <span class="text-ink font-medium">{{ \Illuminate\Support\Carbon::parse($s['start'])->translatedFormat('D, d M') }}</span>
                <span class="text-gray-600">{{ \Illuminate\Support\Carbon::parse($s['start'])->format('H.i') }}–{{ \Illuminate\Support\Carbon::parse($s['end'])->format('H.i') }}</span>
                <span class="text-xs px-2 py-0.5 rounded-full bg-pine-100 text-pine-800 font-medium">{{ $s['perusahaan'] }}</span>
            </li>
            @endforeach
        </ul>
        @endif
    </section>

    <section class="portal-card p-6">
        <h2 class="text-sm font-semibold text-ink mb-3">Sesi terjadwal</h2>
        @if($booked->isEmpty())
        <p class="text-sm text-gray-400">Belum ada sesi terjadwal.</p>
        @else
        <ul class="divide-y divide-[#f1ede4]">
            @foreach($booked as $ses)
            <li class="py-2.5 flex items-center justify-between gap-3 text-sm">
                <span class="text-ink font-medium">{{ $ses->scheduled_at?->translatedFormat('D, d M H.i') }}</span>
                <span class="text-xs px-2 py-0.5 rounded-full {{ $ses->status === 'done' ? 'bg-terra-100 text-terra-800' : 'bg-pine-100 text-pine-800' }} font-medium">
                    {{ $ses->perusahaan?->brand ?? '—' }} · {{ ucfirst($ses->status) }}
                </span>
            </li>
            @endforeach
        </ul>
        @endif
    </section>
</div>
@endsection
