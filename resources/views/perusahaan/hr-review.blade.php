@extends('layouts.app')

@section('content')
@php
    $typeLabels = ['psikolog' => 'Psikolog', 'nutrisionis' => 'Nutrisionis', 'fisioterapis' => 'Fisioterapis', 'karir' => 'Konselor Karir'];
@endphp

<div class="flex items-center justify-between mb-1">
    <h1 class="text-xl font-bold text-pine-900">Review Konsultasi</h1>
    <span class="text-xs font-semibold bg-terra-100 text-terra-800 rounded-lg px-2.5 py-1">{{ $requests->count() }} menunggu</span>
</div>
<p class="text-sm text-gray-500 mb-5">Setujui untuk tugaskan konsultan, atau tolak dengan alasan. Karyawan tidak tahu siapa yang menolak — hanya alasan.</p>

@if(session('success'))
<div class="mb-4 bg-pine-50 border border-pine-200 rounded-xl p-3 text-sm text-pine-800" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="mb-4 bg-terra-50 border border-terra-200 rounded-xl p-3 text-sm text-terra-800" role="alert">
    <ul class="space-y-0.5 list-disc list-inside">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- Filter jenis konsultan --}}
<div class="flex flex-wrap gap-2 mb-4">
    <a href="{{ route('perusahaan.review') }}"
       class="text-xs font-semibold rounded-full px-3 py-1.5 {{ !$activeFilter ? 'bg-pine-700 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:border-pine-400' }}">Semua</a>
    @foreach($typeLabels as $key => $label)
    <a href="{{ route('perusahaan.review', ['type' => $key]) }}"
       class="text-xs font-semibold rounded-full px-3 py-1.5 {{ $activeFilter === $key ? 'bg-pine-700 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:border-pine-400' }}">{{ $label }}</a>
    @endforeach
</div>

@forelse($requests as $session)
@php
    $pool = $pools->get($session->konsultan_type) ?? collect();
    $pick = $suggested[$session->id] ?? null;
@endphp
<div class="portal-card p-5 mb-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-pine-900">{{ $session->employee?->name ?? '—' }}
                <span class="font-normal text-gray-400">·</span>
                <span class="text-xs font-semibold bg-pine-100 text-pine-800 rounded-full px-2 py-0.5">{{ $typeLabels[$session->konsultan_type] ?? ucfirst($session->konsultan_type) }}</span>
            </p>
            <p class="text-xs text-gray-500 mt-1">dikirim {{ $session->created_at->diffForHumans() }}</p>
            @if($session->topic_category)
            <p class="text-xs text-gray-500 mt-0.5">Topik: <span class="font-medium text-gray-700">{{ $session->topic_category }}</span></p>
            @endif
            {{-- Isi keluhan TIDAK dirender untuk HR: hanya karyawan + konsultan yang
                 boleh tahu (lihat janji privasi di portal karyawan). HR cukup tahu
                 jenis konsultan + kategori topik untuk menugaskan. --}}
            <p class="text-[11px] text-gray-400 mt-1.5">Isi cerita bersifat rahasia — hanya dibaca konsultan yang ditugaskan.</p>
        </div>
    </div>

    <div class="mt-3 grid sm:grid-cols-2 gap-3">
        {{-- Approve --}}
        <form method="POST" action="{{ route('perusahaan.review.approve', $session) }}" class="rounded-xl border border-pine-200 bg-pine-50/40 p-3">
            @csrf
            <label class="block text-xs font-semibold text-pine-900 mb-1.5" for="approve-{{ $session->id }}">Tugaskan konsultan</label>
            @if($pick)
            <p class="text-[11px] text-gray-600 mb-1.5">Saran sistem: <span class="font-semibold text-pine-800">{{ $pick['name'] }}</span> (beban terendah)</p>
            @endif
            <div class="flex gap-2">
                <select id="approve-{{ $session->id }}" name="consultant_id" class="flex-1 rounded-lg border border-gray-300 px-2.5 py-2 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-pine-500">
                    <option value="">— otomatis ({{ $pick['name'] ?? 'beban terendah' }}) —</option>
                    @foreach($pool as $c)
                    <option value="{{ $c['id'] }}" @selected($pick && $pick['id'] === $c['id'])>{{ $c['name'] }}</option>
                    @endforeach
                </select>
                <button type="submit" class="shrink-0 rounded-lg bg-pine-700 text-white text-xs font-semibold px-3 py-2 hover:bg-pine-800 focus:outline-none focus:ring-2 focus:ring-pine-500 focus:ring-offset-1">Setujui</button>
            </div>
            <input type="text" name="override_reason" maxlength="1000"
                   placeholder="Alasan override (opsional, hanya bila beda dari saran)"
                   class="mt-2 w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-pine-500">
            @if($pool->isEmpty())
            <p class="text-[11px] text-terra-700 mt-1.5">Pool konsultan tipe ini kosong — tambah konsultan dulu.</p>
            @endif
        </form>

        {{-- Reject --}}
        <form method="POST" action="{{ route('perusahaan.review.reject', $session) }}" class="rounded-xl border border-red-200 bg-red-50/40 p-3"
              onsubmit="return confirm('Tolak request ini? Alasan akan terlihat oleh karyawan.')">
            @csrf
            <label class="block text-xs font-semibold text-red-900 mb-1.5" for="reject-{{ $session->id }}">Tolak dengan alasan</label>
            <div class="flex gap-2">
                <input id="reject-{{ $session->id }}" name="reason" required maxlength="1000"
                       placeholder="Misal: kuota diprioritaskan, dsb."
                       class="flex-1 rounded-lg border border-gray-300 px-2.5 py-2 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-red-400">
                <button type="submit" class="shrink-0 rounded-lg bg-red-600 text-white text-xs font-semibold px-3 py-2 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-1">Tolak</button>
            </div>
        </form>
    </div>
</div>
@empty
<div class="portal-card p-8 text-center text-sm text-gray-400">
    Tidak ada request menunggu review.
</div>
@endforelse
@endsection
