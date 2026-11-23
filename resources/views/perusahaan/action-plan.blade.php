@extends('layouts.app')

@php
    $title = 'Action Plan';
    $statusLabels = ['planned' => 'Direncanakan', 'in_progress' => 'Berjalan', 'completed' => 'Selesai'];
    $statusStyles = ['planned' => 'bg-gray-100 text-gray-700', 'in_progress' => 'bg-amber-100 text-amber-800', 'completed' => 'bg-pine-100 text-pine-800'];
@endphp

@section('content')
<div class="max-w-3xl">
    <div class="mb-4">
        <h1 class="text-xl font-bold">Action Plan</h1>
        <p class="text-sm text-gray-500">Tindak lanjut per area prioritas hasil asesmen — tersedia untuk paket Growth &amp; Impact.</p>
    </div>

    @if(!$allowed)
        <div class="dash-card p-6 text-center">
            <p class="text-sm text-gray-500">
                @if($program)
                    Action Plan tersedia untuk paket <strong>Growth</strong> dan <strong>Impact</strong>. Paket aktif Anda: <strong>{{ $program->wellbeingPackage?->name ?? '-' }}</strong>.
                @else
                    Belum ada program Wellbeing aktif. Hubungi Soluusi untuk aktivasi.
                @endif
            </p>
        </div>
    @else
        <div class="dash-card p-5 mb-4">
            <h2 class="text-[0.95rem] font-semibold text-gray-900 mb-3">Tambah action plan</h2>
            <form method="POST" action="{{ route('perusahaan.action-plan.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label for="area" class="block text-xs font-medium text-gray-500 mb-1">Area prioritas (departemen / domain)</label>
                    <input id="area" name="area" type="text" required maxlength="100"
                           class="w-full rounded-xl border border-[#e7e2d9] px-3 py-2 text-sm" placeholder="mis. Keuangan / mental health">
                    @error('area')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="note" class="block text-xs font-medium text-gray-500 mb-1">Rencana tindakan</label>
                    <textarea id="note" name="note" rows="3" maxlength="2000"
                              class="w-full rounded-xl border border-[#e7e2d9] px-3 py-2 text-sm" placeholder="mis. Webinar stres kerja + ikhtiar 1-on-1 untuk area ini"></textarea>
                    @error('note')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="status" class="block text-xs font-medium text-gray-500 mb-1">Status awal</label>
                    <select id="status" name="status" class="w-full rounded-xl border border-[#e7e2d9] px-3 py-2 text-sm">
                        @foreach($statuses as $s)
                            <option value="{{ $s }}" @selected($s === 'planned')>{{ $statusLabels[$s] }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-xl bg-pine-700 px-4 py-2 text-sm font-semibold text-white hover:bg-pine-800">Simpan</button>
            </form>
        </div>

        <div class="dash-card p-5">
            <h2 class="text-[0.95rem] font-semibold text-gray-900 mb-3">Daftar action plan ({{ $plans->count() }})</h2>
            @if($plans->isEmpty())
                <p class="text-sm text-gray-400">Belum ada action plan.</p>
            @else
                <div class="space-y-2">
                    @foreach($plans as $plan)
                    <div class="panel-surface bg-white p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-medium text-gray-700">{{ $plan->area }}</p>
                                @if($plan->note)<p class="mt-0.5 text-xs text-gray-500">{{ $plan->note }}</p>@endif
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusStyles[$plan->status] ?? 'bg-gray-100' }}">{{ $statusLabels[$plan->status] ?? $plan->status }}</span>
                                <form method="POST" action="{{ route('perusahaan.action-plan.status', $plan) }}" class="flex items-center gap-1">
                                    @csrf
                                    <select name="status" onchange="this.form.submit()" class="text-xs border border-[#e7e2d9] rounded-lg px-2 py-1 bg-white" aria-label="Ubah status">
                                        @foreach($statuses as $s)
                                            <option value="{{ $s }}" @selected($s === $plan->status)>{{ $statusLabels[$s] }}</option>
                                        @endforeach
                                    </select>
                                </form>
                                <form method="POST" action="{{ route('perusahaan.action-plan.destroy', $plan) }}" onsubmit="return confirm('Hapus action plan ini?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-red-600 hover:underline">Hapus</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
