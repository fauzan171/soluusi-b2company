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
                    <td class="p-3 font-medium">{{ $row['name'] }}</td>
                    <td class="p-3 text-gray-500">{{ $row['category'] ?: '—' }}</td>
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