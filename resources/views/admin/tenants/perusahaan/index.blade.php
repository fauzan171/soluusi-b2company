@extends('layouts.app')

@section('title', 'Tenant Perusahaan — Admin')

@section('content')
<div class="flex items-baseline justify-between mb-2">
    <h1 class="text-xl font-bold">Tenant Perusahaan</h1>
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-emerald-700 font-semibold hover:underline">← Dashboard</a>
</div>
<p class="text-sm text-gray-500 mb-6">Kelola tenant perusahaan (CAP): brand, rate PEPM, kuota sesi. Provisioning akun HR tetap lewat halaman <a href="{{ route('admin.perusahaans.index') }}" class="text-emerald-700 hover:underline">Perusahaan B2B</a>.</p>

<form method="GET" class="mb-6 flex gap-3 items-end max-w-xl">
    <div class="flex-1">
        <label for="q" class="block text-xs text-gray-500 mb-1">Cari nama / brand</label>
        <input id="q" name="q" value="{{ $q }}" class="w-full border rounded px-3 py-2 text-sm" placeholder="cth: Maju">
    </div>
    <button type="submit" class="bg-emerald-600 text-white text-sm font-semibold rounded px-4 py-2 hover:bg-emerald-700">Cari</button>
</form>

<div class="overflow-x-auto bg-white border rounded-lg"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Perusahaan</th>
        <th class="p-3">HR</th>
        <th class="p-3">Karyawan</th>
        <th class="p-3">Rate PEPM</th>
        <th class="p-3">Kuota sesi/bln</th>
        <th class="p-3">Status</th>
        <th class="p-3"></th>
    </tr></thead>
    <tbody>
        @forelse($perusahaans as $p)
        <tr class="border-b">
            <td class="p-3 font-semibold">
                <a href="{{ route('admin.tenants.perusahaans.show', $p) }}" class="hover:underline">{{ $p->brand }}</a>
                <div class="text-xs text-gray-400">{{ $p->name }}</div>
            </td>
            <td class="p-3">
                @foreach($p->admins->pluck('user') as $hr)
                    <div>{{ trim(($hr?->firstname ?? '') . ' ' . ($hr?->lastname ?? '')) }}</div>
                @endforeach
            </td>
            <td class="p-3">{{ $p->employees_count }}</td>
            <td class="p-3">Rp{{ number_format($p->pepm_rate, 0, ',', '.') }}</td>
            <td class="p-3">{{ $p->sessions_per_employee_month }}</td>
            <td class="p-3">
                @if($p->is_active)
                    <span class="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 font-semibold">Aktif</span>
                @else
                    <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-500">Pending</span>
                @endif
            </td>
            <td class="p-3 text-right">
                <a href="{{ route('admin.tenants.perusahaans.edit', $p) }}" class="text-sm text-emerald-700 font-semibold hover:underline">Edit</a>
            </td>
        </tr>
        @empty
        <tr><td class="p-3 text-gray-400" colspan="7">Tidak ada perusahaan{{ $q !== '' ? ' yang cocok dengan pencarian' : '' }}.</td></tr>
        @endforelse
    </tbody>
</table></div>
{{ $perusahaans->links() }}
@endsection
