@extends('layouts.app')

@section('content')
<div class="max-w-3xl">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-xl font-bold">Booking Sesi Konsultasi</h1>
            <p class="text-sm text-gray-500">Sisa kuota bulan ini: <span class="font-semibold {{ $quotaLeft > 0 ? 'text-pine-700' : 'text-red-600' }}">{{ $quotaLeft }} sesi</span></p>
        </div>
        <a href="{{ route('perusahaan.employee') }}" class="text-sm text-pine-700 hover:underline">← Kembali</a>
    </div>

    @if($quotaLeft <= 0)
    <div class="bg-terra-50 border border-terra-200 rounded-lg p-4 text-sm text-terra-800">
        Kuota bulan ini habis. Pilih slot bulan depan, atau hubungi HR kamu.
    </div>
    @endif
    <form method="POST" action="{{ route('perusahaan.booking.store') }}" class="space-y-4">
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
                <option value="{{ $slot->id }}" @selected(old('schedule_id') == $slot->id)>{{ $slot->started_at->translatedFormat('D, d M Y H:i') }}–{{ $slot->ended_at->format('H:i') }} · {{ $slot->consultant->user->firstname }} · {{ $slot->mode }}</option>
                @endforeach
            </select>
            @if($slots->isEmpty())<p class="text-sm text-gray-500 mt-2">Belum ada slot tersedia. Hubungi pengelola program.</p>@endif
            @error('schedule_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-800">
            Sesi berlangsung online, rahasia penuh. HR hanya melihat jumlah agregat — tidak pernah isi atau identitas sesi kamu.
        </div>

        <button type="submit" class="w-full bg-pine-700 text-white text-sm font-semibold rounded px-4 py-2.5 hover:bg-pine-800">Jadwalkan Sesi</button>
    </form>
</div>
@endsection
