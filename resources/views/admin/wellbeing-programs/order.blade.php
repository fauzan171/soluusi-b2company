@extends('layouts.app')

@section('title', 'Program Order — '.$program->perusahaan->brand)

@section('content')
@php
    $package = $program->wellbeingPackage;
    $band = $program->pricingBand;
    $domainLabels = ['mh' => 'Mental Health', 'wellbeing' => 'Wellbeing', 'nutrition' => 'Nutrition'];
    $domains = collect($program->assessment_domains ?? [])->map(fn ($d) => $domainLabels[$d] ?? ucfirst($d))->implode(', ');
    $selectedCount = is_array($program->selected_employee_ids) ? count($program->selected_employee_ids) : null;
    $priceLabel = $program->monthly_price
        ? 'Rp ' . number_format($program->monthly_price, 0, ',', '.') . ' + pajak'
        : 'Gratis';
    $months = (int) round($package->duration_days / 30);
    // B2 (D4): add-on assignment berbayar per program — baris terpisah + total.
    $addons = \App\Models\AssessmentAssignment::where('assignable_type', $program->getMorphClass())
        ->where('assignable_id', $program->id)
        ->where('source', \App\Models\AssessmentAssignment::SOURCE_ADDON)
        ->whereNotNull('addon_price')
        ->orderBy('instrument_code')->get();
    $addonTotal = (float) $addons->sum('addon_price');
    $total = (float) $program->monthly_price * $months + $addonTotal;
    $hasTotal = $program->monthly_price || $addonTotal > 0;
    $totalLabel = $hasTotal
        ? 'Rp ' . number_format($total, 0, ',', '.') . ' + pajak'
        : null;
    // B2 (D3): instrumen core program (termasuk add-on gratis/extras).
    $coreInstruments = \App\Models\AssessmentAssignment::where('assignable_type', $program->getMorphClass())
        ->where('assignable_id', $program->id)
        ->where('source', \App\Models\AssessmentAssignment::SOURCE_CORE)
        ->orderBy('instrument_code')->get()
        ->map(fn ($a) => $a->instrument?->name ?? $a->instrument_code);
@endphp

<style>
    .order-sheet { border: 1px solid #e7e2d9; background: #fff; border-radius: 1.25rem; box-shadow: 0 1px 2px rgba(12,59,46,.04), 0 12px 28px rgba(12,59,46,.04); }
    .order-row { display: grid; grid-template-columns: 16rem 1fr; gap: 1rem; padding: .65rem 0; border-bottom: 1px solid #f0ece4; }
    .order-row dt { color: #66716c; font-size: .85rem; }
    .order-row dd { color: #1a2420; font-size: .85rem; font-weight: 600; }
    .order-sig { border: 1px solid #e7e2d9; border-radius: .75rem; padding: 1rem; min-height: 7rem; }
    @media print {
        aside, .no-print { display: none !important; }
        .order-sheet { box-shadow: none; border: 1px solid #ccc; }
        body { background: white; }
    }
</style>

<div class="space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <h1 class="text-xl font-bold">Program Order — {{ $program->perusahaan->brand }}</h1>
        <button type="button" onclick="window.print()" class="no-print rounded-xl border border-[#e7e2d9] bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">Cetak / PDF</button>
    </div>

    <section class="order-sheet p-6 md:p-8">
        <div class="flex items-start justify-between gap-4 border-b border-[#e7e2d9] pb-5">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[.18em] text-pine-700">Program Order</p>
                <h2 class="mt-1 text-2xl font-semibold tracking-tight text-pine-950">{{ $package->name }} — {{ $program->perusahaan->name }}</h2>
                <p class="mt-1 text-xs text-gray-500">Dokumen kontrak snapshot — dibuat {{ $program->created_at->translatedFormat('d M Y') }}</p>
            </div>
            <div class="text-right text-xs text-gray-500">
                <p>No. Order: <span class="font-semibold text-gray-700">#WB-{{ $program->id }}</span></p>
                <p>Status: <span class="font-semibold {{ $program->status === 'active' ? 'text-emerald-700' : 'text-gray-700' }}">{{ ucfirst($program->status) }}</span></p>
            </div>
        </div>

        <div class="mt-5 grid md:grid-cols-2 gap-x-10">
            <dl class="grid">
                <div class="order-row"><dt>Perusahaan</dt><dd>{{ $program->perusahaan->name }}</dd></div>
                <div class="order-row"><dt>Paket</dt><dd>{{ $package->name }}{{ $package->is_free ? ' (Gratis)' : '' }}</dd></div>
                <div class="order-row"><dt>Band karyawan</dt><dd>{{ $band ? number_format($band->band_min, 0, ',', '.') . '–' . number_format($band->band_max, 0, ',', '.') . ' karyawan' : '—' }}</dd></div>
                <div class="order-row"><dt>Jumlah karyawan terdaftar</dt><dd>{{ number_format($program->employee_band_size, 0, ',', '.') }} karyawan</dd></div>
                <div class="order-row"><dt>Harga per bulan</dt><dd>{{ $priceLabel }}</dd></div>
                @if ($totalLabel)
                    <div class="order-row"><dt>Total biaya kontrak</dt><dd>{{ $totalLabel }}{{ $program->monthly_price ? ' (' . $months . ' bulan' . ($addonTotal > 0 ? ' + add-on sekali bayar' : '') . ')' : ' (add-on)' }}</dd></div>
                @endif
                @forelse ($addons as $addon)
                    <div class="order-row"><dt>Add-on</dt><dd>Add-on: {{ $addon->instrument?->name ?? $addon->instrument_code }} — Rp {{ number_format((float) $addon->addon_price, 0, ',', '.') }} (sekali bayar, belum termasuk pajak)</dd></div>
                @empty
                    <div class="order-row"><dt>Add-on</dt><dd>— (layanan tambahan berbayar terpisah)</dd></div>
                @endforelse
            </dl>
            <dl class="grid">
                <div class="order-row"><dt>Kuota expert chat</dt><dd>{{ $program->expert_chat_quota }} sesi</dd></div>
                <div class="order-row"><dt>Kuota deep support</dt><dd>{{ $program->deep_support_quota }} sesi{{ $program->deep_support_mode === 'mhcu' ? ' (MHCU)' : ($program->deep_support_mode === 'one_on_one' ? ' (1-on-1)' : '') }}</dd></div>
                <div class="order-row"><dt>Kuota webinar</dt><dd>{{ $program->webinar_quota }} webinar</dd></div>
                <div class="order-row"><dt>Domain assessment</dt><dd>{{ $domains ?: '—' }}</dd></div>
                <div class="order-row"><dt>Instrumen core</dt><dd>{{ $coreInstruments->unique()->implode(', ') ?: '—' }}</dd></div>
                <div class="order-row"><dt>Karyawan terpilih (Discovery)</dt><dd>{{ $selectedCount !== null ? $selectedCount . ' karyawan' : 'Semua karyawan' }}</dd></div>
            </dl>
        </div>

        <div class="mt-5 grid md:grid-cols-2 gap-4">
            <dl class="grid">
                <div class="order-row"><dt>Periode program</dt><dd>{{ $program->starts_at->translatedFormat('d M Y') }} s.d. {{ $program->ends_at->translatedFormat('d M Y') }} ({{ $package->duration_days }} hari)</dd></div>
                <div class="order-row"><dt>Tanggal aktivasi</dt><dd>{{ $program->created_at->translatedFormat('d M Y') }}</dd></div>
            </dl>
            <dl class="grid">
                <div class="order-row"><dt>Ketentuan kuota</dt><dd>Kontrak-level — sisa kuota hangus saat berakhir, tidak dipindahtuangkan</dd></div>
                <div class="order-row"><dt>Terminasi</dt><dd>Pemberitahuan tertulis minimal 30 hari kalender</dd></div>
            </dl>
        </div>

        <p class="mt-6 text-xs leading-5 text-gray-500">Dokumen ini merupakan snapshot kontrak sesuai Syarat &amp; Ketentuan Layanan Wellbeing Perusahaan Soluusi. Harga belum termasuk pajak. Laporan agregat mengikuti threshold privasi minimal 10 responden.</p>

        <div class="mt-8 grid md:grid-cols-2 gap-8">
            <div>
                <p class="mb-2 text-xs font-semibold uppercase tracking-[.16em] text-gray-500">Untuk dan atas nama Perusahaan</p>
                <div class="order-sig">
                    <p class="text-xs text-gray-400">{{ $program->perusahaan->name }}</p>
                </div>
                <p class="mt-2 text-xs text-gray-500">( ................................................ )</p>
            </div>
            <div>
                <p class="mb-2 text-xs font-semibold uppercase tracking-[.16em] text-gray-500">PT Saya Soluusi Bangsa Indonesia</p>
                <div class="order-sig">
                    <p class="text-xs text-gray-400">Authorized Signatory</p>
                </div>
                <p class="mt-2 text-xs text-gray-500">( ................................................ )</p>
            </div>
        </div>
    </section>
</div>
@endsection
