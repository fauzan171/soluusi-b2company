@extends('layouts.app')

@section('content')
@php
    $checkpointPct = min(100, round($completedCount / max(1, $stageTarget) * 100));
    $toneClasses = [
        'pine' => ['chip' => 'bg-pine-100 text-pine-800', 'icon' => 'bg-pine-50 text-pine-700', 'text' => 'text-pine-700'],
        'terra' => ['chip' => 'bg-terra-100 text-terra-800', 'icon' => 'bg-terra-50 text-terra-600', 'text' => 'text-terra-700'],
        'red' => ['chip' => 'bg-red-100 text-red-800', 'icon' => 'bg-red-50 text-red-600', 'text' => 'text-red-700'],
        'slate' => ['chip' => 'bg-gray-100 text-gray-700', 'icon' => 'bg-gray-100 text-gray-600', 'text' => 'text-gray-900'],
    ];
    $statCards = [
        ['label' => 'Total penjualan', 'value' => 'Rp' . number_format($grossSales, 0, ',', '.'), 'icon' => 'receipt', 'tone' => 'slate', 'detail' => 'gross sebelum potongan'],
        ['label' => 'Sesi terjadwal', 'value' => $scheduledCount, 'icon' => 'calendar', 'tone' => 'terra', 'detail' => 'menunggu pelaksanaan'],
        ['label' => 'Pendapatan bersih', 'value' => 'Rp' . number_format($marginSettled, 0, ',', '.'), 'icon' => 'check', 'tone' => 'pine', 'detail' => 'sudah settled'],
        ['label' => 'Menunggu payout', 'value' => 'Rp' . number_format($marginPending, 0, ',', '.'), 'icon' => 'arrow-trending-up', 'tone' => 'terra', 'detail' => 'margin belum cair'],
    ];
@endphp

<style>
    html { scroll-behavior: smooth; }
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
    .hero-band {
        border-radius: 1.25rem;
        background: linear-gradient(135deg, #062a20 0%, #0c3b2e 45%, #007648 100%);
        color: white;
        overflow: hidden;
        position: relative;
    }
    .hero-band::after {
        content: "";
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at top right, rgba(255,255,255,0.14), transparent 24%),
            linear-gradient(90deg, transparent, rgba(255,255,255,0.03));
        pointer-events: none;
    }
    .hero-metric {
        border-radius: 1rem;
        border: 1px solid rgba(255,255,255,0.12);
        background: rgba(255,255,255,0.08);
        padding: 1rem;
        position: relative;
        z-index: 1;
    }
    .chart-wrap { position: relative; height: 190px; }
    .chart-wrap--donut { position: relative; height: 190px; }
    .table-dashed th { font-size: .68rem; text-transform: uppercase; letter-spacing: .08em; color: #94a3b8; font-weight: 600; padding: .75rem 1rem; text-align: left; }
    .table-dashed td { padding: .8rem 1rem; border-top: 1px dashed #e7e2d9; font-size: .84rem; color: #334155; vertical-align: middle; }
    .fade-up { animation: fadeUp .55s ease both; }
    .fade-up:nth-child(2) { animation-delay: .04s; }
    .fade-up:nth-child(3) { animation-delay: .08s; }
    .fade-up:nth-child(4) { animation-delay: .12s; }
    .fade-up:nth-child(5) { animation-delay: .16s; }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="space-y-4">

    {{-- Panel ops super admin — invisible bagi admin/staff klinik (white-label). --}}
    @if($isSuperAdmin)
    <section class="fade-up dash-card p-5 border-indigo-200" style="border-color:#c7d2fe;">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-2xl bg-indigo-50 text-indigo-700">
                    @include('perusahaan.partials.icon', ['name' => 'shield'])
                </span>
                <div>
                    <h3 class="dash-section-title text-indigo-900">Mode Super Admin — kelola mitra</h3>
                    <p class="dash-section-subtitle">Info internal Soluusi; tidak pernah terlihat oleh klinik.</p>
                </div>
            </div>
            <div class="flex gap-2 text-xs">
                <a href="{{ route('admin.kliniks.packages.index', $klinik) }}" class="rounded-full bg-indigo-700 px-3 py-1.5 font-semibold text-white hover:bg-indigo-800">Kelola paket</a>
                <a href="{{ route('admin.kliniks.markups.index', $klinik) }}" class="rounded-full border border-indigo-200 px-3 py-1.5 font-semibold text-indigo-700 hover:bg-indigo-50">Atur markup</a>
                <a href="{{ route('admin.referral-overview') }}" class="rounded-full border border-indigo-200 px-3 py-1.5 font-semibold text-indigo-700 hover:bg-indigo-50">Referral HPA</a>
            </div>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-5">
            <div class="panel-surface bg-white">
                <p class="text-[11px] uppercase tracking-[0.14em] text-gray-400">HPA</p>
                <p class="mt-1.5 text-sm font-semibold text-gray-900">{{ $klinik->associate ? trim($klinik->associate->firstname . ' ' . $klinik->associate->lastname) : '— belum ditugaskan' }}</p>
            </div>
            <div class="panel-surface bg-white">
                <p class="text-[11px] uppercase tracking-[0.14em] text-gray-400">MoU</p>
                <p class="mt-1.5 text-sm font-semibold text-gray-900">{{ $klinik->mou_at ? $klinik->mou_at->format('d M Y') : '— belum ada MoU' }}</p>
            </div>
            <div class="panel-surface bg-white">
                <p class="text-[11px] uppercase tracking-[0.14em] text-gray-400">Rate total pervorma</p>
                <p class="mt-1.5 text-sm font-semibold text-gray-900">Rp{{ number_format($klinik->total_rate, 0, ',', '.') }}</p>
            </div>
            <div class="panel-surface bg-white">
                <p class="text-[11px] uppercase tracking-[0.14em] text-gray-400">Status</p>
                @if($klinik->is_active)
                    <p class="mt-1.5"><span class="rounded-full bg-pine-100 px-3 py-1 text-xs font-semibold text-pine-800">Aktif</span></p>
                @else
                    <form method="POST" action="{{ route('admin.kliniks.approve', $klinik) }}">
                        @csrf
                        <button class="mt-1 rounded-full bg-pine-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-pine-800">Approve mitra</button>
                    </form>
                @endif
            </div>
            <div class="panel-surface bg-white">
                <p class="text-[11px] uppercase tracking-[0.14em] text-gray-400">Checkout link</p>
                <p class="mt-1.5 text-sm font-semibold text-gray-900">
                    @if($linkStats)
                        {{ $linkStats['aktif'] }} aktif · {{ $linkStats['terpakai'] }} pakai · {{ $linkStats['kadaluarsa'] }} expired
                    @else — @endif
                </p>
            </div>
        </div>

        <div class="mt-4 grid gap-3 xl:grid-cols-2">
            <div class="panel-surface bg-white">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-indigo-900">Komisi pervorma HPA</p>
                    <span class="rounded-full bg-indigo-50 px-3 py-1 text-[11px] font-semibold text-indigo-700">{{ $pervormas->count() }} tahap</span>
                </div>
                @if($pervormas->isEmpty())
                    <p class="mt-3 text-xs text-gray-400">Belum ada pervorma terpicu. Tahap 1 terpicu saat MoU + 5 sesi terverifikasi dalam 90 hari.</p>
                @else
                    <div class="mt-3 overflow-x-auto">
                        <table class="table-dashed w-full">
                            <thead><tr><th>Tahap</th><th>Nominal</th><th>Status</th><th>Dibayar</th></tr></thead>
                            <tbody>
                                @foreach($pervormas as $p)
                                <tr>
                                    <td class="font-semibold text-gray-900">Tahap {{ $p->stage }}</td>
                                    <td>Rp{{ number_format($p->amount, 0, ',', '.') }}</td>
                                    <td>
                                        <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $p->status === 'paid' ? 'bg-pine-100 text-pine-800' : 'bg-amber-100 text-amber-800' }}">{{ $p->status === 'paid' ? 'Dibayar' : 'Jatuh tempo' }}</span>
                                    </td>
                                    <td>{{ $p->paid_at ? $p->paid_at->format('d M Y') : '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <div class="panel-surface bg-white">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-indigo-900">Markup rules</p>
                    <span class="rounded-full bg-indigo-50 px-3 py-1 text-[11px] font-semibold text-indigo-700">{{ $markupRules->count() }} paket</span>
                </div>
                @if($markupRules->isEmpty())
                    <p class="mt-3 text-xs text-gray-400">Belum ada markup rule untuk klinik ini.</p>
                @else
                    <div class="mt-3 overflow-x-auto">
                        <table class="table-dashed w-full">
                            <thead><tr><th>Paket</th><th>Netto</th><th>Markup</th><th>Final</th></tr></thead>
                            <tbody>
                                @foreach($markupRules as $r)
                                <tr>
                                    <td class="font-medium text-gray-900">{{ $r->package->name ?? 'paket #' . $r->consultant_package_id }}</td>
                                    <td>Rp{{ number_format($r->package->price ?? 0, 0, ',', '.') }}</td>
                                    <td>Rp{{ number_format($r->markup_amount, 0, ',', '.') }}</td>
                                    <td class="font-semibold text-gray-900">Rp{{ number_format(($r->package->price ?? 0) + $r->markup_amount, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </section>
    @endif

    {{-- Hero: checkpoint 40 sesi --}}
    <section class="fade-up dash-card p-4 md:p-5">
        <div class="hero-band p-5 md:p-6">
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1.15fr),minmax(300px,0.85fr)]">
                <div class="relative z-10">
                    <div class="flex flex-wrap items-center gap-2 text-[11px]">
                        <span class="rounded-full bg-white/12 px-3 py-1 font-semibold uppercase tracking-[0.18em] text-white">Dashboard Mitra</span>
                        <span class="rounded-full bg-white/10 px-3 py-1 font-medium text-pine-50">{{ $klinik->brand_name }}</span>
                    </div>
                    <h2 class="mt-3 max-w-2xl text-[1.6rem] md:text-[1.8rem] font-semibold tracking-tight text-white">Checkpoint {{ $stageTarget }} Konsultasi</h2>
                    <p class="mt-2 max-w-2xl text-[13px] leading-6 text-pine-50/85">Sesi terselesaikan &amp; terverifikasi sejak MoU, dengan target aktivasi 90 hari. Data agregat — konten sesi &amp; identitas customer tidak ditampilkan.</p>

                    <div class="mt-5">
                        <div class="flex items-baseline justify-between text-sm">
                            <span class="text-2xl font-semibold text-white">{{ $completedCount }}<span class="text-base font-normal text-pine-100/70"> / {{ $stageTarget }} sesi</span></span>
                            <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-pine-50">{{ $checkpointPct }}%</span>
                        </div>
                        <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-white/15">
                            <div class="h-full rounded-full bg-gradient-to-r from-terra-400 via-pine-300 to-pine-200" style="width: {{ $checkpointPct }}%"></div>
                        </div>
                        <p class="mt-2 text-[11px] text-pine-50/70">Sisa {{ max(0, $stageTarget - $completedCount) }} sesi menuju checkpoint berikutnya.</p>
                    </div>
                </div>

                <div class="relative z-10 grid content-start gap-3">
                    <div class="hero-metric">
                        <div class="text-[11px] uppercase tracking-[0.16em] text-pine-100/80">Total penjualan</div>
                        <div class="mt-2 text-2xl font-semibold">Rp{{ number_format($grossSales, 0, ',', '.') }}</div>
                        <div class="mt-1 text-[13px] text-pine-50/85">gross payment customer terverifikasi sebelum potongan</div>
                    </div>
                    <div class="hero-metric">
                        <div class="text-[11px] uppercase tracking-[0.16em] text-pine-100/80">Total pendapatan bersih</div>
                        <div class="mt-2 text-2xl font-semibold">Rp{{ number_format($marginSettled, 0, ',', '.') }}</div>
                        <div class="mt-1 text-[13px] text-pine-50/85">margin klinik yang sudah settled</div>
                    </div>
                    <div class="hero-metric">
                        <div class="text-[11px] uppercase tracking-[0.16em] text-pine-100/80">Pendapatan menunggu payout</div>
                        <div class="mt-2 text-2xl font-semibold">Rp{{ number_format($marginPending, 0, ',', '.') }}</div>
                        <div class="mt-1 text-[13px] text-pine-50/85">payment masuk dulu ke sistem, lalu diproses payout</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Stat cards --}}
    <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        @foreach($statCards as $card)
            @php $tone = $toneClasses[$card['tone']]; @endphp
            <div class="fade-up dash-card p-4">
                <div class="flex items-start justify-between gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-2xl {{ $tone['icon'] }}">
                        @include('perusahaan.partials.icon', ['name' => $card['icon']])
                    </span>
                    <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $tone['chip'] }}">{{ $card['detail'] }}</span>
                </div>
                <div class="mt-5 text-2xl font-semibold tracking-tight text-gray-900">{{ $card['value'] }}</div>
                <p class="mt-1 text-[13px] text-gray-500">{{ $card['label'] }}</p>
            </div>
        @endforeach
    </section>

    {{-- Charts --}}
    <section class="grid gap-3 lg:grid-cols-2">
        <div class="fade-up dash-card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="dash-section-title">Sesi selesai per minggu</h3>
                    <p class="dash-section-subtitle">Rolling 12 minggu terakhir</p>
                </div>
                <span class="rounded-full bg-pine-50 px-3 py-1 text-xs font-semibold text-pine-700">{{ collect($chartWeekly['data'])->sum() }} sesi</span>
            </div>
            <div class="chart-wrap mt-4"><canvas id="chartWeekly"></canvas></div>
        </div>
        <div class="fade-up dash-card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="dash-section-title">Status sesi</h3>
                    <p class="dash-section-subtitle">Distribusi seluruh sesi</p>
                </div>
                <span class="rounded-full bg-pine-50 px-3 py-1 text-xs font-semibold text-pine-700">{{ collect($chartStatus['data'])->sum() }} sesi</span>
            </div>
            <div class="chart-wrap--donut mt-4"><canvas id="chartStatus"></canvas></div>
        </div>
    </section>

    {{-- Paket --}}
    <section class="fade-up dash-card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="dash-section-title">Paket kamu</h3>
                <p class="dash-section-subtitle">Paket disusun bersama tim Soluusi sesuai kebutuhan &amp; budget klinik. Hubungi kami untuk mengubah.</p>
            </div>
            @if($isSuperAdmin)
                <a href="{{ route('admin.kliniks.packages.index', $klinik) }}" class="rounded-full bg-pine-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-pine-800">Kelola paket</a>
            @endif
        </div>
        @if($klinikPackages->count())
        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @foreach($klinikPackages as $p)
            <div class="panel-surface bg-white">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2.5">
                        <span class="grid h-9 w-9 place-items-center rounded-2xl bg-pine-50 text-pine-700">
                            @include('perusahaan.partials.icon', ['name' => 'heart'])
                        </span>
                        <h4 class="text-sm font-semibold text-gray-900">{{ $p->name }}</h4>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $p->mode === 'offline' ? 'bg-amber-100 text-amber-800' : 'bg-pine-100 text-pine-800' }}">{{ ucfirst($p->mode) }}</span>
                </div>
                @if($p->description)<p class="mt-2 text-xs leading-6 text-gray-500">{{ $p->description }}</p>@endif
                <div class="mt-3 flex items-baseline justify-between border-t border-dashed border-[#e7e2d9] pt-3">
                    <span class="text-base font-bold text-pine-700">Rp{{ number_format($p->price, 0, ',', '.') }}</span>
                    <span class="text-[11px] text-gray-400">{{ $p->duration_minutes }} menit</span>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <p class="mt-4 rounded-3xl bg-gray-50 px-4 py-8 text-center text-sm text-gray-400">Belum ada paket untuk klinik kamu.</p>
        @endif
    </section>

    {{-- Konsultan + aktivitas --}}
    <section class="grid gap-3 xl:grid-cols-[minmax(0,1.1fr),minmax(0,0.9fr)]">
        <div class="fade-up dash-card p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="dash-section-title">Konsultan di daerah kamu</h3>
                    <p class="dash-section-subtitle">Konsultan terverifikasi dengan slot aktif</p>
                </div>
                <a href="{{ route('klinik.consultants.index') }}" class="rounded-full bg-pine-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-pine-800">Lihat semua</a>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @forelse($nearbyConsultants as $c)
                <div class="panel-surface bg-white flex flex-col">
                    <div class="flex items-start justify-between">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-900">{{ trim(($c->user->firstname ?? '') . ' ' . ($c->user->lastname ?? '')) }}</h4>
                            <p class="mt-0.5 text-xs text-gray-500">{{ $c->user->regency->name ?? 'Lokasi fleksibel' }}</p>
                        </div>
                        @if($c->schedules_count > 0)
                            <span class="rounded-full bg-pine-100 px-2.5 py-1 text-[10px] font-semibold text-pine-800">{{ $c->schedules_count }} slot</span>
                        @endif
                    </div>
                    <div class="mt-2 flex flex-wrap gap-1">
                        @foreach($c->categories->take(3) as $cat)
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-600">{{ $cat->name }}</span>
                        @endforeach
                    </div>
                    <div class="mt-auto pt-3">
                        <a href="{{ route('klinik.consultants.show', $c) }}" class="inline-block rounded-full border border-pine-200 px-3 py-1.5 text-xs font-semibold text-pine-700 hover:bg-pine-50">Lihat profil</a>
                    </div>
                </div>
                @empty
                <div class="sm:col-span-2 rounded-3xl bg-gray-50 px-4 py-8 text-center text-sm text-gray-400">
                    Belum ada konsultan di daerah kamu. <a href="{{ route('klinik.consultants.index', ['semua_daerah' => 1]) }}" class="font-semibold text-pine-700">Lihat konsultan semua daerah</a>
                </div>
                @endforelse
            </div>
        </div>

        <div class="fade-up dash-card p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="dash-section-title">Aktivitas terbaru</h3>
                    <p class="dash-section-subtitle">Order terakhir tanpa identitas customer</p>
                </div>
                <a href="{{ route('klinik.links.index') }}" class="rounded-full border border-pine-200 px-3 py-1.5 text-xs font-semibold text-pine-700 hover:bg-pine-50">Checkout link</a>
            </div>
            <div class="mt-3 overflow-x-auto">
                <table class="table-dashed w-full">
                    <thead><tr><th>Waktu</th><th>Konsultan</th><th>Bayar</th><th>Status</th><th class="text-right">Margin</th></tr></thead>
                    <tbody>
                        @forelse($recentSessions as $s)
                        <tr>
                            <td class="whitespace-nowrap">{{ $s->order_at->format('d M H:i') }}</td>
                            <td>
                                <span class="font-medium text-gray-900">{{ trim(($s->consultant->user->firstname ?? '') . ' ' . ($s->consultant->user->lastname ?? '')) }}</span>
                                <span class="block text-[11px] text-gray-400">{{ $s->consultantPackage->name ?? '-' }}</span>
                            </td>
                            <td>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $s->payment_status === 'verified' ? 'bg-pine-100 text-pine-800' : ($s->payment_status === 'waiting' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-500') }}">{{ ucfirst($s->payment_status) }}</span>
                            </td>
                            <td>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $s->status === 'done' ? 'bg-pine-100 text-pine-800' : 'bg-gray-100 text-gray-500' }}">{{ ucfirst(str_replace('_', ' ', $s->status)) }}</span>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                @if($s->splitSettlement)
                                    <span class="font-semibold text-pine-700">Rp{{ number_format($s->splitSettlement->klinik_margin, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-gray-400">Belum ada aktivitas. Buat checkout link pertama kamu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- Quick actions --}}
    <section class="fade-up flex flex-wrap gap-3">
        <a href="{{ route('checkout.klinik', $klinik->uuid) }}" target="_blank" class="rounded-full bg-terra-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-terra-600">Buka Link Customer</a>
        <a href="{{ route('klinik.consultants.index') }}" class="rounded-full bg-pine-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-pine-800">Pilih Konsultan</a>
        <a href="{{ route('klinik.schedules.index') }}" class="rounded-full border border-[#e7e2d9] bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:border-pine-300">Kelola Jadwal</a>
        <a href="{{ route('klinik.links.index') }}" class="rounded-full border border-pine-200 bg-pine-50 px-5 py-2.5 text-sm font-semibold text-pine-700 hover:bg-pine-100">Kelola Checkout Link</a>
    </section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
const chartFont = { family: 'Instrument Sans, sans-serif', size: 11 };
const gridColor = 'rgba(148, 163, 184, 0.18)';
const axisColor = '#64748b';
const PINE = '#007648', PINE_LIGHT = '#1e8a5e', PINE_SOFT = '#4aa37c', TERRA = '#e2703a', RED = '#dc2626';

const weekly = @json($chartWeekly);
new Chart(document.getElementById('chartWeekly'), {
    type: 'bar',
    data: {
        labels: weekly.labels,
        datasets: [{
            data: weekly.data,
            backgroundColor: weekly.data.map((v, i) => i === weekly.data.length - 1 ? PINE : PINE_LIGHT),
            borderRadius: 999,
            borderSkipped: false,
            maxBarThickness: 18,
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color: axisColor, font: chartFont, maxRotation: 45 }, grid: { display: false } },
            y: { beginAtZero: true, ticks: { color: axisColor, font: chartFont, precision: 0 }, grid: { color: gridColor } }
        }
    }
});

const st = @json($chartStatus);
new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
        labels: st.labels,
        datasets: [{
            data: st.data,
            backgroundColor: [PINE, PINE_SOFT, TERRA, RED, '#94a3b8'],
            borderWidth: 0,
            cutout: '62%',
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 10, color: axisColor, font: chartFont } } }
    }
});
</script>
@endpush
