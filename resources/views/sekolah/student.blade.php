@extends('layouts.app')

@section('title', 'Portal Siswa — ' . $sekolah->brand)

@section('content')
<div x-data="{ detail: null, rescheduleId: null }" @keydown.escape.window="detail = null">
    <div class="flex items-center justify-between mb-1">
        <h1 class="text-xl font-bold text-pine-900">Halo, {{ $student->name }}</h1>
        @if($isPreview)
        <span class="text-[10px] font-semibold bg-terra-100 text-terra-800 rounded-lg px-2 py-1">Preview Super Admin</span>
        @endif
    </div>
    <p class="text-sm text-gray-500 mb-6">Semua sesi kamu bersifat rahasia — hanya kamu dan konsultan yang tahu isinya. Sekolah hanya melihat data agregat.</p>

    <div class="grid md:grid-cols-3 gap-4 mb-6">
        {{-- Kuota --}}
        <div class="portal-card p-5">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-2xl bg-pine-50 text-pine-700">
                    @include('perusahaan.partials.icon', ['name' => 'calendar'])
                </span>
                <div>
                    <div class="text-2xl font-bold text-pine-700">{{ max(0, $quota - $usedMonth - $scheduledMonth) }}<span class="text-sm font-normal text-gray-400"> / {{ $quota }}</span></div>
                    <div class="text-xs text-gray-500">Sisa sesi bulan ini</div>
                </div>
            </div>
            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden mt-3">
                <div class="h-full bg-pine-600 rounded-full transition-all duration-700" style="width: {{ min(100, round(($usedMonth + $scheduledMonth) / max(1, $quota) * 100)) }}%"></div>
            </div>
        </div>

        {{-- Wellbeing score --}}
        <div class="portal-card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500">Skor kesejahteraan (WHO-5)</p>
                    @if($assessment)
                    <p class="text-2xl font-bold mt-1 {{ $assessment->band() === 'Sehat' ? 'text-pine-700' : ($assessment->band() === 'Perlu Perhatian' ? 'text-terra-500' : 'text-red-600') }}">{{ $assessment->score }}<span class="text-sm text-gray-400">/100</span></p>
                    <p class="text-[11px] text-gray-400">{{ $assessment->band() }} · {{ $assessment->taken_at->translatedFormat('d M') }}</p>
                    @else
                    <p class="text-sm text-gray-500 mt-1">Belum ada</p>
                    @endif
                </div>
                <a href="{{ route('sekolah.assessment.form') }}" class="text-xs bg-pine-700 text-white px-3 py-1.5 rounded-lg">{{ $assessment ? 'Ulangi' : 'Isi' }}</a>
            </div>
            @if($needsAssessment)
            <p class="text-[11px] text-terra-700 mt-2">Asesmen terakhir &gt; 30 hari lalu — isi ulang ya.</p>
            @endif
        </div>

        {{-- Booking CTA --}}
        <a href="{{ route('sekolah.booking') }}" class="portal-card p-5 hover:shadow-md transition flex flex-col justify-between">
            <div>
                <span class="grid h-10 w-10 place-items-center rounded-2xl bg-pine-50 text-pine-700">
                    @include('perusahaan.partials.icon', ['name' => 'sparkles'])
                </span>
                <p class="text-sm font-semibold text-pine-900 mt-3">Booking konseling</p>
                <p class="text-xs text-gray-500">Pilih topik & jadwal. Online atau di ruang BK.</p>
            </div>
            <span class="text-xs font-semibold text-pine-700 mt-3">Buat jadwal →</span>
        </a>
    </div>

    {{-- Jadwal mendatang — termasuk yang dijadwalkan guru BK --}}
    @php
    $upcoming = $history->where('status', 'scheduled')->filter(fn ($h) => $h->scheduled_at->isFuture());
    @endphp
    @if($upcoming->isNotEmpty())
    <section class="portal-card p-5 mb-6 border-pine-300 bg-pine-50/40">
        <h3 class="dash-section-title">Jadwal konseling kamu</h3>
        <p class="text-xs text-gray-500 mt-1">@if($upcoming->contains(fn ($h) => $h->origin === 'bk_referral'))Sebagian dijadwalkan oleh guru BK kamu.@endif</p>
        <div class="mt-3 space-y-2">
            @foreach($upcoming as $h)
            <div class="flex items-center justify-between rounded-xl border border-pine-200 bg-white px-4 py-3">
                <div>
                    <p class="text-sm font-medium">{{ ucfirst($h->konsultan_type) }} · {{ ucfirst($h->mode) }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $h->scheduled_at->translatedFormat('l, d M Y, H.i') }}
                        @if($h->consultant?->user) · {{ $h->consultant->user->fullname }}@endif
                        @if($h->origin === 'bk_referral')
                        <span class="ml-1 text-[10px] font-semibold bg-terra-100 text-terra-800 rounded px-1.5 py-0.5">dijadwalkan guru BK</span>
                        @endif
                    </p>
                </div>
                <a href="{{ \App\Support\GCalLink::addLink('Konseling — ' . ucfirst($h->konsultan_type), $h->scheduled_at, 'Sesi konseling rahasia via program ' . $sekolah->brand . ($h->mode === 'offline' ? '. Lokasi: ruang BK sekolah.' : '. Link online dikirim mendekati jadwal.'), $h->mode === 'offline' ? 'Ruang BK ' . $sekolah->brand : null) }}"
                    target="_blank" rel="noopener"
                    class="shrink-0 text-xs font-semibold bg-blue-600 text-white rounded-lg px-3 py-1.5 hover:bg-blue-700 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19 3h-1V1h-2v2H8V1H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Zm0 16H5V9h14v10Zm0-12H5V5h14v2Z"/></svg>
                    Google Calendar
                </a>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Riwayat --}}
    <section class="portal-card p-5">
        <h3 class="dash-section-title">Riwayat sesi kamu</h3>
        <div class="mt-4 space-y-2">
            @forelse($history as $h)
            <div class="flex items-center justify-between rounded-xl border border-[#e7e2d9] px-4 py-3">
                <div>
                    <p class="text-sm font-medium">{{ ucfirst($h->konsultan_type) }} · {{ ucfirst($h->mode) }}</p>
                    <p class="text-xs text-gray-500">{{ $h->scheduled_at?->translatedFormat('d M Y, H.i') }}@if($h->consultant?->user) · {{ $h->consultant->user->fullname }}@endif</p>
                </div>
                @if($h->status === 'scheduled')
                <form method="POST" action="{{ route('sekolah.booking.cancel', $h) }}">@csrf
                    <button class="text-xs text-red-600 hover:underline">Batalkan</button>
                </form>
                @else
                <span class="text-xs font-semibold px-2 py-1 rounded-lg {{ $h->status === 'done' ? 'bg-pine-50 text-pine-700' : 'bg-gray-100 text-gray-500' }}">{{ ucfirst($h->status) }}</span>
                @endif
            </div>
            @empty
            <p class="text-sm text-gray-500">Belum ada sesi. Mulai dengan booking pertamamu.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
