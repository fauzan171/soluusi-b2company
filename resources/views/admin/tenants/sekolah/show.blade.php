@extends('layouts.app')

@section('title', $sekolah->brand . ' — Tenant Sekolah')

@section('content')
<div class="flex items-baseline justify-between mb-2">
    <h1 class="text-xl font-bold">{{ $sekolah->brand }}</h1>
    <a href="{{ route('admin.tenants.sekolahs.index') }}" class="text-sm text-emerald-700 font-semibold hover:underline">← Semua sekolah</a>
</div>

<div class="grid md:grid-cols-2 gap-6 mb-8">
    <dl class="bg-white border rounded-lg p-6 text-sm space-y-2">
        <div class="flex justify-between"><dt class="text-gray-500">Nama yayasan</dt><dd class="font-semibold">{{ $sekolah->name }}</dd></div>
        <div class="flex justify-between"><dt class="text-gray-500">Tingkat</dt><dd class="font-semibold">{{ $sekolah->tingkat ?: '—' }}</dd></div>
        <div class="flex justify-between"><dt class="text-gray-500">Kuota sesi/siswa/bln</dt><dd class="font-semibold">{{ $sekolah->sessions_per_student_month }}</dd></div>
        <div class="flex justify-between"><dt class="text-gray-500">MoU</dt><dd class="font-semibold">{{ optional($sekolah->mou_at)->format('d M Y') ?? '—' }}</dd></div>
        <div class="flex justify-between"><dt class="text-gray-500">Associate</dt><dd class="font-semibold">{{ $sekolah->associate?->fullname ?? '—' }}</dd></div>
        <div class="flex justify-between"><dt class="text-gray-500">AI insight</dt><dd class="font-semibold">{{ $sekolah->ai_enabled ? 'aktif' : 'nonaktif' }}</dd></div>
        <div class="flex justify-between"><dt class="text-gray-500">Status program</dt>
            <dd>@if($sekolah->is_active)<span class="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 font-semibold">Aktif</span>@else<span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-500">Pending</span>@endif</dd>
        </div>
    </dl>
    <div class="bg-white border rounded-lg p-6 text-sm">
        <h2 class="font-semibold text-gray-700 mb-2">BK (admin sekolah)</h2>
        @forelse($sekolah->admins as $a)
            <div class="flex justify-between border-b last:border-0 py-1">
                <span>{{ trim(($a->user?->firstname ?? '') . ' ' . ($a->user?->lastname ?? '')) }}</span>
                <span class="text-gray-400">{{ $a->role }}</span>
            </div>
        @empty
            <p class="text-gray-400">Belum ada BK terhubung.</p>
        @endforelse
        <div class="grid grid-cols-2 gap-2 pt-4 text-center">
            <div><div class="text-lg font-bold">{{ $sekolah->students_count }}</div><div class="text-xs text-gray-400">Siswa</div></div>
            <div><div class="text-lg font-bold">{{ $sekolah->sessions_count }}</div><div class="text-xs text-gray-400">Sesi</div></div>
        </div>
    </div>
</div>

<a href="{{ route('admin.tenants.sekolahs.edit', $sekolah) }}"
   class="inline-block bg-emerald-600 text-white font-semibold rounded px-4 py-2 text-sm hover:bg-emerald-700">Edit tenant</a>

@include('admin.tenants._lifecycle-actions', ['tenant' => $sekolah, 'vertical' => 'sekolahs'])
@include('admin.tenants._associate-form', ['tenant' => $sekolah])
@include('admin.tenants._view-as-form')

{{-- B3: assignment instrumen per sekolah (D9 minor-safety gate di controller). --}}
@include('admin.tenants.sekolah._assignment-panel', ['sekolah' => $sekolah])
@endsection
