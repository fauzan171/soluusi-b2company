@extends('layouts.app')

@section('title', $perusahaan->brand . ' — Tenant Perusahaan')

@section('content')
<div class="flex items-baseline justify-between mb-2">
    <h1 class="text-xl font-bold">{{ $perusahaan->brand }}</h1>
    <a href="{{ route('admin.tenants.perusahaans.index') }}" class="text-sm text-emerald-700 font-semibold hover:underline">← Semua perusahaan</a>
</div>

<div class="grid md:grid-cols-2 gap-6 mb-8">
    <dl class="bg-white border rounded-lg p-6 text-sm space-y-2">
        <div class="flex justify-between"><dt class="text-gray-500">Nama legal</dt><dd class="font-semibold">{{ $perusahaan->name }}</dd></div>
        <div class="flex justify-between"><dt class="text-gray-500">Rate PEPM</dt><dd class="font-semibold">Rp{{ number_format($perusahaan->pepm_rate, 0, ',', '.') }}</dd></div>
        <div class="flex justify-between"><dt class="text-gray-500">Kuota sesi/karyawan/bln</dt><dd class="font-semibold">{{ $perusahaan->sessions_per_employee_month }}</dd></div>
        <div class="flex justify-between"><dt class="text-gray-500">Jumlah karyawan terdaftar</dt><dd class="font-semibold">{{ $perusahaan->employee_count }}</dd></div>
        <div class="flex justify-between"><dt class="text-gray-500">MoU</dt><dd class="font-semibold">{{ optional($perusahaan->mou_at)->format('d M Y') ?? '—' }}</dd></div>
        <div class="flex justify-between"><dt class="text-gray-500">Associate</dt><dd class="font-semibold">{{ $perusahaan->associate?->fullname ?? '—' }}</dd></div>
        <div class="flex justify-between"><dt class="text-gray-500">AI insight</dt><dd class="font-semibold">{{ $perusahaan->ai_enabled ? 'aktif' : 'nonaktif' }}</dd></div>
        <div class="flex justify-between"><dt class="text-gray-500">Status program</dt>
            <dd>@if($perusahaan->is_active)<span class="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 font-semibold">Aktif</span>@else<span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-500">Pending</span>@endif</dd>
        </div>
        <div class="flex justify-between"><dt class="text-gray-500">Jabatan Pendaftar</dt><dd class="font-semibold">{{ $perusahaan->registrant_position ?? '—' }}</dd></div>
        <div class="flex justify-between"><dt class="text-gray-500">Versi S&K</dt><dd class="font-semibold">{{ $perusahaan->terms_version ?? '—' }}</dd></div>
        <div class="flex justify-between"><dt class="text-gray-500">Lifecycle</dt>
            <dd>@if($perusahaan->status === 'suspended')<span class="text-xs px-2 py-0.5 rounded bg-red-100 text-red-700 font-semibold">Suspended</span>@else<span class="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 font-semibold">{{ ucfirst($perusahaan->status ?? 'active') }}</span>@endif</dd>
        </div>
    </dl>
    <div class="bg-white border rounded-lg p-6 text-sm">
        <h2 class="font-semibold text-gray-700 mb-2">HR admin</h2>
        @forelse($perusahaan->admins as $a)
            <div class="flex justify-between border-b last:border-0 py-1">
                <span>{{ trim(($a->user?->firstname ?? '') . ' ' . ($a->user?->lastname ?? '')) }}</span>
                <span class="text-gray-400">{{ $a->role }}</span>
            </div>
        @empty
            <p class="text-gray-400">Belum ada HR admin.</p>
        @endforelse
        <div class="grid grid-cols-2 gap-2 pt-4 text-center">
            <div><div class="text-lg font-bold">{{ $perusahaan->employees_count }}</div><div class="text-xs text-gray-400">Karyawan</div></div>
            <div><div class="text-lg font-bold">{{ $perusahaan->sessions_count }}</div><div class="text-xs text-gray-400">Sesi</div></div>
        </div>
    </div>
</div>

<a href="{{ route('admin.tenants.perusahaans.edit', $perusahaan) }}"
   class="inline-block bg-emerald-600 text-white font-semibold rounded px-4 py-2 text-sm hover:bg-emerald-700">Edit tenant</a>

@if(!$perusahaan->is_active)
    <form action="{{ route('admin.perusahaans.activate', $perusahaan) }}" method="POST" class="inline-block ml-2"
          onsubmit="return confirm('Aktifkan program perusahaan ini?')">
        @csrf
        <button class="bg-emerald-600 text-white font-semibold rounded px-4 py-2 text-sm hover:bg-emerald-700">Aktifkan program</button>
    </form>
@endif
<a href="{{ route('admin.wellbeing-programs.index') }}" class="inline-block ml-2 text-sm text-emerald-700 font-semibold hover:underline">Program Wellbeing →</a>
<a href="{{ route('admin.tenants.perusahaans.assessments', $perusahaan) }}" class="inline-block ml-2 text-sm text-emerald-700 font-semibold hover:underline">Alokasi Asesmen →</a>

@php($v = 'perusahaans')
@include('admin.tenants._lifecycle-actions', ['tenant' => $perusahaan, 'vertical' => $v])
@include('admin.tenants._associate-form', ['tenant' => $perusahaan])
@include('admin.tenants._view-as-form')
@endsection
