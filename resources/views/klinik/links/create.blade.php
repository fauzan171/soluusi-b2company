@extends('layouts.app')

@section('content')
<h1 class="text-xl font-bold mb-1">Buat Checkout Link</h1>
<div class="mb-4 flex items-center justify-between bg-emerald-50 border border-emerald-200 rounded-lg px-4 py-3">
    <p class="text-sm text-emerald-900">Konsultan terpilih: <strong>{{ trim(($selectedConsultant->user->firstname ?? '') . ' ' . ($selectedConsultant->user->lastname ?? '')) }}</strong></p>
    <div class="flex gap-3 text-xs">
        <a href="{{ route('klinik.schedules.create', ['consultant_id' => $selectedConsultant->id]) }}" class="text-emerald-700 hover:underline">Atur jadwal</a>
        <a href="{{ route('klinik.consultants.show', $selectedConsultant) }}" class="text-emerald-700 hover:underline">Lihat profil</a>
        <a href="{{ route('klinik.consultants.index') }}" class="text-emerald-700 hover:underline">Ganti konsultan</a>
    </div>
</div>

<form method="POST" action="{{ route('klinik.links.store') }}" class="bg-white border rounded-lg p-6 max-w-xl space-y-4">
    @csrf
    <div>
        <label class="block text-sm mb-1">Jadwal konsultan (slot tersedia)</label>
        <select name="schedule_id" required class="w-full border rounded px-3 py-2">
            <option value="">— pilih jadwal —</option>
            @foreach($schedules as $s)
            <option value="{{ $s->id }}" @selected(old('schedule_id') == $s->id || request('schedule_id') == $s->id)>
                [{{ strtoupper($s->mode ?? 'online') }}] {{ $s->started_at->translatedFormat('d M Y H.i') }}
            </option>
            @endforeach
        </select>
        @if($schedules->isEmpty())
            <p class="text-xs text-amber-700 mt-1">Belum ada jadwal untuk konsultan ini. Atur jadwal dulu sebelum membuat link.</p>
        @endif
    </div>
    <div>
        <label class="block text-sm mb-1">Paket</label>
        <select name="consultant_package_id" required class="w-full border rounded px-3 py-2">
            <option value="">— pilih paket —</option>
            @foreach($packages as $p)
            @php
                $markup = (int) (optional($markups->get($p->id))->markup_amount ?? 0);
                $final = (int) $p->price + $markup;
            @endphp
            <option value="{{ $p->id }}" @selected(old('consultant_package_id') == $p->id || request('consultant_package_id') == $p->id)>
                [{{ strtoupper($p->mode ?? 'online') }}] {{ $p->name }} — Rp{{ number_format($final, 0, ',', '.') }} · {{ $p->duration }} mnt
            </option>
            @endforeach
        </select>
        <p class="text-xs text-gray-400 mt-1">Harga sudah termasuk margin klinik. Pilih paket dan jadwal dengan mode yang sama.</p>
    </div>
    <button class="bg-emerald-700 text-white rounded px-4 py-2 font-semibold" @disabled($schedules->isEmpty())>Generate Link</button>
</form>
@endsection
