@extends('layouts.app')

@section('content')
@php
$labels = ['hap' => 'HPA (Klinik)', 'sap' => 'SAP (Sekolah)', 'cap' => 'CAP (Perusahaan)'];
@endphp
<h1 class="text-xl font-bold mb-1">Pervorma 3 Vertikal — <a href="{{ route('admin.dashboard') }}" class="text-sm text-emerald-700 font-normal hover:underline">← Dashboard</a></h1>
<p class="text-sm text-gray-500 mb-4">
    @if($vertical === 'hap')
        Sesi yang di-tag berkontribusi ke stage performa (1/2/3). Sesi tanpa tag = di atas stage-3 trigger atau di luar window 90 hari.
    @else
        Daftar pervorma {{ $labels[$vertical] }} (engine session-count; tidak ada tag per-sesi). Gunakan filter untuk menyaring.
    @endif
</p>

<nav class="flex flex-wrap gap-2 mb-4 text-sm">
    @foreach($labels as $slug => $label)
    <a href="{{ route('admin.pervorma-sessions', ['vertical' => $slug]) }}" class="px-3 py-1.5 rounded-full border {{ $vertical === $slug ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-white hover:border-emerald-600' }}">{{ $label }}</a>
    @endforeach
</nav>

@if($vertical === 'hap')
<form method="GET" class="mb-4 flex gap-3 items-end">
    <input type="hidden" name="vertical" value="hap">
    <div>
        <label class="block text-xs text-gray-500 mb-1">Klinik</label>
        <select name="klinik_id" class="border rounded px-3 py-2 text-sm">
            <option value="">— semua —</option>
            @foreach($kliniks as $k)
            <option value="{{ $k->id }}" @selected(request('klinik_id') == $k->id)>{{ $k->brand_name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Stage</label>
        <select name="stage" class="border rounded px-3 py-2 text-sm">
            <option value="">— semua —</option>
            @foreach([1,2,3] as $s)
            <option value="{{ $s }}" @selected(request('stage') == $s)>Stage {{ $s }}</option>
            @endforeach
        </select>
    </div>
    <button class="bg-emerald-700 text-white rounded px-4 py-2 text-sm font-semibold">Filter</button>
</form>

<div class="overflow-x-auto bg-white border rounded-lg"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Klinik</th>
        <th class="p-3">Konsultan</th>
        <th class="p-3">Stage</th>
        <th class="p-3">Selesai pada</th>
        <th class="p-3">Jadwal</th>
        <th class="p-3">Bayar</th>
    </tr></thead>
    <tbody>
        @forelse($sessions as $s)
        <tr class="border-b">
            <td class="p-3"><a class="text-emerald-700 hover:underline" href="{{ route('klinik.dashboard', ['klinik_id' => $s->klinik_id]) }}">{{ $s->klinik->brand_name ?? '—' }}</a></td>
            <td class="p-3">{{ trim(($s->consultant->user->firstname ?? '') . ' ' . ($s->consultant->user->lastname ?? '')) }}</td>
            <td class="p-3"><span class="bg-emerald-100 text-emerald-800 text-xs font-semibold rounded px-2 py-1">Stage {{ $s->hap_pervorma_stage }}</span></td>
            <td class="p-3">{{ optional($s->completion_verified_at)->translatedFormat('d M Y H.i') }}</td>
            <td class="p-3">{{ optional($s->schedule)->started_at?->translatedFormat('d M Y H.i') ?? '—' }}</td>
            <td class="p-3">Rp{{ number_format((int) $s->payment, 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="p-3 text-gray-400">Belum ada sesi yang di-tag.</td></tr>
        @endforelse
    </tbody>
</table></div>

<div class="mt-4">{{ $sessions->links() }}</div>
@else
<form method="GET" class="mb-4 flex gap-3 items-end">
    <input type="hidden" name="vertical" value="{{ $vertical }}">
    <div>
        <label class="block text-xs text-gray-500 mb-1">Tenant</label>
        <select name="tenant_id" class="border rounded px-3 py-2 text-sm">
            <option value="">— semua —</option>
            @foreach($tenants as $t)
            <option value="{{ $t->id }}" @selected(request('tenant_id') == $t->id)>{{ $t->brand }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Stage</label>
        <select name="stage" class="border rounded px-3 py-2 text-sm">
            <option value="">— semua —</option>
            @foreach([1,2,3] as $s)
            <option value="{{ $s }}" @selected(request('stage') == $s)>Stage {{ $s }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Status</label>
        <select name="status" class="border rounded px-3 py-2 text-sm">
            <option value="">— semua —</option>
            @foreach(['due' => 'Due', 'paid' => 'Paid'] as $val => $lbl)
            <option value="{{ $val }}" @selected(request('status') === $val)>{{ $lbl }}</option>
            @endforeach
        </select>
    </div>
    <button class="bg-emerald-700 text-white rounded px-4 py-2 text-sm font-semibold">Filter</button>
</form>

<div class="overflow-x-auto bg-white border rounded-lg"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Tenant</th>
        <th class="p-3">Associate</th>
        <th class="p-3">Stage</th>
        <th class="p-3">Amount</th>
        <th class="p-3">Status</th>
        <th class="p-3">Jatuh tempo</th>
        <th class="p-3">Aksi</th>
    </tr></thead>
    <tbody>
        @forelse($payouts as $p)
        <tr class="border-b">
            <td class="p-3">{{ $p->{$vertical === 'sap' ? 'sekolah' : 'perusahaan'}->brand ?? '—' }}</td>
            <td class="p-3">{{ $p->associate ? trim($p->associate->firstname . ' ' . $p->associate->lastname) : '—' }}</td>
            <td class="p-3"><span class="bg-emerald-100 text-emerald-800 text-xs font-semibold rounded px-2 py-1">Stage {{ $p->stage }}</span></td>
            <td class="p-3">Rp{{ number_format($p->amount, 0, ',', '.') }}</td>
            <td class="p-3"><span class="text-xs px-2 py-0.5 rounded {{ $p->status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ ucfirst($p->status) }}</span></td>
            <td class="p-3">{{ optional($p->due_at)->translatedFormat('d M Y') ?? '—' }}</td>
            <td class="p-3">
                @if($p->status !== 'paid')
                <form method="POST" action="{{ route('admin.pervormas.pay', ['vertical' => $vertical, 'pervorma' => $p->id]) }}">@csrf
                    <button class="text-xs bg-emerald-700 text-white rounded px-3 py-1.5 font-semibold">Tandai dibayar</button>
                </form>
                @else
                <span class="text-xs text-gray-400">{{ optional($p->paid_at)->translatedFormat('d M Y') }}</span>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="p-3 text-gray-400">Belum ada pervorma untuk vertikal ini.</td></tr>
        @endforelse
    </tbody>
</table></div>

<div class="mt-4">{{ $payouts->links() }}</div>
@endif
@endsection
