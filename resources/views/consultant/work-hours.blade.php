@extends('layouts.app')

@php $title = 'Jam Kerja Mingguan'; @endphp

@section('content')
<div class="max-w-4xl">
    <section class="overflow-hidden rounded-[1.35rem] bg-gradient-to-br from-pine-950 via-pine-900 to-pine-700 px-5 py-6 text-white shadow-[0_22px_60px_rgba(6,42,32,.18)] sm:px-7 mb-5">
        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-pine-200">Ketersediaan</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Jam Kerja Mingguan</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-pine-50/80">Jam ini jadi sumber slot booking karyawan perusahaan — slot yang sudah dibooking otomatis hilang.</p>
    </section>

    @if(session('success'))
    <div class="bg-pine-50 border border-pine-200 rounded-2xl p-4 mb-5 text-sm text-pine-800" role="status">{{ session('success') }}</div>
    @endif

    @if($errors->any())
    <div class="bg-terra-50 border border-terra-200 rounded-2xl p-4 mb-5" role="alert">
        <ul class="mt-2 space-y-1 text-sm text-terra-700 list-disc list-inside">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @forelse($pools as $pool)
    @php
        // Prefill: jam tersimpan, atau old() bila submit pool ini gagal validasi.
        $existing = $pool->relationLoaded('workHours') ? $pool->workHours : $pool->workHours()->get();
        $prefill = old('pool_id') == $pool->id
            ? collect(old('hours', []))
            : $existing->map(fn ($h) => ['weekday' => (string) $h->weekday, 'start_time' => substr($h->start_time, 0, 5), 'end_time' => substr($h->end_time, 0, 5)]);
    @endphp
    <form method="POST" action="{{ route('consultant.work-hours.store') }}" class="portal-card mb-5" x-data="{ rows: @json($prefill->values()) }">
        @csrf
        <input type="hidden" name="pool_id" value="{{ $pool->id }}">
        <div class="flex items-center justify-between border-b border-[#e7e2d9] pb-3 mb-4">
            <div>
                <h2 class="text-lg font-semibold text-pine-900">{{ $pool->perusahaan?->name ?? 'Perusahaan #'.$pool->perusahaan_id }}</h2>
                <p class="text-xs text-gray-500">Simpan = ganti seluruh set jam untuk perusahaan ini.</p>
            </div>
            <button type="button" x-on:click="rows.push({ weekday: '1', start_time: '09:00', end_time: '17:00' })"
                class="rounded-xl bg-pine-700 px-4 py-2 text-sm font-semibold text-white hover:bg-pine-800">+ Tambah jam</button>
        </div>

        <div class="space-y-2">
            <template x-for="(row, i) in rows" :key="i">
                <div class="flex items-center gap-2">
                    <input type="hidden" name="hours[i][weekday]" x-bind:value="row.weekday" />
                    <input type="hidden" name="hours[i][start_time]" x-bind:value="row.start_time" />
                    <input type="hidden" name="hours[i][end_time]" x-bind:value="row.end_time" />
                    <select x-model="row.weekday" class="rounded-lg border border-[#ddd8cf] px-3 py-2 text-sm">
                        @foreach($days as $idx => $day)
                        <option value="{{ $idx }}">{{ $day }}</option>
                        @endforeach
                    </select>
                    <input type="time" x-model="row.start_time" class="rounded-lg border border-[#ddd8cf] px-3 py-2 text-sm" />
                    <span class="text-gray-400">–</span>
                    <input type="time" x-model="row.end_time" class="rounded-lg border border-[#ddd8cf] px-3 py-2 text-sm" />
                    <button type="button" x-on:click="rows.splice(i, 1)" class="ml-auto text-sm text-terra-600 hover:underline">Hapus</button>
                </div>
            </template>
            <p x-show="rows.length === 0" class="text-sm text-gray-400 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                Belum ada jam — karyawan perusahaan ini tidak melihat slot.
            </p>
        </div>

        <button type="submit" class="mt-4 w-full rounded-lg bg-pine-700 text-white text-sm font-semibold px-4 py-3 hover:bg-pine-800">
            Simpan Jam Kerja
        </button>
    </form>
    @empty
    <div class="portal-card p-8 text-center text-sm text-gray-400">Belum ada perusahaan yang meng-assign kamu.</div>
    @endforelse
</div>
@endsection
