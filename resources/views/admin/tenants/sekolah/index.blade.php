@extends('layouts.app')

@section('title', 'Tenant Sekolah — Admin')

@section('content')
<div class="flex items-baseline justify-between mb-2">
    <h1 class="text-xl font-bold">Tenant Sekolah</h1>
    <div class="flex gap-4 items-center">
        <a href="{{ route('admin.tenants.sekolahs.create') }}" class="text-sm bg-emerald-600 text-white font-semibold rounded px-3 py-1.5 hover:bg-emerald-700">+ Sekolah baru</a>
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-emerald-700 font-semibold hover:underline">← Dashboard</a>
    </div>
</div>
<p class="text-sm text-gray-500 mb-6">Kelola tenant sekolah (BK): brand, kuota sesi siswa. Sekolah baru dibuat di sini (sebelumnya hanya via seeder) dan diaktifkan lewat aksi ops.</p>

<form method="GET" class="mb-6 flex gap-3 items-end max-w-xl">
    <div class="flex-1">
        <label for="q" class="block text-xs text-gray-500 mb-1">Cari nama / brand</label>
        <input id="q" name="q" value="{{ $q }}" class="w-full border rounded px-3 py-2 text-sm" placeholder="cth: Cendekia">
    </div>
    <button type="submit" class="bg-emerald-600 text-white text-sm font-semibold rounded px-4 py-2 hover:bg-emerald-700">Cari</button>
</form>

<div class="overflow-x-auto bg-white border rounded-lg"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Sekolah</th>
        <th class="p-3">BK</th>
        <th class="p-3">Siswa</th>
        <th class="p-3">Kuota sesi/siswa/bln</th>
        <th class="p-3">Status</th>
        <th class="p-3"></th>
    </tr></thead>
    <tbody>
        @forelse($sekolahs as $s)
        <tr class="border-b">
            <td class="p-3 font-semibold">
                <a href="{{ route('admin.tenants.sekolahs.show', $s) }}" class="hover:underline">{{ $s->brand }}</a>
                <div class="text-xs text-gray-400">{{ $s->name }}{{ $s->tingkat ? ' · ' . $s->tingkat : '' }}</div>
            </td>
            <td class="p-3">
                @foreach($s->admins->pluck('user') as $bk)
                    <div>{{ trim(($bk?->firstname ?? '') . ' ' . ($bk?->lastname ?? '')) }}</div>
                @endforeach
            </td>
            <td class="p-3">{{ $s->students_count }}</td>
            <td class="p-3">{{ $s->sessions_per_student_month }}</td>
            <td class="p-3">
                @if($s->is_active)
                    <span class="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 font-semibold">Aktif</span>
                @else
                    <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-500">Pending</span>
                @endif
            </td>
            <td class="p-3 text-right">
                <a href="{{ route('admin.tenants.sekolahs.edit', $s) }}" class="text-sm text-emerald-700 font-semibold hover:underline">Edit</a>
            </td>
        </tr>
        @empty
        <tr><td class="p-3 text-gray-400" colspan="6">Tidak ada sekolah{{ $q !== '' ? ' yang cocok dengan pencarian' : '' }}.</td></tr>
        @endforelse
    </tbody>
</table></div>
{{ $sekolahs->links() }}
@endsection
