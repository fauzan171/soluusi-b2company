@extends('layouts.app')

@section('title', 'Booking Konseling — ' . $sekolah->brand)

@section('content')
<div class="max-w-3xl">
    <h1 class="text-xl font-bold text-pine-900">Booking Konseling</h1>
    <p class="text-sm text-gray-500 mt-1 mb-6">Pilih jenis konsultan dan slot; mode mengikuti slot. Sisa kuota kamu: <b>{{ $quotaLeft }}</b> sesi bulan ini.</p>

    @if(session('error'))<div class="mb-4 p-3 rounded-xl bg-red-100 text-red-800 text-sm">{{ session('error') }}</div>@endif

    <form method="POST" action="{{ $action ?? route('sekolah.booking.store') }}" class="portal-card p-5 space-y-5" x-data="{ type: @old('konsultan_type', 'psikolog') }">
        @csrf
        <div>
            <label for="konsultan_type" class="block text-sm font-medium mb-2">Jenis konsultan</label>
            <select id="konsultan_type" name="konsultan_type" required class="w-full border rounded px-3 py-2">
                @foreach($types as $key => $label)
                <option value="{{ $key }}" @selected(old('konsultan_type') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            @error('konsultan_type')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="schedule_id" class="block text-sm font-medium mb-2">Slot konsultan tersedia (WIB)</label>
            <select id="schedule_id" name="schedule_id" required class="w-full border rounded px-3 py-2">
                <option value="">— Pilih slot sesuai jenis konsultan —</option>
                @foreach($slots as $slot)
                <option value="{{ $slot->id }}" @selected(old('schedule_id') == $slot->id)>{{ $slot->started_at->translatedFormat('D, d M Y H.i') }}–{{ $slot->ended_at->translatedFormat('H.i') }} · {{ $slot->consultant->user->firstname }} · {{ $slot->mode === 'offline' ? 'Tatap muka' : 'Online' }}</option>
                @endforeach
            </select>
            @if($slots->isEmpty())<p class="text-sm text-gray-500 mt-2">Belum ada slot tersedia. Hubungi pengelola program.</p>@endif
            @error('schedule_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <button class="w-full bg-pine-700 text-white text-sm font-semibold py-3 rounded-xl">
            Jadwalkan Sesi
        </button>
    </form>
</div>
@endsection
