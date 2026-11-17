@extends('layouts.app')

@section('title', 'Ops Work Queue — Admin')

@section('content')
<div class="flex items-baseline justify-between mb-2">
    <h1 class="text-xl font-bold">Ops Work Queue</h1>
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-emerald-700 font-semibold hover:underline">← Dashboard</a>
</div>
<p class="text-sm text-gray-500 mb-6">Antrean ops gabungan ({{ $total }} item): sesi perlu review, mitra klinik pending approval, pervorma payout due, aktivasi perusahaan/sekolah. Aksi memakai route existing.</p>

<nav class="flex flex-wrap gap-2 mb-6 text-sm">
    <a href="{{ route('admin.ops-queue.index') }}" class="px-3 py-1.5 rounded-full border {{ $active === null ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-white hover:border-emerald-600' }}">Semua ({{ $total }})</a>
    @foreach([
        'sessions' => 'Sesi review',
        'kliniks' => 'Klinik pending',
        'pervormas' => 'Pervorma due',
        'perusahaan' => 'Perusahaan pending',
        'sekolah' => 'Sekolah pending',
    ] as $key => $label)
        <a href="{{ route('admin.ops-queue.index', ['type' => $key]) }}" class="px-3 py-1.5 rounded-full border {{ $active === $key ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-white hover:border-emerald-600' }}">{{ $label }} ({{ $counts[$key] }})</a>
    @endforeach
</nav>

@if($sessions->isNotEmpty())
<h2 class="font-semibold mb-2">Sesi perlu review ops</h2>
<div class="overflow-x-auto bg-white border rounded-lg mb-8"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Sesi</th><th class="p-3">Konsultan</th><th class="p-3">Klinik</th><th class="p-3">Mark-done</th><th class="p-3">Aksi</th>
    </tr></thead>
    <tbody>
        @foreach($sessions as $s)
        <tr class="border-b">
            <td class="p-3">#{{ $s->id }} · {{ $s->order_number }}</td>
            <td class="p-3">{{ trim(($s->consultant->user->firstname ?? '') . ' ' . ($s->consultant->user->lastname ?? '')) }}</td>
            <td class="p-3">{{ $s->klinik->brand_name ?? '-' }}</td>
            <td class="p-3">{{ optional($s->konsultan_done_at)->translatedFormat('d M Y H.i') ?: '-' }}</td>
            <td class="p-3">
                <form method="POST" action="{{ route('admin.sessions.verify', $s) }}">@csrf
                    <button class="text-xs bg-emerald-700 text-white rounded px-2 py-1 font-semibold">Verifikasi Manual</button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table></div>
@endif

@if($kliniks->isNotEmpty())
<h2 class="font-semibold mb-2">Mitra klinik menunggu approval</h2>
<div class="overflow-x-auto bg-white border rounded-lg mb-8"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Klinik</th><th class="p-3">Daftar</th><th class="p-3">Aksi</th>
    </tr></thead>
    <tbody>
        @foreach($kliniks as $k)
        <tr class="border-b">
            <td class="p-3">{{ $k->name }} <span class="text-xs text-gray-400">{{ $k->brand }}</span></td>
            <td class="p-3">{{ $k->created_at->translatedFormat('d M Y H.i') }}</td>
            <td class="p-3">
                <form method="POST" action="{{ route('admin.kliniks.approve', $k) }}">@csrf
                    <button class="text-xs bg-emerald-700 text-white rounded px-3 py-1.5 font-semibold">Approve</button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table></div>
@endif

@if($pervormas->isNotEmpty())
<h2 class="font-semibold mb-2">Performa HPA jatuh tempo</h2>
<div class="overflow-x-auto bg-white border rounded-lg mb-8"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Vertikal</th><th class="p-3">Tenant</th><th class="p-3">Associate</th><th class="p-3">Stage</th><th class="p-3">Amount</th><th class="p-3">Jatuh tempo</th><th class="p-3">Aksi</th>
    </tr></thead>
    <tbody>
        @foreach($pervormas as $c)
        @php
            $tenant = $c->vertical === 'hap' ? $c->klinik : ($c->vertical === 'cap' ? $c->perusahaan : $c->sekolah);
            $tenantName = $c->vertical === 'hap' ? ($tenant->brand_name ?? '-') : ($tenant->brand ?? $tenant->name ?? '-');
            $verticalLabel = ['hap' => 'Klinik', 'cap' => 'Perusahaan', 'sap' => 'Sekolah'][$c->vertical] ?? $c->vertical;
        @endphp
        <tr class="border-b">
            <td class="p-3"><span class="text-xs px-2 py-0.5 rounded bg-gray-100">{{ $verticalLabel }}</span></td>
            <td class="p-3">{{ $tenantName }}</td>
            <td class="p-3">{{ $c->associate ? trim($c->associate->firstname . ' ' . $c->associate->lastname) : '-' }}</td>
            <td class="p-3">Stage {{ $c->stage }}</td>
            <td class="p-3">Rp{{ number_format($c->amount, 0, ',', '.') }}</td>
            <td class="p-3">{{ optional($c->due_at)->translatedFormat('d M Y') ?? '-' }}</td>
            <td class="p-3">
                <form method="POST" action="{{ route('admin.pervormas.pay', ['vertical' => $c->vertical, 'pervorma' => $c->id]) }}">@csrf
                    <button class="text-xs bg-emerald-700 text-white rounded px-3 py-1.5 font-semibold">Tandai dibayar</button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table></div>
@endif

@if($perusahaans->isNotEmpty())
<h2 class="font-semibold mb-2">Perusahaan menunggu aktivasi</h2>
<div class="overflow-x-auto bg-white border rounded-lg mb-8"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Perusahaan</th><th class="p-3">Dibuat</th><th class="p-3">Aksi</th>
    </tr></thead>
    <tbody>
        @foreach($perusahaans as $p)
        <tr class="border-b">
            <td class="p-3">{{ $p->name }} <span class="text-xs text-gray-400">{{ $p->brand }}</span></td>
            <td class="p-3">{{ $p->created_at->translatedFormat('d M Y') }}</td>
            <td class="p-3">
                <form method="POST" action="{{ route('admin.perusahaans.activate', $p) }}">@csrf
                    <button class="text-xs bg-emerald-700 text-white rounded px-3 py-1.5 font-semibold">Aktifkan</button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table></div>
@endif

@if($sekolahs->isNotEmpty())
<h2 class="font-semibold mb-2">Sekolah menunggu aktivasi</h2>
<div class="overflow-x-auto bg-white border rounded-lg mb-8"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Sekolah</th><th class="p-3">Dibuat</th><th class="p-3">Aksi</th>
    </tr></thead>
    <tbody>
        @foreach($sekolahs as $s)
        <tr class="border-b">
            <td class="p-3">{{ $s->name }} <span class="text-xs text-gray-400">{{ $s->brand }}</span></td>
            <td class="p-3">{{ $s->created_at->translatedFormat('d M Y') }}</td>
            <td class="p-3">
                <form method="POST" action="{{ route('admin.sekolahs.activate', $s) }}">@csrf
                    <button class="text-xs bg-emerald-700 text-white rounded px-3 py-1.5 font-semibold">Aktifkan</button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table></div>
@endif

@if($total === 0)
<div class="bg-white border rounded-lg p-6 text-gray-400 text-sm">Tidak ada item antrean.</div>
@endif
@endsection
