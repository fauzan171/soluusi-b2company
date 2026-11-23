@extends('layouts.app')

@section('content')
<div class="flex items-baseline justify-between mb-2">
    <h1 class="text-xl font-bold">Edit Paket — {{ $package->name }}</h1>
    <a href="{{ route('admin.wellbeing-packages.index') }}" class="text-sm text-emerald-700 font-semibold hover:underline">← Katalog</a>
</div>
<p class="text-sm text-gray-500 mb-6">
    {{ $package->duration_days >= 365 ? round($package->duration_days / 365).' tahun' : ($package->duration_days >= 90 ? round($package->duration_days / 30).' bulan' : $package->duration_days.' hari') }}
    · Harga band Rp/bulan, exclude pajak. Angka terkunci saat program diaktifkan.
</p>

<form method="POST" action="{{ route('admin.wellbeing-packages.update', $package) }}"
      class="bg-white border rounded-lg p-6 mb-8 grid md:grid-cols-2 gap-4 max-w-3xl">
    @csrf
    @method('PUT')
    <h2 class="md:col-span-2 font-semibold text-gray-700">Isi paket</h2>
    <label class="text-sm md:col-span-2">Deskripsi singkat (1 kalimat)
        <textarea name="description" rows="2" maxlength="500" class="w-full border rounded p-2 mt-1"
                  placeholder="Intervensi terarah 6 bulan di dua domain, dengan pilihan Deep Support sesuai kebutuhan tim.">{{ old('description', $package->description) }}</textarea>
    </label>
    <label class="text-sm">Kuota chat expert
        <input name="expert_chat_experts" type="number" min="0" max="99" required
               value="{{ old('expert_chat_experts', $package->expert_chat_experts) }}" class="w-full border rounded p-2 mt-1">
    </label>
    <label class="text-sm">Sesi deep support
        <input name="deep_support_quota" type="number" min="0" max="999" required
               value="{{ old('deep_support_quota', $package->deep_support_quota) }}" class="w-full border rounded p-2 mt-1">
    </label>
    <label class="text-sm">Jumlah webinar
        <input name="webinar_count" type="number" min="0" max="99" required
               value="{{ old('webinar_count', $package->webinar_count) }}" class="w-full border rounded p-2 mt-1">
    </label>
    <label class="text-sm">Status
        <select name="is_active" class="w-full border rounded p-2 mt-1">
            <option value="1" @selected(old('is_active', $package->is_active ? '1' : '0') === '1')>Aktif</option>
            <option value="0" @selected(old('is_active', $package->is_active ? '1' : '0') === '0')>Nonaktif</option>
        </select>
    </label>
    @if($errors->any())
        <p class="md:col-span-2 text-sm text-red-600">{{ $errors->first() }}</p>
    @endif
    <button type="submit" class="md:col-span-2 bg-emerald-600 text-white font-semibold rounded py-2 hover:bg-emerald-700">
        Simpan paket
    </button>
</form>

@if(! $package->is_free)
<h2 class="font-semibold text-gray-700 mb-1">Harga per Band Karyawan</h2>
<p class="text-sm text-gray-500 mb-3">
    Harga bulanan menyesuaikan jumlah karyawan. Angka terkunci saat program diaktifkan.
</p>
<div class="overflow-x-auto bg-white border rounded-lg max-w-3xl"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Band karyawan</th><th class="p-3">Harga / bulan (Rp, exclude pajak)</th>
        <th class="p-3">MHCU / bulan</th><th class="p-3">Aksi</th>
    </tr></thead>
    <tbody>
        @forelse($package->pricingBands as $band)
            @php $formId = 'band-' . $band->id; @endphp
            <tr class="border-b">
                <td class="p-3">
                    {{ number_format($band->band_min, 0, ',', '.') }}–{{ number_format($band->band_max, 0, ',', '.') }} karyawan
                </td>
                <td class="p-3">
                    <form id="{{ $formId }}" method="POST" action="{{ route('admin.wellbeing-packages.bands.update', [$package, $band]) }}">@csrf
                        @method('PUT')
                        <input type="number" name="monthly_price" min="0" step="1000" required
                               value="{{ old('monthly_price', $band->monthly_price) }}" class="w-44 border rounded px-2 py-1.5">
                    </form>
                </td>
                <td class="p-3">
                    <input type="number" name="mhcu_quota" min="0" form="{{ $formId }}"
                           value="{{ old('mhcu_quota', $band->mhcu_quota) }}" class="w-20 border rounded px-2 py-1.5">
                </td>
                <td class="p-3">
                    <button form="{{ $formId }}" class="text-xs bg-emerald-700 text-white rounded px-3 py-1.5 font-semibold">Simpan</button>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="p-6 text-center text-gray-400">Belum ada band harga. Jalankan WellbeingPackageSeeder.</td></tr>
        @endforelse
    </tbody>
</table></div>
@else
<p class="text-sm text-gray-500 bg-white border rounded-lg p-4 max-w-3xl">
    Discovery adalah paket gratis — tanpa harga band.
</p>
@endif
@endsection
