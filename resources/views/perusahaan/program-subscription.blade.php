@extends('layouts.app')

@section('title', 'Program & Subscription — '.$perusahaan->brand)

@section('content')
@php
    // Style block copy-paste pola perusahaan/dashboard.blade.php:27-40 (ux §6, no component extraction).
@endphp

<style>
    .dash-card {
        border: 1px solid #e7e2d9;
        background: #fff;
        border-radius: 1.25rem;
        box-shadow: 0 1px 2px rgba(12, 59, 46, 0.04), 0 12px 28px rgba(12, 59, 46, 0.04);
    }
    .dash-section-title { font-size: .95rem; font-weight: 600; color: #1a2420; }
    .dash-section-subtitle { margin-top: .25rem; font-size: .75rem; color: #6b7280; }
    .panel-surface {
        border-radius: 1rem;
        border: 1px solid #e7e2d9;
        background: linear-gradient(180deg, #fdfcfa, #f7f4ee);
        padding: 1rem;
    }
</style>

<div class="max-w-6xl space-y-4">

    {{-- Empty state: belum ada paket aktif (pola perusahaan/pending.blade.php) --}}
    @if(!$program)
    <div class="max-w-lg mx-auto mt-12 bg-white rounded-xl border p-8 text-center">
        <div class="w-12 h-12 mx-auto rounded-full bg-amber-100 flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h1 class="text-xl font-bold mb-2">Program belum aktif</h1>
        <p class="text-sm text-gray-500 leading-relaxed">
            Wellbeing program {{ $perusahaan->brand }} belum memiliki paket aktif.
            Hubungi tim Soluusi untuk memilih paket yang sesuai untuk perusahaan Anda.
        </p>
    </div>
    @else

    {{-- Hero band gradient pine (pola perusahaan/booking.blade.php:6-23) --}}
    <section class="overflow-hidden rounded-[1.35rem] bg-gradient-to-br from-pine-950 via-pine-900 to-pine-700 px-5 py-6 text-white shadow-[0_22px_60px_rgba(6,42,32,.18)] sm:px-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-pine-200">Program &amp; Subscription</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Paket aktif: {{ $program->wellbeingPackage->name }}</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-pine-50/80">Paket aktif perusahaan {{ $perusahaan->brand }}, sisa kuota layanan, dan masa berlaku program.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-sm text-pine-50">
                    Berlaku: <span class="font-semibold">{{ $program->starts_at->translatedFormat('j M Y') }} – {{ $program->ends_at->translatedFormat('j M Y') }}</span>
                    <span class="block text-xs text-pine-50/70 mt-0.5">{{ $daysLeft }} hari tersisa</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Near-expiry alert strip ≤30 hari (pola dashboard.blade.php:92-100, tone netral) --}}
    @if($nearExpiry)
    <div class="flex items-center gap-3 rounded-2xl border bg-terra-50 border-terra-200 text-terra-800 px-4 py-3 text-sm" role="alert">
        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-terra-100">
            @include('perusahaan.partials.icon', ['name' => 'clock'])
        </span>
        Program {{ $program->wellbeingPackage->name }} berakhir dalam {{ $daysLeft }} hari ({{ $program->ends_at->translatedFormat('j M Y') }}).
        Hubungi account manager Soluusi untuk perpanjangan — kuota tersisa tidak dipindahtuangkan ke periode berikutnya.
    </div>
    @endif

    {{-- 3 stat card kuota sisa program (pola dashboard.blade.php:10-22) --}}
    <section class="grid gap-3 sm:grid-cols-3">
        @foreach($buckets as $b)
        @php
            $left = max(0, $b['total'] - $b['used']);
            $pct = $b['total'] > 0 ? (int) round($b['used'] / $b['total'] * 100) : 0;
        @endphp
        <div class="dash-card p-5">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-full bg-pine-50 text-pine-700">
                    @include('perusahaan.partials.icon', ['name' => $b['icon']])
                </span>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $b['label'] }}</p>
                    <p class="text-2xl font-bold text-pine-900">{{ $b['used'] }} / {{ $b['total'] }}{{ $b['unit'] ? ' '.$b['unit'] : '' }}</p>
                </div>
            </div>
            <div class="mt-4 h-3 overflow-hidden rounded-full bg-gray-100">
                <div class="h-full rounded-full bg-gradient-to-r from-pine-700 via-pine-500 to-terra-400" style="width: {{ $pct }}%"></div>
            </div>
            <p class="mt-2 text-xs text-gray-500">Sisa kuota program: <span class="font-semibold {{ $pct >= 80 ? 'text-terra-700' : 'text-pine-700' }}">{{ $left }}{{ $b['unit'] ? ' '.$b['unit'] : '' }}</span></p>
        </div>
        @endforeach
    </section>

    {{-- Ringkasan komponen (dash-card + panel-surface) --}}
    <section class="dash-card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="dash-section-title">Ringkasan Program {{ $program->wellbeingPackage->name }}</h3>
                <p class="dash-section-subtitle">Komponen dan kuota snapshot kontrak — tidak berubah meski katalog paket diperbarui.</p>
            </div>
        </div>
        <div class="mt-5 panel-surface">
            <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                <div class="flex items-baseline justify-between gap-4">
                    <dt class="text-gray-500">Asesmen</dt>
                    <dd class="font-semibold text-pine-900">{{ $program->assessment_count }} domain</dd>
                </div>
                <div class="flex items-baseline justify-between gap-4">
                    <dt class="text-gray-500">Chat Expert</dt>
                    <dd class="font-semibold text-pine-900">{{ $buckets[0]['total'] }} sesi</dd>
                </div>
                <div class="flex items-baseline justify-between gap-4">
                    <dt class="text-gray-500">Sesi dukungan mendalam</dt>
                    <dd class="font-semibold text-pine-900">{{ $buckets[1]['total'] }} sesi</dd>
                </div>
                <div class="flex items-baseline justify-between gap-4">
                    <dt class="text-gray-500">Webinar</dt>
                    <dd class="font-semibold text-pine-900">{{ $buckets[2]['total'] }}</dd>
                </div>
                <div class="flex items-baseline justify-between gap-4">
                    <dt class="text-gray-500">Review kuartalan</dt>
                    <dd class="font-semibold text-pine-900">{{ $program->quarterly_review ? 'Termasuk' : '—' }}</dd>
                </div>
                <div class="flex items-baseline justify-between gap-4">
                    <dt class="text-gray-500">Biaya</dt>
                    <dd class="font-semibold text-pine-900">
                        @if($program->monthly_price)
                        Rp {{ number_format($program->monthly_price, 0, ',', '.') }}/bulan
                        <span class="block text-xs font-normal text-gray-500">exclude pajak</span>
                        @else
                        Gratis
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
        <p class="mt-4 text-xs text-gray-500">
            <a href="#" class="text-pine-700 font-medium hover:underline">Syarat &amp; Ketentuan program</a> — rincian lengkap kontrak tersedia dari account manager Anda.
        </p>
    </section>
    @endif
</div>
@endsection
