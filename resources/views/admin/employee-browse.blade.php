@extends('layouts.app')

@php
    $title = 'Karyawan';
@endphp

@section('content')
<div class="max-w-6xl">
    <div class="mb-4">
        <h1 class="text-xl font-bold">Karyawan</h1>
        <p class="text-sm text-gray-500">Browser karyawan lintas perusahaan — metadata roster saja, skor asesmen individual tidak ditampilkan.</p>
    </div>

    <form method="GET" action="{{ route('admin.employees.index') }}" class="dash-card p-4 mb-4 flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[180px]">
            <label for="q" class="block text-xs font-medium text-gray-500 mb-1">Cari nama / email</label>
            <input id="q" name="q" type="text" value="{{ $q }}" class="w-full rounded-xl border border-[#e7e2d9] px-3 py-2 text-sm" placeholder="mis. Budi / budi@contoh.co.id">
        </div>
        <div>
            <label for="perusahaan_id" class="block text-xs font-medium text-gray-500 mb-1">Perusahaan</label>
            <select id="perusahaan_id" name="perusahaan_id" class="rounded-xl border border-[#e7e2d9] px-3 py-2 text-sm bg-white">
                <option value="">Semua perusahaan</option>
                @foreach($companies as $c)
                    <option value="{{ $c->id }}" @selected($c->id === $companyId)>{{ $c->brand }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded-xl bg-pine-700 px-4 py-2 text-sm font-semibold text-white hover:bg-pine-800">Cari</button>
    </form>

    <div class="dash-card p-5">
        @if($employees->isEmpty())
            <p class="text-sm text-gray-400">Tidak ada karyawan yang cocok.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-400 border-b border-gray-100">
                            <th class="py-2 pr-4 font-medium">Nama</th>
                            <th class="py-2 pr-4 font-medium">Perusahaan</th>
                            <th class="py-2 pr-4 font-medium">Consent</th>
                            <th class="py-2 font-medium">Program aktif</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $e)
                        @php($program = $activePrograms[$e->perusahaan_id] ?? null)
                        <tr class="border-b border-gray-50 last:border-0">
                            <td class="py-2.5 pr-4 text-gray-700">{{ $e->name }}</td>
                            <td class="py-2.5 pr-4 text-gray-500">{{ $e->perusahaan?->brand ?? '—' }}</td>
                            <td class="py-2.5 pr-4">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $e->consent_at ? 'bg-pine-100 text-pine-800' : 'bg-gray-100 text-gray-500' }}">{{ $e->consent_at ? 'Consent' : 'Belum' }}</span>
                            </td>
                            <td class="py-2.5 text-gray-500">{{ $program ? ($program->wellbeingPackage?->name ?? 'Program aktif') : '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $employees->links() }}
        @endif
    </div>
</div>
@endsection
