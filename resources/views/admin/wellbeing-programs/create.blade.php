@extends('layouts.app')

@section('title', 'Assign Program — Admin')

@section('content')
<div class="flex items-baseline justify-between mb-2">
    <h1 class="text-xl font-bold">Assign Program ({{ $step }}/3)</h1>
    <a href="{{ route('admin.wellbeing-programs.index') }}" class="text-sm text-emerald-700 font-semibold hover:underline">← Program Wellbeing</a>
</div>
<p class="text-sm text-gray-500 mb-6">
    Aktifkan paket untuk perusahaan: pilih paket, band, opsi Deep Support, dan tanggal mulai.
</p>

@if($step === 1)
{{-- STEP 1 — GET form: server memproses pilihan → step 2 dengan konteks lengkap --}}
<form method="GET" action="{{ route('admin.wellbeing-programs.create') }}"
      class="bg-white border rounded-lg p-6 mb-8 max-w-3xl">
    <input type="hidden" name="step" value="2">
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
                <label class="flex items-center gap-1.5 border rounded px-3 py-1.5 cursor-pointer">
                    <input type="radio" name="package_code" value="{{ $pkg->code }}" @checked(old('package_code') === $pkg->code)>
                    {{ $pkg->name }}{{ $pkg->is_free ? ' (Gratis)' : '' }}
                </label>
            @endforeach
        </div>
    </fieldset>
    <button type="submit" class="bg-emerald-600 text-white font-semibold rounded py-2 px-4 text-sm hover:bg-emerald-700">Lanjut →</button>
</form>
@endif

@if($step > 1)
@php
    $bandMap = $bands->mapWithKeys(fn ($b) => [
        $b->band_min => [
            'label' => number_format($b->band_min, 0, ',', '.') . '–' . number_format($b->band_max, 0, ',', '.') . ' karyawan',
            'price' => 'Rp' . number_format($b->monthly_price, 0, ',', '.') . '/bulan',
        ],
    ])->all();
@endphp
<form method="POST" action="{{ route('admin.wellbeing-programs.store') }}"
      class="bg-white border rounded-lg p-6 mb-8 max-w-3xl"
      x-data='{
          step: {{ $step }},
          bandMin: "{{ old('band_min', (string) ($selectedBand?->band_min ?? $suggestedBandMin ?? '')) }}",
          mode: "{{ old('deep_support_mode', $package?->deep_support_mode ?? 'mhcu') }}",
          startsAt: "{{ old('starts_at', now()->addDay()->toDateString()) }}",
          durationDays: {{ (int) ($package?->duration_days ?? 0) }},
          bands: @js($bandMap),
          endsAt() {
              if (!this.startsAt || this.durationDays < 1) return "—";
              const p = this.startsAt.split("-");
              const d = new Date(+p[0], +p[1] - 1, +p[2]);
              d.setDate(d.getDate() + this.durationDays - 1);
              const m = String(d.getMonth() + 1).padStart(2, "0");
              return d.getFullYear() + "-" + m + "-" + String(d.getDate()).padStart(2, "0");
          }
      }'>
    @csrf
    <input type="hidden" name="perusahaan_id" value="{{ $perusahaan?->id }}">
    <input type="hidden" name="package_code" value="{{ $package?->code }}">

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
            <fieldset class="mb-4">
                <legend class="text-sm font-semibold mb-1">Karyawan terpilih Deep Support (maks 8, kosong = semua)</legend>
                <div class="grid gap-1.5 text-sm max-h-48 overflow-y-auto">
                    @foreach($employees as $emp)
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="selected_employee_ids[]" value="{{ $emp->id }}"
                                   @checked(in_array($emp->id, (array) old('selected_employee_ids', [])))>
                            {{ $emp->name }}{{ $emp->departement?->name ? ' — ' . $emp->departement->name : '' }}
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endif

        {{-- B2/D3: instrumen core per paket --}}
        @if($package && $package->code === 'discovery')
            <fieldset class="mb-4">
                <legend class="text-sm font-semibold mb-1">Instrumen Assessment (Mental Health)</legend>
                <div class="grid gap-2 text-sm">
                    <label class="flex items-center gap-2 border rounded px-3 py-1.5 cursor-pointer">
                        <input type="radio" name="discovery_instrument" value="who5" @checked(old('discovery_instrument', 'who5') === 'who5')>
                        WHO-5 Well-Being Index (default)
                    </label>
                    <label class="flex items-center gap-2 border rounded px-3 py-1.5 cursor-pointer">
                        <input type="radio" name="discovery_instrument" value="dass21" @checked(old('discovery_instrument') === 'dass21')>
                        DASS-21 (Depresi, Kecemasan, Stres)
                    </label>
                </div>
            </fieldset>
        @endif

        @if($package && $package->code === 'foundation')
            <fieldset class="mb-4">
                <legend class="text-sm font-semibold mb-1">Domain Assessment (pilih satu)</legend>
                <div class="grid gap-2 text-sm">
                    <label class="flex items-center gap-2 border rounded px-3 py-1.5 cursor-pointer">
                        <input type="radio" name="foundation_domain" value="mh" required @checked(old('foundation_domain') === 'mh')>
                        Mental Health — DASS-21
                    </label>
                    <label class="flex items-center gap-2 border rounded px-3 py-1.5 cursor-pointer">
                        <input type="radio" name="foundation_domain" value="nutrition" @checked(old('foundation_domain') === 'nutrition')>
                        Nutrition — Gizi &amp; Pola Makan
                    </label>
                </div>
            </fieldset>
        @endif

        {{-- D3: extras gratis (hanya domain MH) --}}
        @if($package && in_array($package->code, ['foundation', 'growth', 'impact']))
            <fieldset class="mb-4">
                <legend class="text-sm font-semibold mb-1">Extras domain MH (termasuk, tanpa biaya)</legend>
                <p class="text-xs text-gray-400 mb-2">Hanya aktif bila program memakai domain Mental Health.</p>
                <div class="flex gap-4 text-sm">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="mh_extras[]" value="who5" @checked(in_array('who5', old('mh_extras', [])))>
                        WHO-5
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="mh_extras[]" value="pss10" @checked(in_array('pss10', old('mh_extras', [])))>
                        PSS-10
                    </label>
                </div>
            </fieldset>
        @endif

        {{-- D4: add-on berbayar --}}
        @if($package)
            <fieldset class="mb-4">
                <legend class="text-sm font-semibold mb-1">Add-on instrumen berbayar (opsional)</legend>
                <div class="grid gap-2 text-sm">
                    @foreach(['bigfive' => 'Big Five (Kepribadian)', 'disc' => 'DISC (Gaya Perilaku)', 'ergonomic' => 'Ergonomi & Nyeri Kerja', 'phq9' => 'PHQ-9', 'gad7' => 'GAD-7', 'cbi' => 'CBI (Burnout)'] as $code => $label)
                        <div class="flex items-center gap-3 border rounded px-3 py-1.5">
                            <label class="flex items-center gap-2 flex-1">
                                <input type="checkbox" name="addons[{{ $code }}][code]" value="{{ $code }}"
                                       onchange="this.form.querySelector('[data-price={{ $code }}]').disabled = !this.checked"
                                       @checked(old("addons.{$code}.code") === (string) $code)>
                                {{ $label }}
                            </label>
                            <input type="number" name="addons[{{ $code }}][price]" min="0" max="999999999" placeholder="Rp"
                                   data-price="{{ $code }}" @disabled(old("addons.{$code}.code") !== (string) $code)
                                   value="{{ old("addons.{$code}.price") }}"
                                   class="border rounded p-1 w-32 text-xs">
                        </div>
                    @endforeach
                </div>
                <p class="text-xs text-gray-400 mt-2">Add-on sekali bayar (belum termasuk pajak) dan muncul sebagai baris di Program Order.</p>
            </fieldset>
        @endif

        <div class="grid sm:grid-cols-2 gap-4 mb-4">
            <label class="text-sm">Tanggal Mulai
                <input type="date" name="starts_at" required x-model="startsAt" value="{{ old('starts_at', now()->addDay()->toDateString()) }}" class="w-full border rounded p-2 mt-1">
            </label>
            <label class="text-sm">Durasi Program
                <input type="text" readonly value="{{ $package ? $package->duration_days . ' hari' : '—' }}"
                       class="w-full border rounded p-2 mt-1 bg-gray-50 text-gray-600">
            </label>
        </div>
    </div>

    {{-- STEP 3 — Konfirmasi (ringkasan live dari pilihan step 2) --}}
    <div x-show="step === 3" x-cloak>
        <h2 class="font-semibold text-gray-700 mb-3">Konfirmasi</h2>
        <dl class="text-sm grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 mb-4 max-w-md">
            <dt class="text-gray-500">Perusahaan</dt><dd class="font-semibold">{{ $perusahaan?->brand }}</dd>
            <dt class="text-gray-500">Paket</dt><dd class="font-semibold">{{ $package?->name }}</dd>
            @if($package && !$package->is_free)
                <dt class="text-gray-500">Band</dt>
                <dd class="font-semibold" x-text="bands[bandMin]?.label || '—'">{{ $selectedBand ? $bandMap[$selectedBand->band_min]['label'] : '—' }}</dd>
                <dt class="text-gray-500">Harga</dt>
                <dd class="font-semibold" x-text="bands[bandMin]?.price ? bands[bandMin].price + ' + pajak' : '—'">{{ $selectedBand ? $bandMap[$selectedBand->band_min]['price'] . ' + pajak' : '—' }}</dd>
            @else
                <dt class="text-gray-500">Band</dt><dd class="font-semibold">—</dd>
                <dt class="text-gray-500">Harga</dt><dd class="font-semibold">{{ $package?->is_free ? 'Gratis' : '—' }}</dd>
            @endif
            @if($package && in_array($package->code, ['growth', 'impact']))
                <dt class="text-gray-500">Opsi DS</dt>
                <dd class="font-semibold" x-text="mode === 'one_on_one' ? 'B: sesi 1-on-1' : 'A: sesi MHCU'">{{ old('deep_support_mode') === 'one_on_one' ? 'B: sesi 1-on-1' : 'A: sesi MHCU' }}</dd>
            @endif
            <dt class="text-gray-500">Periode</dt>
            <dd class="font-semibold">
                <span x-text="startsAt">{{ old('starts_at', now()->addDay()->toDateString()) }}</span>
                s.d. <span x-text="endsAt()">{{ \Illuminate\Support\Carbon::parse(old('starts_at', now()->addDay()))->addDays((int) $package?->duration_days)->subDay()->toDateString() }}</span>
                ({{ $package?->duration_days }} hari)
            </dd>
            @if($package?->code === 'foundation')
                <dt class="text-gray-500">Domain</dt>
                <dd class="font-semibold">{{ old('foundation_domain') === 'nutrition' ? 'Nutrition' : 'Mental Health' }}</dd>
            @endif
        </dl>
        <label class="flex items-start gap-2 text-sm mb-4">
            <input type="checkbox" name="confirm" value="1" required @checked(old('confirm')) class="mt-1">
            Saya konfirmasi scope &amp; harga sesuai Program Order.
        </label>
    </div>

    @if($errors->any())
        <p class="text-sm text-red-600 mb-4">{{ $errors->first() }}</p>
    @endif

    {{-- Navigasi step: step 2→3 via JS; submit final saat step 3 --}}
    <div class="flex gap-3">
        @if($step === 2)
            <a href="{{ route('admin.wellbeing-programs.create', ['perusahaan_id' => $perusahaan?->id, 'package_code' => $package?->code]) }}"
               class="border rounded py-2 px-4 text-sm font-semibold hover:bg-gray-50">← Kembali</a>
        @else
            <button type="button" @click="step = 2"
                    class="border rounded py-2 px-4 text-sm font-semibold hover:bg-gray-50">← Kembali</button>
        @endif
        <button type="button" x-show="step === 2" @click="step++"
                class="bg-emerald-600 text-white font-semibold rounded py-2 px-4 text-sm hover:bg-emerald-700">Lanjut →</button>
        <button type="submit" x-show="step === 3" x-cloak
                class="bg-emerald-600 text-white font-semibold rounded py-2 px-4 text-sm hover:bg-emerald-700">Aktifkan Program</button>
    </div>
</form>
@endif

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
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.wellbeing-webinars.index', $pr) }}"
                               class="text-xs text-emerald-700 font-semibold hover:underline">Webinar</a>
                            @if($pr->notice_requested_at)
                                <span class="text-xs text-terra-700 font-semibold">Terminasi efektif {{ \Illuminate\Support\Carbon::parse($pr->terminated_at)->format('d M Y') }}</span>
                                <form method="POST" action="{{ route('admin.wellbeing-programs.cancel-termination', $pr) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-emerald-700 font-semibold hover:underline"
                                            onclick="return confirm('Batalkan pengajuan terminasi?')">Batalkan</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.wellbeing-programs.request-termination', $pr) }}" class="flex items-center gap-2">
                                    @csrf
                                    <input type="date" name="effective_date" required min="{{ now()->addDays(30)->toDateString() }}"
                                           value="{{ now()->addDays(30)->toDateString() }}" class="border rounded p-1 text-xs">
                                    <button type="submit" class="text-xs text-terra-700 font-semibold hover:underline"
                                            onclick="return confirm('Ajukan terminasi 30-day notice?')">
                                        Terminasi
                                    </button>
                                </form>
                            @endif
                        </div>
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
