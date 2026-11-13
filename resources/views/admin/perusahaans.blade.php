@extends('layouts.app')

@section('content')
<div class="flex items-baseline justify-between mb-2">
    <h1 class="text-xl font-bold">Perusahaan B2B</h1>
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-emerald-700 font-semibold hover:underline">← Dashboard</a>
</div>
<p class="text-sm text-gray-500 mb-6">
    Provision tenant perusahaan pelanggan B2B: buat perusahaan + akun HR pertama
    sekaligus. Karyawan tidak dibuat di sini — HR mengundang lewat roster
    (nama + email karyawan), karyawan melengkapi akunnya sendiri via tautan undangan.
</p>

<form method="POST" action="{{ route('admin.perusahaans.store') }}"
      class="bg-white border rounded-lg p-6 mb-8 grid md:grid-cols-2 gap-4 max-w-3xl">
    @csrf
    <h2 class="md:col-span-2 font-semibold text-gray-700">Perusahaan + HR baru</h2>
    <label class="text-sm">Nama perusahaan
        <input name="name" required maxlength="100" class="w-full border rounded p-2 mt-1" placeholder="PT Contoh Sejahtera">
    </label>
    <label class="text-sm">Brand tampilan
        <input name="brand" required maxlength="100" class="w-full border rounded p-2 mt-1" placeholder="Contoh">
    </label>
    <label class="text-sm">Rate PEPM (Rp, opsional)
        <input name="pepm_rate" type="number" min="0" class="w-full border rounded p-2 mt-1" placeholder="15000">
    </label>
    <label class="text-sm">Sesi/karyawan/bulan (opsional)
        <input name="sessions_per_employee_month" type="number" min="1" max="30" class="w-full border rounded p-2 mt-1" placeholder="2">
    </label>
    <label class="text-sm">Nama HR
        <input name="hr_name" required maxlength="100" class="w-full border rounded p-2 mt-1" placeholder="Budi Santoso">
    </label>
    <label class="text-sm">Email HR (akun login)
        <input name="hr_email" type="email" required maxlength="150" class="w-full border rounded p-2 mt-1" placeholder="hr@contoh.co.id">
    </label>
    <label class="text-sm md:col-span-2">Password awal HR (min 8)
        <input name="hr_password" type="text" required minlength="8" class="w-full border rounded p-2 mt-1" autocomplete="off">
    </label>
    @if($errors->any())
        <p class="md:col-span-2 text-sm text-red-600">{{ $errors->first() }}</p>
    @endif
    <button type="submit" class="md:col-span-2 bg-emerald-600 text-white font-semibold rounded py-2 hover:bg-emerald-700">
        Buat perusahaan &amp; akun HR
    </button>
</form>

<table class="w-full bg-white border rounded-lg text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Perusahaan</th>
        <th class="p-3">HR</th>
        <th class="p-3">Karyawan</th>
        <th class="p-3">Rate PEPM</th>
        <th class="p-3">Kuota sesi/bln</th>
        <th class="p-3">Status</th>
    </tr></thead>
    <tbody>
        @forelse($perusahaans as $p)
            <tr class="border-b">
                <td class="p-3 font-semibold">{{ $p->brand }}<div class="text-xs text-gray-400">{{ $p->name }}</div></td>
                <td class="p-3">
                    @foreach($p->admins->pluck('user') as $hr)
                        <div>{{ trim(($hr?->firstname ?? '') . ' ' . ($hr?->lastname ?? '')) }}</div>
                        <div class="text-xs text-gray-400">{{ $hr?->email }}</div>
                    @endforeach
                </td>
                <td class="p-3">{{ $p->employees_count }}</td>
                <td class="p-3">Rp{{ number_format($p->pepm_rate, 0, ',', '.') }}</td>
                <td class="p-3">{{ $p->sessions_per_employee_month }}</td>
                <td class="p-3">
                    @if($p->is_active)
                        <span class="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 font-semibold">Aktif</span>
                    @else
                        <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-500">Nonaktif</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td class="p-3 text-gray-400" colspan="6">Belum ada perusahaan. Buat lewat form di atas.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
