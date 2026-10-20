@extends('layouts.app')

@section('content')
<div class="flex items-baseline justify-between mb-2">
    <h1 class="text-xl font-bold">Paket Klinik — {{ $klinik->brand }}</h1>
    <div class="flex gap-4 text-sm">
        <a href="{{ route('admin.referral-overview') }}" class="text-emerald-700 font-semibold hover:underline">← Referral HPA</a>
        <a href="{{ route('admin.dashboard') }}" class="text-emerald-700 font-semibold hover:underline">Dashboard</a>
    </div>
</div>
<p class="text-sm text-gray-500 mb-6">
    Hanya super admin yang membuat paket untuk mitra (harga sesuai budget klinik,
    termasuk paket 1-on-1 & mental health check-up). Klinik melihatnya read-only.
</p>

{{-- Tambah paket --}}
<form method="POST" action="{{ route('admin.kliniks.packages.store', $klinik) }}" class="bg-white border rounded-lg p-5 mb-8 grid md:grid-cols-12 gap-3 items-end">
    @csrf
    <div class="md:col-span-3">
        <label class="block text-xs text-gray-500 mb-1">Nama paket *</label>
        <input name="name" required value="{{ old('name') }}" placeholder="cth: Mental Health Check-Up" class="w-full border rounded px-3 py-2 text-sm">
    </div>
    <div class="md:col-span-3">
        <label class="block text-xs text-gray-500 mb-1">Deskripsi</label>
        <input name="description" value="{{ old('description') }}" placeholder="Isi paket / skrining" class="w-full border rounded px-3 py-2 text-sm">
    </div>
    <div class="md:col-span-2">
        <label class="block text-xs text-gray-500 mb-1">Harga (Rp) *</label>
        <input type="number" name="price" required min="0" value="{{ old('price') }}" class="w-full border rounded px-3 py-2 text-sm">
    </div>
    <div class="md:col-span-1">
        <label class="block text-xs text-gray-500 mb-1">Menit *</label>
        <input type="number" name="duration_minutes" required min="10" value="{{ old('duration_minutes', 60) }}" class="w-full border rounded px-3 py-2 text-sm">
    </div>
    <div class="md:col-span-2">
        <label class="block text-xs text-gray-500 mb-1">Mode *</label>
        <select name="mode" required class="w-full border rounded px-3 py-2 text-sm">
            <option value="online">Online</option>
            <option value="offline">Offline</option>
        </select>
    </div>
    <div class="md:col-span-1">
        <button class="w-full bg-emerald-700 text-white rounded px-3 py-2 text-sm font-semibold">Tambah</button>
    </div>
</form>

{{-- Daftar paket --}}
@foreach($packages as $p)
<form method="POST" action="{{ route('admin.kliniks.packages.update', $p) }}" class="bg-white border rounded-lg p-5 mb-4 grid md:grid-cols-12 gap-3 items-end">
    @csrf
    @method('PUT')
    <div class="md:col-span-3">
        <label class="block text-xs text-gray-500 mb-1">Nama paket</label>
        <input name="name" required value="{{ $p->name }}" class="w-full border rounded px-3 py-2 text-sm">
    </div>
    <div class="md:col-span-3">
        <label class="block text-xs text-gray-500 mb-1">Deskripsi</label>
        <input name="description" value="{{ $p->description }}" class="w-full border rounded px-3 py-2 text-sm">
    </div>
    <div class="md:col-span-2">
        <label class="block text-xs text-gray-500 mb-1">Harga (Rp)</label>
        <input type="number" name="price" required min="0" value="{{ $p->price }}" class="w-full border rounded px-3 py-2 text-sm">
    </div>
    <div class="md:col-span-1">
        <label class="block text-xs text-gray-500 mb-1">Menit</label>
        <input type="number" name="duration_minutes" required min="10" value="{{ $p->duration_minutes }}" class="w-full border rounded px-3 py-2 text-sm">
    </div>
    <div class="md:col-span-2">
        <label class="block text-xs text-gray-500 mb-1">Mode</label>
        <select name="mode" required class="w-full border rounded px-3 py-2 text-sm">
            <option value="online" @selected($p->mode === 'online')>Online</option>
            <option value="offline" @selected($p->mode === 'offline')>Offline</option>
        </select>
    </div>
    <div class="md:col-span-1 flex gap-2">
        <button class="flex-1 bg-emerald-700 text-white rounded px-2 py-2 text-sm font-semibold">Simpan</button>
    </div>
</form>
<form method="POST" action="{{ route('admin.kliniks.packages.destroy', $p) }}" onsubmit="return confirm('Hapus paket {{ addslashes($p->name) }}?')" class="-mt-3 mb-4">
    @csrf
    @method('DELETE')
    <button class="text-xs text-red-600 hover:underline">Hapus "{{ $p->name }}"</button>
</form>
@endforeach

@if($packages->isEmpty())
<p class="text-sm text-gray-400">Belum ada paket untuk klinik ini.</p>
@endif
@endsection
