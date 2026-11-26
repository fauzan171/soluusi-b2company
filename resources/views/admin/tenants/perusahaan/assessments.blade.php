@extends('layouts.app')

@section('title', $perusahaan->brand . ' — Alokasi Asesmen')

@section('content')
<div class="flex items-baseline justify-between mb-2">
    <h1 class="text-xl font-bold">Alokasi Asesmen — {{ $perusahaan->brand }}</h1>
    <a href="{{ route('admin.tenants.perusahaans.show', $perusahaan) }}" class="text-sm text-emerald-700 font-semibold hover:underline">← Detail perusahaan</a>
</div>
<p class="text-sm text-gray-500 mb-6">
    Instrumen aktif dialokasikan langsung ke perusahaan (semua karyawan), independen dari
    program wellbeing. MCMI-III (klinis) hanya per karyawan lewat grant di bawah.
</p>

@if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mb-4">
        @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
@endif

<h2 class="text-lg font-bold mb-3">Instrumen Aktif</h2>
<div class="overflow-x-auto bg-white border rounded-lg mb-8"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Kode</th><th class="p-3">Instrumen</th><th class="p-3">Tipe</th>
        <th class="p-3">Status</th><th class="p-3">Aksi</th>
    </tr></thead>
    <tbody>
        @foreach ($instruments as $instrument)
            @php($allocated = $allocations->get($instrument->code))
            <tr class="border-b">
                <td class="p-3 font-mono text-xs">{{ $instrument->code }}</td>
                <td class="p-3 font-semibold">{{ $instrument->name }}</td>
                <td class="p-3">
                    @if (in_array($instrument->code, $addonCodes, true))
                        <span class="text-xs px-2 py-0.5 rounded bg-amber-100 text-amber-700 font-semibold">Add-on</span>
                    @else
                        <span class="text-xs px-2 py-0.5 rounded bg-blue-100 text-blue-700 font-semibold">Core</span>
                    @endif
                </td>
                <td class="p-3">
                    @if ($allocated)
                        <span class="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 font-semibold">Dialokasikan</span>
                    @else
                        <span class="text-xs text-gray-400">—</span>
                    @endif
                </td>
                <td class="p-3">
                    @if ($allocated)
                        <form method="POST" action="{{ route('admin.tenants.perusahaans.assessments.close', [$perusahaan, $allocated]) }}" class="inline"
                              onsubmit="return confirm('Tutup alokasi {{ $instrument->code }}?')">
                            @csrf
                            <button type="submit" class="text-xs font-semibold rounded px-3 py-1.5 bg-gray-200 text-gray-700">Hapus alokasi</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.tenants.perusahaans.assessments.store', $perusahaan) }}" class="inline">
                            @csrf
                            <input type="hidden" name="instrument_code" value="{{ $instrument->code }}">
                            <button type="submit" class="text-xs font-semibold rounded px-3 py-1.5 bg-emerald-700 text-white">Alokasikan</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table></div>

<h2 class="text-lg font-bold mb-3">Grant Instrumen Klinis (MCMI-III) — per Karyawan</h2>
<p class="text-sm text-gray-500 mb-3">Hanya dengan indikasi klinis. Karyawan bisa mengerjakan instrumen klinis tanpa alokasi massal.</p>

@if ($employees->isEmpty())
    <p class="text-gray-400 text-sm mb-6">Belum ada karyawan terdaftar.</p>
@else
<div class="overflow-x-auto bg-white border rounded-lg mb-8"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Karyawan</th><th class="p-3">Email</th><th class="p-3">Aksi</th>
    </tr></thead>
    <tbody>
        @foreach ($employees as $employee)
            @php($grant = $grants->firstWhere('employee_id', $employee->id))
            <tr class="border-b">
                <td class="p-3 font-semibold">{{ $employee->name }}</td>
                <td class="p-3 text-xs text-gray-500">{{ $employee->email }}</td>
                <td class="p-3">
                    @if ($grant)
                        <form method="POST" action="{{ route('admin.instruments.grants.destroy', $grant) }}" class="inline"
                              onsubmit="return confirm('Cabut grant MCMI-III dari {{ $employee->name }}?')">
                            @csrf @method('DELETE')
                            <input type="hidden" name="from_allocation" value="1">
                            <button type="submit" class="text-xs font-semibold rounded px-3 py-1.5 bg-red-100 text-red-700">Cabut grant</button>
                        </form>
                        <span class="text-xs text-gray-400 ml-2">{{ $grant->reason }}</span>
                    @else
                        <form method="POST" action="{{ route('admin.instruments.grants.store') }}" class="inline flex gap-2 items-center">
                            @csrf
                            <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                            <input type="hidden" name="instrument_code" value="mcmi3">
                            <input type="hidden" name="from_allocation" value="1">
                            <input type="text" name="reason" required maxlength="500" placeholder="Indikasi klinis (wajib)…"
                                   class="border rounded text-xs px-2 py-1.5 w-56">
                            <button type="submit" class="text-xs bg-amber-600 text-white rounded px-3 py-1.5 font-semibold">Grant</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table></div>
@endif
@endsection
