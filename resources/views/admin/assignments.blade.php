@extends('layouts.app')

@section('content')
<div class="flex items-baseline justify-between mb-2">
    <h1 class="text-xl font-bold">Assignment Konsultan</h1>
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-emerald-700 font-semibold hover:underline">← Dashboard</a>
</div>
<p class="text-sm text-gray-500 mb-6">
    Assign konsultan ke perusahaan (kontrak), atur jam kerja mingguan per assignment,
    dan exception ketersediaan. Hanya super admin — HR hanya melihat.
</p>

{{-- Filter perusahaan --}}
<form method="GET" action="{{ route('admin.assignments.index') }}" class="mb-6 flex gap-3 items-end max-w-md">
    <label class="text-sm flex-1">Perusahaan
        <select name="perusahaan_id" class="w-full border rounded p-2 mt-1" onchange="this.form.submit()">
            @foreach($perusahaans as $p)
            <option value="{{ $p->id }}" @selected($selected?->id === $p->id)>{{ $p->brand }}</option>
            @endforeach
        </select>
    </label>
    <button type="submit" class="bg-emerald-600 text-white font-semibold rounded py-2 px-4 hover:bg-emerald-700">Lihat</button>
</form>

@if($selected)
{{-- Form assign baru --}}
<form method="POST" action="{{ route('admin.assignments.store') }}"
      class="bg-white border rounded-lg p-6 mb-8 grid md:grid-cols-4 gap-4 items-end max-w-4xl">
    @csrf
    <input type="hidden" name="perusahaan_id" value="{{ $selected->id }}">
    <label class="text-sm md:col-span-2">Konsultan
        <select name="consultant_id" required class="w-full border rounded p-2 mt-1">
            <option value="">— Pilih konsultan —</option>
            @foreach($consultants as $c)
            <option value="{{ $c->id }}">{{ $c->user?->firstname }} {{ $c->user?->lastname }} (#{{ $c->id }})</option>
            @endforeach
        </select>
    </label>
    <label class="text-sm">Kontrak mulai
        <input name="contract_start" type="date" class="w-full border rounded p-2 mt-1">
    </label>
    <label class="text-sm">Kontrak selesai
        <input name="contract_end" type="date" class="w-full border rounded p-2 mt-1">
    </label>
    @if($errors->any())
        <p class="md:col-span-4 text-sm text-red-600">{{ $errors->first() }}</p>
    @endif
    <button type="submit" class="md:col-span-4 bg-emerald-600 text-white font-semibold rounded py-2 hover:bg-emerald-700">
        Assign konsultan ke {{ $selected->brand }}
    </button>
</form>

{{-- Daftar assignment --}}
<table class="w-full bg-white border rounded-lg text-sm mb-8">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Konsultan</th>
        <th class="p-3">Kontrak</th>
        <th class="p-3">Status</th>
        <th class="p-3">Aksi</th>
    </tr></thead>
    <tbody>
        @forelse($assignments as $a)
        <tr class="border-b align-top">
            <td class="p-3 font-semibold">{{ $a->consultant?->user?->firstname }} {{ $a->consultant?->user?->lastname }}</td>
            <td class="p-3">
                {{ $a->contract_start?->format('d M Y') ?? '—' }} s/d {{ $a->contract_end?->format('d M Y') ?? '—' }}
                <div class="mt-2">
                    @foreach($a->workHours as $wh)
                    <div class="text-xs text-gray-600 flex items-center gap-2">
                        <span class="bg-pine-100 text-pine-800 rounded px-1.5 py-0.5">{{ ['Min','Sen','Sel','Rab','Kam','Jum','Sab'][$wh->weekday] }}</span>
                        {{ substr($wh->start_time, 0, 5) }}–{{ substr($wh->end_time, 0, 5) }} ({{ $wh->slot_minutes }}m)
                        <form method="POST" action="{{ route('admin.workhours.destroy', $wh) }}"
                              onsubmit="return confirm('Hapus jam kerja ini?')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:underline text-xs">hapus</button>
                        </form>
                    </div>
                    @endforeach
                    <form method="POST" action="{{ route('admin.workhours.store') }}" class="mt-2 flex flex-wrap gap-1 items-center">
                        @csrf
                        <input type="hidden" name="consultant_pool_id" value="{{ $a->id }}">
                        <select name="weekday" class="border rounded px-1 py-0.5 text-xs">
                            @foreach(['Min','Sen','Sel','Rab','Kam','Jum','Sab'] as $i => $d)
                            <option value="{{ $i }}">{{ $d }}</option>
                            @endforeach
                        </select>
                        <input name="start_time" type="time" required class="border rounded px-1 py-0.5 text-xs">
                        <input name="end_time" type="time" required class="border rounded px-1 py-0.5 text-xs">
                        <input name="slot_minutes" type="number" min="15" max="240" value="60" class="border rounded px-1 py-0.5 text-xs w-16">
                        <button class="text-emerald-700 hover:underline text-xs font-semibold">+ jam</button>
                    </form>
                </div>
            </td>
            <td class="p-3">
                @if($a->status === 'active')
                <span class="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 font-semibold">Active</span>
                @else
                <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-500">Ended</span>
                @endif
            </td>
            <td class="p-3">
                @if($a->status === 'active')
                <form method="POST" action="{{ route('admin.assignments.end', $a) }}"
                      onsubmit="return confirm('Akhiri assignment ini?')">
                    @csrf
                    <button class="text-red-600 hover:underline text-xs font-semibold">Akhiri</button>
                </form>
                @else
                <form method="POST" action="{{ route('admin.assignments.activate', $a) }}">
                    @csrf
                    <button class="text-emerald-700 hover:underline text-xs font-semibold">Aktifkan</button>
                </form>
                @endif
            </td>
        </tr>
        @empty
        <tr><td class="p-3 text-gray-400" colspan="4">Belum ada konsultan di-assign.</td></tr>
        @endforelse
    </tbody>
</table>

{{-- Exceptions + flag lock --}}
<div class="grid lg:grid-cols-2 gap-6">
    <div class="bg-white border rounded-lg p-6">
        <h2 class="font-semibold text-gray-700 mb-3">Exception ketersediaan</h2>
        <form method="POST" action="{{ route('admin.exceptions.store') }}" class="flex flex-wrap gap-2 items-end mb-4">
            @csrf
            <label class="text-xs">Konsultan
                <select name="consultant_id" required class="border rounded p-1.5 text-sm w-40">
                    <option value="">—</option>
                    @foreach($consultants as $c)
                    <option value="{{ $c->id }}">{{ $c->user?->firstname }} {{ $c->user?->lastname }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-xs">Tanggal
                <input name="date" type="date" required class="border rounded p-1.5 text-sm">
            </label>
            <label class="text-xs">Mulai (opsional)
                <input name="start_time" type="time" class="border rounded p-1.5 text-sm">
            </label>
            <label class="text-xs">Selesai
                <input name="end_time" type="time" class="border rounded p-1.5 text-sm">
            </label>
            <button class="bg-emerald-600 text-white rounded px-3 py-1.5 text-xs font-semibold hover:bg-emerald-700">Tambah</button>
        </form>
        <p class="text-xs text-gray-400 mb-3">Kosongkan jam = blokir full-day. Global semua perusahaan konsultan.</p>
        <ul class="text-sm divide-y">
            @forelse($exceptions as $e)
            <li class="py-2 flex justify-between items-center">
                <span>{{ $e->consultant?->user?->firstname }} {{ $e->consultant?->user?->lastname }} —
                    {{ $e->date->format('d M Y') }}
                    @if($e->start_time) {{ substr($e->start_time, 0, 5) }}–{{ substr($e->end_time, 0, 5) }} @else (full-day) @endif
                </span>
                <form method="POST" action="{{ route('admin.exceptions.destroy', $e) }}">
                    @csrf @method('DELETE')
                    <button class="text-red-600 hover:underline text-xs">hapus</button>
                </form>
            </li>
            @empty
            <li class="py-2 text-gray-400">Belum ada exception.</li>
            @endforelse
        </ul>
    </div>

    <div class="bg-white border rounded-lg p-6">
        <h2 class="font-semibold text-gray-700 mb-3">Lock permintaan konsultan</h2>
        <p class="text-sm text-gray-500 mb-4">
            Saat terkunci, karyawan tidak bisa "Minta Konsultasi" (auto-assign) — hanya pilih jadwal sendiri.
            Tombol di dashboard karyawan disembunyikan + POST ditolak server-side.
        </p>
        <form method="POST" action="{{ route('admin.perusahaans.request-toggle', $selected) }}" class="flex items-center gap-3">
            @csrf
            <span class="text-sm font-semibold {{ $selected->consultant_request_enabled ? 'text-emerald-700' : 'text-gray-500' }}">
                {{ $selected->consultant_request_enabled ? 'TERBUKA' : 'TERKUNCI' }}
            </span>
            <button class="rounded px-4 py-2 text-sm font-semibold text-white {{ $selected->consultant_request_enabled ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                {{ $selected->consultant_request_enabled ? 'Kunci' : 'Buka' }}
            </button>
        </form>
    </div>
</div>
@endif
@endsection
