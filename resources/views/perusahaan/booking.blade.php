@extends('layouts.app')

@php($title = 'Booking Sesi Konsultasi')

@section('content')
<div class="max-w-3xl">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <h1 class="text-xl font-bold text-pine-900">Booking Sesi Konsultasi</h1>
            <p class="text-sm text-gray-500 mt-1">Sisa kuota bulan ini:
                <span class="font-semibold {{ $quotaLeft > 0 ? 'text-pine-700' : 'text-terra-600' }}">{{ $quotaLeft }} sesi</span>
            </p>
        </div>
        <a href="{{ route('perusahaan.employee') }}" class="text-sm text-pine-700 font-medium hover:underline">&larr; Kembali</a>
    </div>

    @if($quotaLeft <= 0)
    <div class="bg-terra-50 border border-terra-200 rounded-2xl p-4 mb-5 text-sm text-terra-800" role="alert">
        <strong class="font-semibold">Kuota bulan ini habis.</strong>
        <span class="block mt-1">Pilih slot bulan depan, atau hubungi HR kamu untuk tambahan sesi.</span>
    </div>
    @endif

    @if($errors->any())
    <div class="bg-terra-50 border border-terra-200 rounded-2xl p-4 mb-5" role="alert">
        <p class="text-sm font-semibold text-terra-800">Periksa lagi isian kamu:</p>
        <ul class="mt-2 space-y-1 text-sm text-terra-700 list-disc list-inside">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('perusahaan.booking.store') }}" class="portal-card p-5 md:p-6 space-y-5">
        @csrf

        <div>
            <label for="konsultan_type" class="block text-sm font-semibold text-pine-900 mb-1.5">
                Jenis konsultan <span class="text-terra-600" aria-hidden="true">*</span>
            </label>
            <p class="text-xs text-gray-500 mb-2">Pilih sesuai kebutuhan kamu.</p>
            <select id="konsultan_type" name="konsultan_type" required
                aria-invalid="{{ $errors->has('konsultan_type') ? 'true' : 'false' }}"
                class="w-full rounded-lg border px-3 py-2.5 text-sm bg-white transition
                    {{ $errors->has('konsultan_type') ? 'border-terra-400 focus:border-terra-500 focus:ring-terra-500' : 'border-gray-300 focus:border-pine-500 focus:ring-pine-500' }}
                    focus:outline-none focus:ring-2 focus:ring-offset-0">
                @foreach($types as $key => $label)
                <option value="{{ $key }}" @selected(old('konsultan_type') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            @error('konsultan_type')
            <p class="text-xs text-terra-600 mt-1.5 font-medium" role="alert">{{ $message }}</p>
            @enderror
        </div>

@php
    // Grouping di-view: $slots dari BookingService sudah terurut started_at. ponytail: overview kalender
    // navigasi saja; input tetap radio schedule_id per tanggal — tanpa JS/library baru.
    $byDate = $slots->groupBy(fn ($s) => $s->started_at->format('Y-m-d'))->sortKeys();
    $month = now()->startOfMonth();
    $daysInMonth = $month->daysInMonth;
    $dow = fn ($day) => $month->copy()->day($day)->format('N'); // 1=Sen..7=Min
    $firstOffset = $dow(1) - 1;
    $keyFor = fn ($day) => $month->copy()->day($day)->format('Y-m-d');
@endphp

        <div>
            <span class="block text-sm font-semibold text-pine-900 mb-1.5">
                Slot konsultan tersedia (WIB) <span class="text-terra-600" aria-hidden="true">*</span>
            </span>
            <p class="text-xs text-gray-500 mb-2">Semua waktu dalam zona WIB. Sesi berlangsung online. Klik tanggal untuk lompat ke slotnya.</p>

            {{-- Overview kalender bulan ini: jumlah slot per tanggal --}}
            <div class="portal-card p-4 mb-4" aria-label="Ikhtisar ketersediaan {{ $month->translatedFormat('F Y') }}">
                <p class="text-xs font-semibold text-pine-900 mb-2">{{ $month->translatedFormat('F Y') }}</p>
                <div class="grid grid-cols-7 gap-1 text-center text-[10px] text-gray-400 mb-1">
                    <span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span>
                </div>
                <div class="grid grid-cols-7 gap-1">
                    @for($i = 0; $i < $firstOffset; $i++)<span></span>@endfor
                    @for($day = 1; $day <= $daysInMonth; $day++)
                    @php $key = $keyFor($day); $count = $byDate->get($key)?->count() ?? 0; @endphp
                    @if($count > 0)
                    <a href="#slot-{{ $key }}" class="rounded-lg bg-pine-50 border border-pine-200 px-1 py-1.5 text-xs font-semibold text-pine-800 hover:bg-pine-100 transition">
                        {{ $day }}<span class="block text-[10px] font-normal">{{ $count }} slot</span>
                    </a>
                    @else
                    <span class="rounded-lg bg-gray-50 border border-gray-100 px-1 py-1.5 text-xs text-gray-300" aria-disabled="true">{{ $day }}</span>
                    @endif
                    @endfor
                </div>
            </div>

            @forelse($byDate as $date => $daySlots)
            <fieldset id="slot-{{ $date }}" class="border border-[#e7e2d9] rounded-xl p-3.5 mb-2.5 scroll-mt-4">
                <legend class="px-1.5 text-xs font-semibold text-pine-900">{{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }} · {{ $daySlots->count() }} slot</legend>
                <div class="mt-1 space-y-1.5">
                    @foreach($daySlots as $slot)
                    <label class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 hover:bg-pine-50/60 cursor-pointer transition">
                        <input type="radio" name="schedule_id" value="{{ $slot->id }}" required @checked(old('schedule_id') == $slot->id)
                            class="h-4 w-4 text-pine-700 border-gray-300 focus:ring-pine-500">
                        <span class="text-sm text-ink">{{ $slot->started_at->translatedFormat('H.i') }}–{{ $slot->ended_at->translatedFormat('H.i') }} · {{ $slot->consultant->user->firstname }} · {{ $slot->mode }}</span>
                    </label>
                    @endforeach
                </div>
            </fieldset>
            @empty
            <p class="text-xs text-gray-500 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">Belum ada slot tersedia. Hubungi pengelola program kamu.</p>
            @endforelse
            @error('schedule_id')
            <p class="text-xs text-terra-600 mt-1.5 font-medium" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="bg-pine-50 border border-pine-200 rounded-xl p-3.5 text-xs text-pine-800 leading-relaxed">
            <strong class="font-semibold">Rahasia penuh.</strong> Sesi berlangsung online. HR hanya melihat jumlah agregat — tidak pernah isi atau identitas sesi kamu.
        </div>

        <div class="pt-1">
            <button type="submit"
                class="w-full rounded-lg bg-pine-700 text-white text-sm font-semibold px-4 py-3 transition hover:bg-pine-800 focus:outline-none focus:ring-2 focus:ring-pine-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                {{ $quotaLeft <= 0 ? 'disabled' : '' }}>
                Jadwalkan Sesi
            </button>
        </div>
    </form>
</div>
@endsection
