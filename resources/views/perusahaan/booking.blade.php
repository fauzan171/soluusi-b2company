@extends('layouts.app')

@php $title = 'Booking Sesi Konsultasi'; @endphp

@section('content')
<div class="max-w-6xl">
    {{-- Hero --}}
    <section class="overflow-hidden rounded-[1.35rem] bg-gradient-to-br from-pine-950 via-pine-900 to-pine-700 px-5 py-6 text-white shadow-[0_22px_60px_rgba(6,42,32,.18)] sm:px-7 mb-5">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-pine-200">Booking Konsultasi</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Pilih Konsultan &amp; Jadwal</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-pine-50/80">Pilih konsultan dari pool perusahaan kamu, lalu pilih slot yang tersedia. Sesi berlangsung online atau tatap muka, dan rahasia penuh.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-sm text-pine-50">
                    Sisa kuota bulan ini: <span class="font-semibold">{{ $quotaLeft }} sesi</span>
                </div>
                <a href="{{ route('perusahaan.employee') }}" class="text-sm font-medium text-pine-100 hover:text-white hover:underline">&larr; Kembali</a>
            </div>
        </div>
    </section>

    @if($quotaLeft <= 0)
    <div class="bg-terra-50 border border-terra-200 rounded-2xl p-4 mb-5 text-sm text-terra-800" role="alert">
        <strong class="font-semibold">Kuota bulan ini habis.</strong>
        <span class="block mt-1">Slot bulan depan dibuka setelah bulan ini berakhir — hubungi HR kamu untuk tambahan sesi.</span>
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

    <div class="grid gap-4 lg:grid-cols-[minmax(0,1.04fr),minmax(360px,.96fr)]">
        {{-- Consultant grid + filter --}}
        <div class="space-y-4">
            <form method="GET" action="{{ route('perusahaan.booking') }}" class="rounded-2xl border border-[#e7e2d9] bg-white p-4 shadow-sm">
                <div class="grid gap-3 md:grid-cols-[minmax(0,1fr),200px,auto] md:items-end">
                    <div>
                        <label for="q" class="mb-1.5 block text-xs font-semibold text-gray-500">Cari konsultan</label>
                        <input id="q" name="q" value="{{ $q }}" placeholder="Nama konsultan" class="w-full rounded-xl border border-[#ddd8cf] px-3 py-2.5 text-sm outline-none focus:border-pine-600 focus:ring-4 focus:ring-pine-100">
                    </div>
                    <div>
                        <label for="category" class="mb-1.5 block text-xs font-semibold text-gray-500">Kategori</label>
                        <select id="category" name="category" class="w-full rounded-xl border border-[#ddd8cf] px-3 py-2.5 text-sm outline-none focus:border-pine-600 focus:ring-4 focus:ring-pine-100">
                            <option value="">Semua</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" @selected($activeCategory === $cat)>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="rounded-xl bg-pine-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-pine-800">Filter</button>
                        <a href="{{ route('perusahaan.booking') }}" class="rounded-xl border border-[#ddd8cf] px-4 py-2.5 text-sm font-semibold text-gray-500 hover:bg-gray-50">Reset</a>
                    </div>                </div>
            </form>

            <div class="grid gap-3 md:grid-cols-2">
                @forelse($consultants as $c)
                    @php
                        $selected = $selectedConsultant && $selectedConsultant['id'] === $c['id'];
                        $url = route('perusahaan.booking', array_filter([
                            'consultant_id' => $c['id'],
                            'q' => $q,
                            'category' => $activeCategory,
                        ]));
                    @endphp
                    <a href="{{ $url }}" class="rounded-2xl border bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-pine-300 hover:shadow-md {{ $selected ? 'border-pine-500 ring-4 ring-pine-100' : 'border-[#e7e2d9]' }}">
                        <div class="flex gap-3">
                            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-pine-50 text-lg font-bold text-pine-700">{{ strtoupper(substr($c['name'], 0, 1)) }}</div>
                            <div class="min-w-0">
                                <h2 class="truncate text-sm font-semibold text-gray-900">{{ $c['name'] }}</h2>
                                <p class="mt-0.5 text-xs text-gray-500">{{ $c['category'] ?: 'Umum' }}</p>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-between border-t border-dashed border-[#e7e2d9] pt-3 text-xs">
                            <span class="font-semibold text-pine-700">{{ $types[$c['type']] ?? ucfirst($c['type']) }}</span>
                            <span class="text-gray-400">{{ $selected ? 'Terpilih' : 'Pilih' }}</span>
                        </div>
                    </a>
                @empty
                    <div class="rounded-2xl border border-[#e7e2d9] bg-white p-8 text-center text-sm text-gray-400 md:col-span-2">Tidak ada konsultan yang cocok dengan filter.</div>
                @endforelse
            </div>
        </div>

        {{-- Slot picker aside --}}
        <aside class="rounded-2xl border border-[#e7e2d9] bg-white p-5 shadow-sm lg:sticky lg:top-5 lg:self-start">
            @if($selectedConsultant)
                <div class="border-b border-[#e7e2d9] pb-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-pine-700">Konsultan terpilih</p>
                    <h2 class="mt-1 text-lg font-semibold text-gray-900">{{ $selectedConsultant['name'] }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ $selectedConsultant['category'] ?: 'Umum' }}</p>
                </div>
            @else
                <div class="border-b border-[#e7e2d9] pb-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-pine-700">Semua slot tersedia</p>
                    <p class="mt-2 text-sm leading-6 text-gray-500">Pilih konsultan di sebelah kiri untuk memfilter slot, atau langsung pilih dari semua slot di bawah.</p>
                </div>
            @endif

            <form method="POST" action="{{ route('perusahaan.booking.store') }}" class="mt-4 space-y-4">
                @csrf
                @if($selectedConsultant)
                    <input type="hidden" name="konsultan_type" value="{{ $selectedConsultant['type'] }}">
                    <input type="hidden" name="consultant_id" value="{{ $selectedConsultant['id'] }}">
                @else
                    <div>
                        <label for="konsultan_type" class="block text-sm font-semibold text-pine-900 mb-1.5">
                            Jenis konsultan <span class="text-terra-600" aria-hidden="true">*</span>
                        </label>
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
                @endif

                {{-- W5 (K9): tipe konsultasi — radio 3 tipe. --}}
                <fieldset>
                    <legend class="block text-sm font-semibold text-pine-900 mb-1.5">
                        Tipe konsultasi <span class="text-terra-600" aria-hidden="true">*</span>
                    </legend>
                    <p class="text-xs text-gray-500 mb-2">Pilih cara kamu ingin berkonsultasi.</p>
                    <div class="grid gap-2">
                        @foreach([
                            'online_meeting' => ['Video Call', 'Sesi online via Google Meet/Zoom'],
                            'chat' => ['Chat', 'Konsultasi teks asinkron'],
                            'one_on_one' => ['Tatap Muka', 'Sesi offline di ruang perusahaan'],
                        ] as $key => [$label, $hint])
                        <label class="flex items-start gap-2.5 rounded-xl border px-3 py-2.5 cursor-pointer transition {{ old('consultation_type', $prefillConsultationType ?? 'chat') === $key ? 'border-pine-500 bg-pine-50' : 'border-gray-300 bg-white hover:bg-pine-50/60' }}">
                            <input type="radio" name="consultation_type" value="{{ $key }}" required @checked(old('consultation_type', $prefillConsultationType ?? 'chat') === $key)
                                class="mt-0.5 h-4 w-4 text-pine-700 border-gray-300 focus:ring-pine-500">
                            <span>
                                <span class="block text-sm font-semibold text-ink">{{ $label }}</span>
                                <span class="block text-xs text-gray-500">{{ $hint }}</span>
                            </span>
                        </label>
                        @endforeach
                    </div>
                    @error('consultation_type')
                    <p class="text-xs text-terra-600 mt-1.5 font-medium" role="alert">{{ $message }}</p>
                    @enderror
                </fieldset>

                @php
                    $byDate = $slots->groupBy(fn ($s) => $s->started_at->format('Y-m-d'))->sortKeys();
                @endphp

                <div>
                    <span class="block text-sm font-semibold text-pine-900 mb-1.5">
                        Slot tersedia (WIB) <span class="text-terra-600" aria-hidden="true">*</span>
                    </span>

                    <div class="max-h-[26rem] overflow-y-auto pr-1 space-y-2.5">
                        @forelse($byDate as $date => $daySlots)
                        <fieldset id="slot-{{ $date }}" class="border border-[#e7e2d9] rounded-xl p-3.5 scroll-mt-4">
                            <legend class="px-1.5 text-xs font-semibold text-pine-900">{{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }} · {{ $daySlots->count() }} slot</legend>
                            <div class="mt-1 space-y-1.5">
                                @foreach($daySlots as $slot)
                                @php
                                    // SLOTS: slot dari jam kerja (id NULL) → starts_at + consultant_id;
                                    // slot Schedule legacy → schedule_id.
                                    $isHours = $slot->id === null;
                                    $value = $isHours ? $slot->started_at->format('Y-m-d H:i:s') : (string) $slot->id;
                                    $name = $isHours ? 'starts_at' : 'schedule_id';
                                    $checkedKey = $isHours ? 'starts_at' : 'schedule_id';
                                @endphp
                                <label class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 hover:bg-pine-50/60 cursor-pointer transition">
                                    <input type="radio" name="{{ $name }}" value="{{ $value }}" required @checked(old($checkedKey) == $value)
                                        x-on:change="{{ $isHours ? "\$el.closest('form').querySelector('input[name=\\'consultant_id\\']')?.setAttribute('value', '{$slot->consultant_id}')" : '' }}"
                                        class="h-4 w-4 text-pine-700 border-gray-300 focus:ring-pine-500">
                                    <span class="text-sm text-ink">{{ $slot->started_at->translatedFormat('H.i') }}–{{ $slot->ended_at->translatedFormat('H.i') }}@unless($selectedConsultant) · {{ $slot->consultant->user->firstname ?? '' }}@endunless · {{ $slot->mode === 'offline' ? 'Tatap muka' : 'Online' }}</span>
                                </label>
                                @endforeach
                            </div>
                        </fieldset>
                        @empty
                        <p class="text-xs text-gray-500 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">Belum ada slot tersedia. Hubungi pengelola program kamu.</p>
                        @endforelse
                    </div>
                    @error('schedule_id')
                    <p class="text-xs text-terra-600 mt-1.5 font-medium" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="bg-pine-50 border border-pine-200 rounded-xl p-3.5 text-xs text-pine-800 leading-relaxed">
                    <strong class="font-semibold">Rahasia penuh.</strong> Sesi berlangsung sesuai tipe yang kamu pilih — online atau tatap muka. HR hanya melihat jumlah agregat — tidak pernah isi atau identitas sesi kamu.
                </div>

                <button type="submit"
                    class="w-full rounded-lg bg-pine-700 text-white text-sm font-semibold px-4 py-3 transition hover:bg-pine-800 focus:outline-none focus:ring-2 focus:ring-pine-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    {{ $quotaLeft <= 0 ? 'disabled' : '' }}>
                    Jadwalkan Sesi
                </button>
            </form>
        </aside>
    </div>
</div>
@endsection
