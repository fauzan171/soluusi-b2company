@extends('layouts.app')

@section('title', 'Assign Program — Admin')

@section('content')
<div class="flex items-baseline justify-between mb-2">
    <h1 class="text-xl font-bold">Assign Program ({{ $step }}/3)</h1>
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-emerald-700 font-semibold hover:underline">← Dashboard</a>
</div>
<p class="text-sm text-gray-500 mb-6">
    Aktifkan paket untuk perusahaan: pilih paket, band, opsi Deep Support, dan tanggal mulai.
</p>

<form method="POST" action="{{ route('admin.wellbeing-programs.store') }}"
      class="bg-white border rounded-lg p-6 mb-8 max-w-3xl"
      x-data="{
          step: {{ $step }},
          packageCode: '{{ old('package_code', $package?->code ?? 'discovery') }}',
          bandMin: '{{ old('band_min', $selectedBand?->band_min ?? $suggestedBandMin ?? '') }}',
          mode: '{{ old('deep_support_mode', $package?->deep_support_mode ?? 'mhcu') }}'
      }">
    @csrf

    {{-- STEP 1 — Perusahaan & Paket --}}
    <div x-show="step === 1" x-cloak>
        <h2 class="font-semibold text-gray-700 mb-3">Perusahaan &amp; Paket</h2>
        <label class="text-sm block mb-4">Perusahaan
            <select name="perusahaan_id" required class="w-full border rounded p-2 mt-1 bg-white">
                <option value="">— pilih perusahaan aktif —</option>
                @foreach($perusahaans as $p)
                    <option value="{{ $p->id }}" @selected(old('perusahaan_id') == $p->id)>
                        {{ $p->brand }} — {{ $p->name }} ({{ $p->employee_count }} karyawan)
                    </option>
                @endforeach
            </select>
        </label>
        <fieldset class="mb-4">
            <legend class="text-sm font-semibold mb-1">Paket</legend>
            <div class="flex flex-wrap gap-3 text-sm">
                @foreach($packages as $pkg)
                    <label class="flex items-center gap-1.5 border rounded px-3 py-1.5 cursor-pointer"
                           :class="packageCode === '{{ $pkg->code }}' ? 'border-emerald-600 bg-emerald-50' : ''">
                        <input type="radio" name="package_code" value="{{ $pkg->code }}" x-model="packageCode" @checked(old('package_code') === $pkg->code)>
                        {{ $pkg->name }}{{ $pkg->is_free ? ' (Gratis)' : '' }}
                    </label>
                @endforeach
            </div>
        </fieldset>
    </div>

    {{-- STEP 2 — Detail Paket --}}
    <div x-show="step === 2" x-cloak>
        <h2 class="font-semibold text-gray-700 mb-3">
            Detail Paket — {{ $perusahaan?->brand }} / {{ $package?->name }}
        </h2>

        @if($package && $package->is_free)
            <p class="text-sm mb-4">Paket gratis — tanpa band harga. Durasi {{ $package->duration_days }} hari.</p>
            <input type="hidden" name="band_min" value="">
        @else
            <fieldset class="mb-4">
                <legend class="text-sm font-semibold mb-1">
                    Band Karyawan
                    @if($suggestedBandMin)
                        <span class="font-normal text-gray-500">(auto-highlight dari jumlah karyawan terdaftar: {{ $perusahaan?->employee_count }})</span>
                    @else
                        <span class="font-normal text-red-600">(jumlah karyawan {{ $perusahaan?->employee_count }} di luar semua band — hubungi Finance untuk harga manual)</span>
                    @endif
                </legend>
                <div class="grid gap-2 text-sm">
                    @foreach($bands as $b)
                        <label class="flex items-center justify-between border rounded px-3 py-1.5 cursor-pointer"
                               :class="bandMin == '{{ $b->band_min }}' ? 'border-emerald-600 bg-emerald-50' : ''">
                            <span class="flex items-center gap-2">
                                <input type="radio" name="band_min" value="{{ $b->band_min }}" x-model.number="bandMin" @checked(old('band_min') == $b->band_min || $selectedBand?->id === $b->id)>
                                {{ number_format($b->band_min, 0, ',', '.') }}–{{ number_format($b->band_max, 0, ',', '.') }} karyawan
                                @if($suggestedBandMin == $b->band_min)
                                    <span class="text-xs px-2 py-0.5 rounded bg-pine-100 text-pine-800 font-semibold">Disarankan</span>
                                @endif
                            </span>
                            <span class="font-semibold">Rp{{ number_format($b->monthly_price, 0, ',', '.') }} / bulan</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
            <label class="text-sm block mb-4">Harga terkunci
                <input type="text" readonly value="{{ $selectedBand ? 'Rp' . number_format($selectedBand->monthly_price, 0, ',', '.') . ' / bulan + pajak' : '—' }}"
                       class="w-full border rounded p-2 mt-1 bg-gray-50 text-gray-600">
                <span class="text-xs text-gray-400">Angka terkunci saat program diaktifkan.</span>
            </label>
        @endif

        {{-- Opsi Deep Support (hanya Growth/Impact) --}}
        @if($package && in_array($package->code, ['growth', 'impact']))
            <fieldset class="mb-4">
                <legend class="text-sm font-semibold mb-1">Opsi Deep Support</legend>
                <div class="grid gap-2 text-sm">
                    <label class="flex items-center gap-2 border rounded px-3 py-1.5 cursor-pointer" :class="mode === 'mhcu' ? 'border-emerald-600 bg-emerald-50' : ''">
                        <input type="radio" name="deep_support_mode" value="mhcu" x-model="mode" @checked(old('deep_support_mode') === 'mhcu')>
                        Option A: {{ $selectedBand?->mhcu_quota ?? $package->deep_support_quota }} sesi MHCU
                    </label>
                    <label class="flex items-center gap-2 border rounded px-3 py-1.5 cursor-pointer" :class="mode === 'one_on_one' ? 'border-emerald-600 bg-emerald-50' : ''">
                        <input type="radio" name="deep_support_mode" value="one_on_one" x-model="mode" @checked(old('deep_support_mode') === 'one_on_one')>
                        Option B: {{ $package->deep_support_quota }} sesi 1-on-1 (60 menit)
                    </label>
                </div>
            </fieldset>
        @else
            <input type="hidden" name="deep_support_mode" value="{{ $package?->deep_support_mode ?? 'one_on_one' }}">
        @endif

        @if($package && $package->code === 'discovery')
            <label class="text-sm block mb-4">Karyawan terpilih Deep Support (maks 8, kosong = semua)
                <input type="text" name="selected_employee_ids" placeholder="ID karyawan, pisahkan koma (mis. 12,15,19)"
                       value="{{ old('selected_employee_ids') ? implode(',', old('selected_employee_ids')) : '' }}"
                       class="w-full border rounded p-2 mt-1">
            </label>
        @endif

        <div class="grid sm:grid-cols-2 gap-4 mb-4">
            <label class="text-sm">Tanggal Mulai
                <input type="date" name="starts_at" required value="{{ old('starts_at', now()->addDay()->toDateString()) }}" class="w-full border rounded p-2 mt-1">
            </label>
            <label class="text-sm">Durasi Program
                <input type="text" readonly value="{{ $package ? $package->duration_days . ' hari' : '—' }}"
                       class="w-full border rounded p-2 mt-1 bg-gray-50 text-gray-600">
            </label>
        </div>
    </div>

    {{-- STEP 3 — Konfirmasi & Aktivasi --}}
    <div x-show="step === 3" x-cloak>
        <h2 class="font-semibold text-gray-700 mb-3">Konfirmasi</h2>
        <dl class="text-sm grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 mb-4 max-w-md">
            <dt class="text-gray-500">Perusahaan</dt><dd class="font-semibold">{{ $perusahaan?->brand }}</dd>
            <dt class="text-gray-500">Paket</dt><dd class="font-semibold">{{ $package?->name }}</dd>
            <dt class="text-gray-500">Band</dt>
            <dd class="font-semibold">{{ $selectedBand ? number_format($selectedBand->band_min, 0, ',', '.') . '–' . number_format($selectedBand->band_max, 0, ',', '.') . ' karyawan' : ($package?->is_free ? '—' : '—') }}</dd>
            <dt class="text-gray-500">Harga</dt>
            <dd class="font-semibold">{{ $selectedBand ? 'Rp' . number_format($selectedBand->monthly_price, 0, ',', '.') . '/bulan + pajak' : ($package?->is_free ? 'Gratis' : '—') }}</dd>
            <dt class="text-gray-500">Opsi DS</dt>
            <dd class="font-semibold">{{ old('deep_support_mode') === 'one_on_one' ? 'B: sesi 1-on-1' : (in_array($package?->code, ['growth', 'impact']) ? 'A: sesi MHCU' : '—') }}</dd>
            <dt class="text-gray-500">Periode</dt>
            <dd class="font-semibold">
                {{ old('starts_at', now()->addDay()->toDateString()) }} s.d.
                {{ \Illuminate\Support\Carbon::parse(old('starts_at', now()->addDay()))->addDays((int) $package?->duration_days)->subDay()->toDateString() }}
                ({{ $package?->duration_days }} hari)
            </dd>
        </dl>
        <label class="flex items-start gap-2 text-sm mb-4">
            <input type="checkbox" name="confirm" value="1" required @checked(old('confirm')) class="mt-1">
            Saya konfirmasi scope &amp; harga sesuai Program Order.
        </label>
    </div>

    @if($errors->any())
        <p class="text-sm text-red-600 mb-4">{{ $errors->first() }}</p>
    @endif

    {{-- Navigasi step: lanjut via JS; submit final saat step 3 --}}
    <div class="flex gap-3">
        <button type="button" x-show="step > 1" x-cloak @click="step--"
                class="border rounded py-2 px-4 text-sm font-semibold hover:bg-gray-50">← Kembali</button>
        <button type="button" x-show="step < 3" @click="step++"
                class="bg-emerald-600 text-white font-semibold rounded py-2 px-4 text-sm hover:bg-emerald-700">Lanjut →</button>
        <button type="submit" x-show="step === 3" x-cloak
                class="bg-emerald-600 text-white font-semibold rounded py-2 px-4 text-sm hover:bg-emerald-700">Aktifkan Program</button>
    </div>

    {{-- hidden fields supaya state step 2 terbawa saat submit --}}
    @if($step > 1)
        <input type="hidden" name="package_code" value="{{ $package?->code }}">
    @endif
</form>

{{-- §3d: tabel Program Berjalan (form-atas-tabel-bawah ala perusahaans.blade.php) --}}
<div class="flex items-baseline justify-between mb-2">
    <h2 class="font-semibold">Program Berjalan</h2>
</div>
<div class="overflow-x-auto bg-white border rounded-lg mb-8"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Perusahaan</th>
        <th class="p-3">Paket</th>
        <th class="p-3">Band</th>
        <th class="p-3">Opsi DS</th>
        <th class="p-3">Periode</th>
        <th class="p-3">Status</th>
        <th class="p-3"></th>
    </tr></thead>
    <tbody>
        @forelse($programs as $pr)
            <tr class="border-b">
                <td class="p-3 font-semibold">{{ $pr->perusahaan?->brand }}</td>
                <td class="p-3">{{ $pr->wellbeingPackage?->name }}</td>
                <td class="p-3">{{ $pr->pricingBand ? number_format($pr->pricingBand->band_min, 0, ',', '.') . '–' . number_format($pr->pricingBand->band_max, 0, ',', '.') : '—' }}</td>
                <td class="p-3">{{ $pr->wellbeingPackage?->code === 'growth' || $pr->wellbeingPackage?->code === 'impact' ? ($pr->deep_support_mode === 'mhcu' ? 'A: MHCU' : 'B: 1-on-1') : '—' }}</td>
                <td class="p-3">{{ $pr->starts_at->format('d M y') }}–{{ $pr->ends_at->format('d M y') }}</td>
                <td class="p-3">
                    @if($pr->status === 'active')
                        <span class="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 font-semibold">Aktif</span>
                    @elseif($pr->status === 'ended')
                        <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-500">Berakhir</span>
                    @else
                        <span class="text-xs px-2 py-0.5 rounded bg-terra-100 text-terra-700 font-semibold">{{ ucfirst($pr->status) }}</span>
                    @endif
                </td>
                <td class="p-3">
                    <a href="{{ route('admin.wellbeing-programs.order', $pr) }}" class="text-xs text-emerald-700 font-semibold hover:underline">Program Order</a>
                    @if($pr->status === 'active')
                        <form method="POST" action="{{ route('admin.wellbeing-programs.request-termination', $pr) }}" class="flex items-center gap-2 mt-1">
                            @csrf
                            <input type="date" name="effective_date" required min="{{ now()->addDays(30)->toDateString() }}"
                                   value="{{ now()->addDays(30)->toDateString() }}" class="border rounded p-1 text-xs">
                            <button type="submit" class="text-xs text-terra-700 font-semibold hover:underline"
                                    onclick="return confirm('Ajukan terminasi 30-day notice?')">
                                Ajukan Terminasi
                            </button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td class="p-3 text-gray-400" colspan="7">Belum ada program. Assign lewat wizard di atas.</td></tr>
        @endforelse
    </tbody>
</table></div>
@endsection

@push('scripts')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
@endpush
