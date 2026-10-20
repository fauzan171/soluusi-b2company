@extends('layouts.app')

@section('content')
<div class="mb-4 flex items-center justify-between flex-wrap gap-2">
    <div>
        <h1 class="text-xl font-bold">Manajemen Karyawan</h1>
        <p class="text-sm text-gray-500">Halaman administratif — data individu hanya untuk pengelolaan program. Konten sesi tidak pernah tampil.</p>
    </div>
    <a href="{{ route('perusahaan.dashboard') }}" class="text-sm text-pine-700 hover:underline">← Dashboard</a>
</div>

<details class="portal-card p-4 mb-4">
    <summary>Tambah karyawan</summary>
    <form method="POST" action="{{ route('perusahaan.employees.store') }}" class="space-y-3 mt-3">
        @csrf
        <label>Nama <input name="name" value="{{ old('name') }}" required maxlength="255"></label>
        <label>Email <input type="email" name="email" value="{{ old('email') }}" required></label>
        <button>Simpan</button>
    </form>
</details>
<div class="portal-card overflow-hidden">
    <table class="w-full text-sm">
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
            @forelse($employees as $emp)
            <tr class="border-b last:border-0 hover:bg-gray-50">
                <td class="p-3 font-medium">{{ $emp->name }}</td>
                <td class="p-3 text-gray-500">{{ $emp->email }}</td>
                <td class="p-3"><span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-600">{{ $emp->departement?->name ?? '—' }}</span></td>
                <td class="p-3">
                    @if($emp->consent_at)
                    <span class="text-xs px-2 py-0.5 rounded bg-pine-100 text-pine-800">Setuju {{ $emp->consent_at->format('d M y') }}</span>
                    @else
                    <span class="text-xs px-2 py-0.5 rounded bg-amber-100 text-terra-800">Belum consent</span>
                    @endif
                    <details><summary>Catat/koreksi consent eksternal</summary>
                        <form method="POST" action="{{ route('perusahaan.employees.consent', $emp) }}">
                            @csrf
                            <label>Status <select name="granted"><option value="1">Setuju</option><option value="0">Cabut</option></select></label>
                            <label>Referensi bukti <input name="evidence" required maxlength="2000"></label>
                            <label>Waktu persetujuan/perubahan <input type="datetime-local" name="agreed_at" required></label>
                            <button>Simpan jejak consent</button>
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
            @empty
            <tr><td colspan="6" class="p-4 text-gray-400">Belum ada karyawan terdaftar.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<p class="text-xs text-gray-400 mt-3">{{ $employees->count() }} karyawan terdaftar dari {{ $perusahaan->employee_count ?? '?' }} kuota kontrak.</p>
@endsection
