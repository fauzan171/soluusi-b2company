@extends('layouts.app')

@section('title', 'Sekolah baru — Admin')

@section('content')
<div class="flex items-baseline justify-between mb-2">
    <h1 class="text-xl font-bold">Sekolah Baru</h1>
    <a href="{{ route('admin.tenants.sekolahs.index') }}" class="text-sm text-emerald-700 font-semibold hover:underline">← Semua sekolah</a>
</div>
<p class="text-sm text-gray-500 mb-6">Tenant sekolah dibuat nonaktif (pending aktivasi) — akun BK dan roster diurus setelah aktivasi program.</p>

<form method="POST" action="{{ route('admin.tenants.sekolahs.store') }}"
      class="bg-white border rounded-lg p-6 grid md:grid-cols-2 gap-4 max-w-3xl">
    @csrf
    <label class="text-sm">Nama yayasan / legal
        <input name="name" required maxlength="100" value="{{ old('name') }}" class="w-full border rounded p-2 mt-1" placeholder="SMA Cendekia Nusantara">
    </label>
    <label class="text-sm">Brand tampilan
        <input name="brand" required maxlength="100" value="{{ old('brand') }}" class="w-full border rounded p-2 mt-1" placeholder="SMA Cendekia">
    </label>
    <label class="text-sm">Tingkat (opsional)
        <select name="tingkat" class="w-full border rounded p-2 mt-1 bg-white">
            @foreach(['' => '— pilih —', 'SMP' => 'SMP', 'SMA' => 'SMA', 'SMK' => 'SMK', 'Kampus' => 'Kampus'] as $val => $label)
                <option value="{{ $val }}" @selected(old('tingkat') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    @if($errors->any())
        <p class="md:col-span-2 text-sm text-red-600">{{ $errors->first() }}</p>
    @endif
    <button type="submit" class="md:col-span-2 bg-emerald-600 text-white font-semibold rounded py-2 hover:bg-emerald-700">Buat sekolah</button>
</form>
@endsection
