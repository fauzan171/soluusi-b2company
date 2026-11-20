@extends('layouts.app')

@section('content')
@php
    $chartMax = max(1, (int) collect($chartKlinik['data'])->max());
    $statusCopy = [
        'waiting' => ['label' => 'Menunggu pembayaran', 'class' => 'bg-amber-50 text-amber-700 border-amber-100'],
        'scheduled' => ['label' => 'Terjadwal', 'class' => 'bg-sky-50 text-sky-700 border-sky-100'],
        'done' => ['label' => 'Selesai', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-100'],
        'canceled' => ['label' => 'Dibatalkan', 'class' => 'bg-rose-50 text-rose-700 border-rose-100'],
    ];
@endphp

<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-pine-700">Associate Partner</p>
        <h1 class="mt-2 text-2xl font-bold text-gray-950">Dashboard HPA</h1>
        <p class="mt-1 text-sm text-gray-500">Pantau tenant referral (klinik, perusahaan, sekolah), progress stage performa, dan status pencairan komisi.</p>
    </div>
</div>

<div class="grid grid-cols-2 gap-3 md:grid-cols-5">
    <div class="rounded-lg border bg-white p-4">
        <div class="text-2xl font-bold">{{ $totalTenants }}</div>
        <div class="text-xs text-gray-500">Total tenant</div>
    </div>
    <div class="rounded-lg border bg-white p-4">
        <div class="text-2xl font-bold text-pine-700">{{ $activeTenants }}</div>
        <div class="text-xs text-gray-500">Tenant aktif</div>
    </div>
    <div class="rounded-lg border bg-white p-4">
        <div class="text-2xl font-bold text-amber-600">Rp{{ number_format($totalDue, 0, ',', '.') }}</div>
        <div class="text-xs text-gray-500">Total belum cair</div>
    </div>
    <div class="rounded-lg border bg-white p-4">
        <div class="text-2xl font-bold text-sky-700">Rp{{ number_format($totalRequested, 0, ',', '.') }}</div>
        <div class="text-xs text-gray-500">Sedang diajukan</div>
    </div>
    <div class="rounded-lg border bg-white p-4">
        <div class="text-2xl font-bold text-emerald-700">Rp{{ number_format($totalPaid, 0, ',', '.') }}</div>
        <div class="text-xs text-gray-500">Sudah diterima</div>
    </div>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
    <section class="rounded-lg border bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-gray-950">Performa sesi per klinik</h2>
                <p class="text-xs text-gray-500">Semua sesi customer dari klinik referral HPA.</p>
            </div>
            <span class="rounded-full bg-pine-50 px-3 py-1 text-xs font-semibold text-pine-700">{{ collect($chartKlinik['data'])->sum() }} sesi</span>
        </div>
        <div class="space-y-3">
            @forelse($chartKlinik['labels'] as $i => $label)
                @php
                    $value = (int) ($chartKlinik['data'][$i] ?? 0);
                    $width = max(8, round(($value / $chartMax) * 100));
                @endphp
                <div>
                    <div class="mb-1 flex items-center justify-between gap-3 text-sm">
                        <span class="truncate font-medium text-gray-800">{{ $label }}</span>
                        <span class="shrink-0 font-semibold text-gray-950">{{ $value }} sesi</span>
                    </div>
                    <div class="h-3 overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-pine-700" style="width: {{ $width }}%"></div>
                    </div>
                </div>
            @empty
                <p class="py-8 text-center text-sm text-gray-400">Belum ada sesi dari klinik referral.</p>
            @endforelse
        </div>
    </section>

    <section id="komisi" class="rounded-lg border bg-white p-5">
        <div class="mb-4">
            <h2 class="font-semibold text-gray-950">Status pencairan dana</h2>
            <p class="text-xs text-gray-500">HPA bisa minta pencairan untuk komisi yang sudah due.</p>
        </div>
        <div class="space-y-3">
            @forelse($payoutItems as $item)
                @php
                    $isPaid = $item->status === 'paid';
                    $isRequested = !$isPaid && filled($item->requested_at);
                    $label = $isPaid ? 'Sudah cair' : ($isRequested ? 'Diproses finance' : 'Siap diajukan');
                    $pill = $isPaid ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : ($isRequested ? 'bg-sky-50 text-sky-700 border-sky-100' : 'bg-amber-50 text-amber-700 border-amber-100');
                    $tenant = $item->klinik ?? $item->perusahaan ?? $item->sekolah;
                    $tenantName = $tenant?->brand_name ?? ($tenant?->brand ?: $tenant?->name) ?? '-';
                    $verticalLabel = ['hap' => 'Klinik', 'cap' => 'Perusahaan', 'sap' => 'Sekolah'][$item->vertical] ?? strtoupper($item->vertical);
                @endphp
                <div class="border-b pb-3 last:border-b-0 last:pb-0">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-950">{{ $tenantName }} · Stage {{ $item->stage }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">{{ $verticalLabel }} · Rp{{ number_format($item->amount, 0, ',', '.') }}</p>
                        </div>
                        <span class="shrink-0 rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $pill }}">{{ $label }}</span>
                    </div>
                    <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-gray-500">
                        <span>Diajukan: {{ optional($item->requested_at)->translatedFormat('d M Y') ?? '-' }}</span>
                        <span>Estimasi cair: {{ optional($item->due_at)->translatedFormat('d M Y') ?? '-' }}</span>
                    </div>
                    @if(!$isPaid && !$isRequested)
                        <form method="POST" action="{{ route('associate.pervormas.request-payout', ['vertical' => $item->vertical, 'pervorma' => $item->id]) }}" class="mt-3">
                            @csrf
                            <button class="inline-flex items-center gap-2 rounded-lg bg-pine-700 px-3 py-2 text-xs font-semibold text-white hover:bg-pine-800">
                                Minta cairkan dana
                            </button>
                        </form>
                    @endif
                </div>
            @empty
                <p class="py-8 text-center text-sm text-gray-400">Belum ada komisi performa.</p>
            @endforelse
        </div>
    </section>
</div>

@foreach($verticals as $slug => $vdata)
    @php
        $stages = $vdata['stages'];
        // Trigger maksimum untuk progress bar (mou_at dianggap 0 — pakai stage 3 int).
        $maxTrigger = collect($stages)->pluck('trigger')->filter(fn ($t) => is_int($t))->max() ?: 1;
    @endphp
    <h2 class="mb-3 mt-8 font-semibold text-gray-950">{{ $vdata['label'] }} referral HPA</h2>
    <div class="mb-3 rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-2 text-xs text-emerald-900">
        <span class="font-semibold">Rule:</span>
        @foreach($stages as $stage => $def)
            Stage {{ $stage }} {{ $def['trigger'] === 'mou_at' ? 'saat tenant aktif' : 'di ' . $def['trigger'] . ' sesi' }}{{ !$loop->last ? ',' : '.' }}
        @endforeach
    </div>
    <div class="grid gap-4 xl:grid-cols-2">
        @forelse($vdata['tenants'] as $tenant)
            @php
                $stats = $vdata['stats'][$tenant->id] ?? ['completed' => 0, 'progress' => [], 'due' => 0, 'paid' => 0, 'pervormas' => collect()];
                $progressPct = min(100, round(($stats['completed'] / max(1, $maxTrigger)) * 100));
                $tenantName = $tenant->brand_name ?? ($tenant->brand ?: $tenant->name);
            @endphp
            <article class="rounded-lg border bg-white p-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h3 class="truncate font-semibold text-gray-950">{{ $tenantName }}</h3>
                        <p class="text-xs text-gray-500">{{ $tenant->name }}</p>
                        <p class="mt-1 text-xs text-gray-400">MoU {{ optional($tenant->mou_at)->translatedFormat('d M Y') ?? optional($tenant->created_at)->translatedFormat('d M Y') }}</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $tenant->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                        {{ $tenant->is_active ? 'Aktif' : 'Pending' }}
                    </span>
                </div>

                <div class="mt-4 grid grid-cols-3 gap-3 text-sm">
                    <div>
                        <div class="font-bold text-gray-950">{{ $stats['completed'] }} / {{ $maxTrigger }}</div>
                        <div class="text-xs text-gray-500">Sesi selesai</div>
                    </div>
                    <div>
                        <div class="font-bold text-amber-600">Rp{{ number_format($stats['due'], 0, ',', '.') }}</div>
                        <div class="text-xs text-gray-500">Belum cair</div>
                    </div>
                    <div>
                        <div class="font-bold text-emerald-700">Rp{{ number_format($stats['paid'], 0, ',', '.') }}</div>
                        <div class="text-xs text-gray-500">Sudah cair</div>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="mb-2 flex items-center justify-between text-xs text-gray-500">
                        <span>Progress stage</span>
                        <span>{{ $progressPct }}%</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-emerald-600" style="width: {{ $progressPct }}%"></div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($stages as $stage => $def)
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ ($stats['progress'][$stage] ?? false) ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-400' }}">Stage {{ $stage }}</span>
                        @endforeach
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-lg border bg-white p-8 text-center text-gray-400">
                Anda belum memiliki {{ strtolower($vdata['label']) }} referral.
            </div>
        @endforelse
    </div>
@endforeach

<h2 class="mb-3 mt-8 font-semibold text-gray-950">Aktivitas terbaru</h2>
<div class="overflow-hidden rounded-lg border bg-white">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b bg-gray-50 text-left text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">
                <th class="p-3">Tanggal</th>
                <th class="p-3">Klinik</th>
                <th class="p-3">Status</th>
                <th class="p-3 text-right">Jumlah sesi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recentSessions as $s)
                @php $copy = $statusCopy[$s->status] ?? ['label' => ucfirst(str_replace('_', ' ', $s->status)), 'class' => 'bg-gray-50 text-gray-600 border-gray-100']; @endphp
                <tr class="border-b last:border-b-0">
                    <td class="p-3">{{ \Illuminate\Support\Carbon::parse($s->activity_date)->translatedFormat('d M Y') }}</td>
                    <td class="p-3">{{ $s->klinik->brand_name ?? '-' }}</td>
                    <td class="p-3">
                        <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $copy['class'] }}">{{ $copy['label'] }}</span>
                    </td>
                    <td class="p-3 text-right font-semibold">{{ $s->session_count }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="p-4 text-center text-gray-400">Belum ada aktivitas.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
