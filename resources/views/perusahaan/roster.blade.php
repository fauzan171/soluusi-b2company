@extends('layouts.app')

@section('content')
@php
    $total = $employees->count();
    $aktif = $employees->whereNotNull('user_id')->count();
    $consent = $employees->whereNotNull('consent_at')->count();
    $pending = $total - $aktif;
@endphp
<div class="mb-4 flex items-center justify-between flex-wrap gap-2">
    <div>
        <h1 class="text-xl font-bold text-pine-900">Manajemen Karyawan</h1>
        <p class="text-sm text-gray-500">Halaman administratif — data individu hanya untuk pengelolaan program. Konten sesi tidak pernah tampil.</p>
    </div>
    <a href="{{ route('perusahaan.dashboard') }}" class="text-sm text-pine-700 hover:underline">← Dashboard</a>
</div>

{{-- Summary cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    <div class="dash-card">
        <p class="text-xs text-gray-500 mb-1">Total Karyawan</p>
        <p class="text-2xl font-bold text-pine-900">{{ $total }}</p>
    </div>
    <div class="dash-card">
        <p class="text-xs text-gray-500 mb-1">Akun login aktif</p>
        <p class="text-2xl font-bold text-pine-900">{{ $aktif }}</p>
    </div>
    <div class="dash-card">
        <p class="text-xs text-gray-500 mb-1">Sudah Consent</p>
        <p class="text-2xl font-bold text-pine-900">{{ $consent }}</p>
    </div>
    <div class="dash-card">
        <p class="text-xs text-gray-500 mb-1">Belum Diundang</p>
        <p class="text-2xl font-bold text-terra-800">{{ $pending }}</p>
    </div>
</div>

<details class="portal-card p-4 mb-4">
    <summary class="cursor-pointer font-semibold text-pine-900">Tambah karyawan</summary>
    <form method="POST" action="{{ route('perusahaan.employees.store') }}" class="space-y-3 mt-3">
        @csrf
        <div class="grid md:grid-cols-2 gap-3">
            <label class="block text-sm">
                <span class="text-gray-600">Nama</span>
                <input name="name" value="{{ old('name') }}" required maxlength="255" class="mt-1 block w-full rounded border-gray-300 text-sm focus:border-pine-500 focus:ring-pine-500">
            </label>
            <label class="block text-sm">
                <span class="text-gray-600">Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 block w-full rounded border-gray-300 text-sm focus:border-pine-500 focus:ring-pine-500">
            </label>
            <label class="block text-sm">
                <span class="text-gray-600">Divisi <span class="text-gray-400">(opsional)</span></span>
                <select name="departement_id" class="mt-1 block w-full rounded border-gray-300 text-sm focus:border-pine-500 focus:ring-pine-500">
                    <option value="">— Belum ditentukan —</option>
                    @foreach($departements as $dept)
                        <option value="{{ $dept->id }}" @selected(old('departement_id') == $dept->id)>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <button class="btn-pine text-sm">Simpan</button>
    </form>
</details>

@if($total === 0)
<div class="portal-card p-8 text-center">
    <p class="text-gray-500 mb-2">Belum ada karyawan terdaftar.</p>
    <p class="text-sm text-gray-400">Tambahkan karyawan pertama lewat form di atas untuk memulai program.</p>
</div>
@else
<div class="portal-card p-4 mb-3">
    <label class="block text-sm">
        <span class="text-gray-600">Cari karyawan</span>
        <input id="rosterSearch" type="search" placeholder="Ketik nama atau email…" class="mt-1 block w-full rounded border-gray-300 text-sm focus:border-pine-500 focus:ring-pine-500">
    </label>
</div>
<div class="portal-card overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-sm" id="rosterTable">
        <thead>
            <tr class="border-b text-left text-gray-500 text-xs bg-gray-50">
                <th class="p-3">Nama</th>
                <th class="p-3">Email</th>
                <th class="p-3">Departemen</th>
                <th class="p-3">Consent</th>
                <th class="p-3">Akun</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $emp)
            <tr class="border-b last:border-0 hover:bg-gray-50" data-search="{{ Str::lower($emp->name . ' ' . $emp->email) }}">
                <td class="p-3 font-medium">{{ $emp->name }}</td>
                <td class="p-3 text-gray-500">{{ $emp->email }}</td>
                <td class="p-3"><span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-600">{{ $emp->departement?->name ?? '—' }}</span></td>
                <td class="p-3">
                    @if($emp->consent_at)
                    <span class="text-xs px-2 py-0.5 rounded bg-pine-100 text-pine-800">Setuju {{ $emp->consent_at->translatedFormat('d M y') }}</span>
                    @else
                    <span class="text-xs px-2 py-0.5 rounded bg-amber-100 text-terra-800">Belum consent</span>
                    @endif
                    <details class="mt-1">
                        <summary class="text-xs text-gray-500 cursor-pointer hover:text-pine-700">Catat/koreksi consent eksternal</summary>
                        <form method="POST" action="{{ route('perusahaan.employees.consent', $emp) }}" class="mt-2 space-y-2 bg-gray-50 p-3 rounded">
                            @csrf
                            <label class="block text-xs">
                                <span class="text-gray-600">Status</span>
                                <select name="granted" class="mt-1 block w-full rounded border-gray-300 text-sm">
                                    <option value="1">Setuju</option>
                                    <option value="0">Cabut</option>
                                </select>
                            </label>
                            <label class="block text-xs">
                                <span class="text-gray-600">Referensi bukti</span>
                                <input name="evidence" required maxlength="2000" class="mt-1 block w-full rounded border-gray-300 text-sm">
                            </label>
                            <label class="block text-xs">
                                <span class="text-gray-600">Waktu persetujuan/perubahan</span>
                                <input type="datetime-local" name="agreed_at" required class="mt-1 block w-full rounded border-gray-300 text-sm">
                            </label>
                            <button class="btn-pine text-xs">Simpan jejak consent</button>
                        </form>
                    </details>
                </td>
                <td class="p-3">
                    @if($emp->user_id)
                    <span class="text-xs px-2 py-0.5 rounded bg-pine-100 text-pine-800">Akun aktif</span>
                    @else
                    <form method="POST" action="{{ route('perusahaan.employees.invite') }}" class="inline">
                        @csrf
                        <input type="hidden" name="employee_id" value="{{ $emp->id }}">
                        <button class="text-xs text-pine-700 font-semibold border border-pine-700 rounded px-3 py-1 hover:bg-pine-50">Undang via email</button>
                    </form>
                    @endif
                </td>
            </tr>
            @endforeach
            <tr id="rosterEmptySearch" class="hidden">
                <td colspan="5" class="p-6 text-center text-gray-400 text-sm">Tidak ada karyawan cocok dengan pencarian.</td>
            </tr>
        </tbody>
    </table>
    </div>
</div>

<p class="text-xs text-gray-400 mt-3">{{ $total }} karyawan terdaftar dari {{ $perusahaan->employee_count ?? '?' }} kuota kontrak.</p>

<script>
document.getElementById('rosterSearch').addEventListener('input', function (e) {
    const q = e.target.value.toLowerCase().trim();
    const rows = document.querySelectorAll('#rosterTable tbody tr[data-search]');
    let visible = 0;
    rows.forEach(r => {
        const match = !q || r.dataset.search.includes(q);
        r.classList.toggle('hidden', !match);
        if (match) visible++;
    });
    document.getElementById('rosterEmptySearch').classList.toggle('hidden', visible > 0);
});
</script>
@endif
@endsection
