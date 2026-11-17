@extends('layouts.app')

@section('title', $klinik->brand . ' — Tenant Klinik')

@section('content')
<div class="flex items-baseline justify-between mb-2">
    <h1 class="text-xl font-bold">{{ $klinik->brand }}</h1>
    <a href="{{ route('admin.tenants.kliniks.index') }}" class="text-sm text-emerald-700 font-semibold hover:underline">← Semua klinik</a>
</div>

<div class="grid md:grid-cols-2 gap-6 mb-8">
    <dl class="bg-white border rounded-lg p-6 text-sm space-y-2">
        <div class="flex justify-between"><dt class="text-gray-500">Nama legal</dt><dd class="font-semibold">{{ $klinik->name }}</dd></div>
        <div class="flex justify-between"><dt class="text-gray-500">Rate HAP</dt><dd class="font-semibold">Rp{{ number_format($klinik->total_rate, 0, ',', '.') }}</dd></div>
        <div class="flex justify-between"><dt class="text-gray-500">MoU</dt><dd class="font-semibold">{{ optional($klinik->mou_at)->format('d M Y') ?? '—' }}</dd></div>
        <div class="flex justify-between"><dt class="text-gray-500">Approve ops</dt><dd class="font-semibold">{{ optional($klinik->approved_at)->format('d M Y') ?? 'belum' }}</dd></div>
        <div class="flex justify-between"><dt class="text-gray-500">Associate (HAP)</dt><dd class="font-semibold">{{ $klinik->associate?->fullname ?? '—' }}</dd></div>
        <div class="flex justify-between"><dt class="text-gray-500">AI insight</dt><dd class="font-semibold">{{ $klinik->ai_enabled ? 'aktif' : 'nonaktif' }}</dd></div>
        <div class="flex justify-between"><dt class="text-gray-500">Status program</dt>
            <dd>@if($klinik->is_active)<span class="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 font-semibold">Aktif</span>@else<span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-500">Pending</span>@endif</dd>
        </div>
    </dl>
    <div class="bg-white border rounded-lg p-6 text-sm space-y-2">
        <h2 class="font-semibold text-gray-700 mb-2">Admin klinik</h2>
        @forelse($klinik->admins as $a)
            <div class="flex justify-between border-b last:border-0 py-1">
                <span>{{ trim(($a->user?->firstname ?? '') . ' ' . ($a->user?->lastname ?? '')) }}</span>
                <span class="text-gray-400">{{ $a->role }}</span>
            </div>
        @empty
            <p class="text-gray-400">Belum ada admin.</p>
        @endforelse
        <div class="grid grid-cols-3 gap-2 pt-4 text-center">
            <div><div class="text-lg font-bold">{{ $klinik->checkout_links_count }}</div><div class="text-xs text-gray-400">Checkout link</div></div>
            <div><div class="text-lg font-bold">{{ $klinik->split_settlements_count }}</div><div class="text-xs text-gray-400">Split settlement</div></div>
            <div><div class="text-lg font-bold">{{ $klinik->klinik_packages_count }}</div><div class="text-xs text-gray-400">Paket</div></div>
        </div>
    </div>
</div>

<a href="{{ route('admin.tenants.kliniks.edit', $klinik) }}"
   class="inline-block bg-emerald-600 text-white font-semibold rounded px-4 py-2 text-sm hover:bg-emerald-700">Edit tenant</a>

@include('admin.tenants._lifecycle-actions', ['tenant' => $klinik, 'vertical' => 'kliniks'])
@include('admin.tenants._associate-form', ['tenant' => $klinik])
@include('admin.tenants._view-as-form')
@endsection
