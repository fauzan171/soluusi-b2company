@extends('layouts.app')

@section('content')
@php
    $isSekolah = $vertical === 'sekolah';
    $dashboard = $isSekolah ? route('sekolah.dashboard') : route('perusahaan.dashboard');
    $toggleRoute = $isSekolah ? 'sekolah.consultants.pool.toggle' : 'perusahaan.consultants.pool.toggle';
    $aktif = $rows->where('is_active', true)->count();
@endphp
<div class="mb-4 flex items-center justify-between flex-wrap gap-2">
    <div>
        <h1 class="text-xl font-bold text-pine-900">Pool Konsultan</h1>
        <p class="text-sm text-gray-500">
            Pilih konsultan mana yang aktif untuk {{ $isSekolah ? 'sekolah' : 'perusahaan' }} ini.
            Daftar konsultan ditetapkan Soluusi sesuai kontrak — Anda hanya bisa menyalakan atau mematikan.
        </p>
    </div>
    <a href="{{ $dashboard }}" class="text-sm text-pine-700 hover:underline">← Dashboard</a>
</div>

@if(session('success'))
<div class="portal-card p-3 mb-4 bg-pine-50 text-pine-800 text-sm" role="status">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-2 gap-3 mb-4">
    <div class="dash-card">
        <p class="text-xs text-gray-500 mb-1">Konsultan aktif</p>
        <p class="text-2xl font-bold text-pine-900">{{ $aktif }}</p>
    </div>
    <div class="dash-card">
        <p class="text-xs text-gray-500 mb-1">Total di kontrak</p>
        <p class="text-2xl font-bold text-pine-900">{{ $rows->count() }}</p>
    </div>
</div>

@if($rows->isEmpty())
<div class="portal-card p-8 text-center">
    <p class="text-gray-500 mb-2">Belum ada konsultan di kontrak {{ $isSekolah ? 'sekolah' : 'perusahaan' }} ini.</p>
    <p class="text-sm text-gray-400">Hubungi Soluusi untuk menambahkan konsultan ke program Anda.</p>
</div>
@else
<div class="portal-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b text-left text-gray-500 text-xs bg-gray-50">
                    <th class="p-3">Nama</th>
                    <th class="p-3">Kategori</th>
                    <th class="p-3">Status</th>
                    <th class="p-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr class="border-b last:border-0 hover:bg-gray-50">
                    <td class="p-3">
                        <div class="flex items-center gap-3">
                            @if($row['photo_url'])
                            <img src="{{ $row['photo_url'] }}" alt="Foto {{ $row['name'] }}" data-initial="{{ mb_strtoupper(mb_substr($row['name'], 0, 1)) }}" class="w-10 h-10 rounded-full object-cover bg-pine-100 shrink-0"
                                onerror="this.replaceWith(Object.assign(document.createElement('div'),{className:'w-10 h-10 rounded-full bg-pine-100 flex items-center justify-center text-pine-700 font-bold text-sm shrink-0',textContent:this.dataset.initial}))">
                            @else
                            <div class="w-10 h-10 rounded-full bg-pine-100 flex items-center justify-center text-pine-700 font-bold text-sm shrink-0">
                                {{ strtoupper(substr($row['name'], 0, 1)) }}
                            </div>
                            @endif
                            <div>
                                <p class="font-medium">{{ $row['name'] }}</p>
                                @if($row['headline'])
                                <p class="text-xs text-pine-700 font-medium">{{ $row['headline'] }}</p>
                                @endif
                                @if(!empty($row['profile']['education']))
                                <p class="text-xs text-gray-400">{{ collect($row['profile']['education'])->pluck('institution')->implode(', ') }}</p>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="p-3 text-gray-500">
                        {{ $row['category'] ?: '—' }}
                        @if(!empty($row['profile']['skills']))
                        <p class="text-xs text-gray-400 mt-1">{{ collect($row['profile']['skills'])->implode(', ') }}</p>
                        @endif
                        @if(!empty($row['profile']['certificates']))
                        <p class="text-xs text-gray-400 mt-1">Sertifikasi: {{ collect($row['profile']['certificates'])->pluck('name')->implode(', ') }}</p>
                        @endif
                    </td>
                    <td class="p-3">
                        @if($row['is_active'])
                        <span class="text-xs px-2 py-0.5 rounded bg-pine-100 text-pine-800">Aktif</span>
                        @else
                        <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-600">Nonaktif</span>
                        @endif
                    </td>
                    <td class="p-3 text-right">
                        <form method="POST" action="{{ route($toggleRoute) }}" class="inline">
                            @csrf
                            <input type="hidden" name="consultant_id" value="{{ $row['id'] }}">
                            <button class="text-xs font-semibold border rounded px-3 py-1 {{ $row['is_active'] ? 'text-terra-800 border-terra-800 hover:bg-amber-50' : 'text-pine-700 border-pine-700 hover:bg-pine-50' }}">
                                {{ $row['is_active'] ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<p class="text-xs text-gray-400 mt-3">
    Konsultan nonaktif tidak muncul di halaman booking {{ $isSekolah ? 'siswa' : 'karyawan' }}. Perubahan tercatat di audit log.
</p>
@endif
@endsection