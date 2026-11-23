@extends('layouts.app')

@section('content')
<div class="flex items-baseline justify-between mb-2">
    <h1 class="text-xl font-bold">Instrumen Assessment</h1>
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-emerald-700 font-semibold hover:underline">← Dashboard</a>
</div>
<p class="text-sm text-gray-500 mb-6">
    Pustaka instrumen skrining. MCMI-III (klinis) hanya bisa diakses karyawan
    dengan grant eksplisit — tidak untuk skrining massal.
</p>

@if(session('success'))
<div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>
@endif

<div class="overflow-x-auto bg-white border rounded-lg mb-8"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Kode</th><th class="p-3">Instrumen</th><th class="p-3">Domain</th>
        <th class="p-3">Item</th><th class="p-3">Status</th><th class="p-3">Aksi</th>
    </tr></thead>
    <tbody>
        @foreach($instruments as $instrument)
            <tr class="border-b">
                <td class="p-3 font-mono text-xs">{{ $instrument->code }}</td>
                <td class="p-3 font-semibold">{{ $instrument->name }}</td>
                <td class="p-3">
                    @if($instrument->domain)
                        <span class="text-xs px-2 py-0.5 rounded bg-blue-100 text-blue-700 font-semibold">{{ $instrument->domain }}</span>
                    @else
                        <span class="text-xs text-gray-400">umum</span>
                    @endif
                    @if($instrument->is_addon)
                        <span class="ml-1 text-xs px-2 py-0.5 rounded bg-amber-100 text-amber-700 font-semibold">Add-on</span>
                    @endif
                </td>
                <td class="p-3">{{ $instrument->item_count ?: '—' }}</td>
                <td class="p-3">
                    @if($instrument->is_active)
                        <span class="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 font-semibold">Aktif</span>
                    @else
                        <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-500">Nonaktif</span>
                    @endif
                </td>
                <td class="p-3">
                    <form method="POST" action="{{ route('admin.instruments.toggle', $instrument) }}" class="inline">
                        @csrf
                        <button type="submit" class="text-xs font-semibold rounded px-3 py-1.5 {{ $instrument->is_active ? 'bg-gray-200 text-gray-700' : 'bg-emerald-700 text-white' }}">
                            {{ $instrument->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table></div>

<h2 class="text-lg font-bold mb-3">Grant Akses Instrumen Klinis (MCMI-III)</h2>

<form method="GET" action="{{ route('admin.instruments.index') }}" class="flex gap-2 mb-4 max-w-md">
    <input type="text" name="q" value="{{ $q }}" placeholder="Cari karyawan (nama/email)…"
           class="flex-1 border rounded px-3 py-2 text-sm">
    <button type="submit" class="bg-emerald-700 text-white text-sm font-semibold rounded px-4">Cari</button>
</form>

@if($q !== null && $q !== '')
<div class="overflow-x-auto bg-white border rounded-lg mb-6"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Karyawan</th><th class="p-3">Perusahaan</th><th class="p-3">Aksi</th>
    </tr></thead>
    <tbody>
        @forelse($employees as $employee)
            <tr class="border-b">
                <td class="p-3">
                    <span class="font-semibold">{{ $employee->name }}</span>
                    <span class="text-xs text-gray-400 ml-2">{{ $employee->email }}</span>
                </td>
                <td class="p-3 text-xs text-gray-500">{{ $employee->perusahaan?->name }}</td>
                <td class="p-3">
                    <form method="POST" action="{{ route('admin.instruments.grants.store') }}">
                        @csrf
                        <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                        <select name="instrument_code" class="border rounded text-xs px-2 py-1.5 mr-2">
                            @foreach($instruments->where('domain', 'clinical') as $clinical)
                                <option value="{{ $clinical->code }}">{{ $clinical->code }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="reason" required maxlength="500" placeholder="Indikasi klinis (wajib)…"
                               class="border rounded text-xs px-2 py-1.5 mr-2 w-56">
                        <button type="submit" class="text-xs bg-amber-600 text-white rounded px-3 py-1.5 font-semibold">Grant</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="3" class="p-6 text-center text-gray-400">Tidak ada karyawan cocok.</td></tr>
        @endforelse
    </tbody>
</table></div>
@endif

<div class="overflow-x-auto bg-white border rounded-lg"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Karyawan</th><th class="p-3">Instrumen</th><th class="p-3">Diberikan</th><th class="p-3">Aksi</th>
    </tr></thead>
    <tbody>
        @forelse($grants as $grant)
            <tr class="border-b">
                <td class="p-3"><span class="font-semibold">{{ $grant->employee?->name }}</span>
                    <span class="text-xs text-gray-400 ml-2">{{ $grant->employee?->email }}</span></td>
                <td class="p-3 font-mono text-xs">{{ $grant->instrument_code }}</td>
                <td class="p-3 text-xs text-gray-500">{{ $grant->created_at->translatedFormat('d M Y') }} oleh {{ $grant->grantor?->firstname }}</td>
                <td class="p-3">
                    <form method="POST" action="{{ route('admin.instruments.grants.destroy', $grant) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs bg-red-600 text-white rounded px-3 py-1.5 font-semibold">Revoke</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="p-6 text-center text-gray-400">Belum ada grant aktif.</td></tr>
        @endforelse
    </tbody>
</table></div>
@endsection
