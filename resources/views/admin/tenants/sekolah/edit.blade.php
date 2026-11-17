@extends('layouts.app')

@section('title', 'Edit ' . $sekolah->brand . ' — Tenant Sekolah')

@section('content')
<div class="flex items-baseline justify-between mb-2">
    <h1 class="text-xl font-bold">Edit Sekolah</h1>
    <a href="{{ route('admin.tenants.sekolahs.show', $sekolah) }}" class="text-sm text-emerald-700 font-semibold hover:underline">← Detail</a>
</div>
<p class="text-sm text-gray-500 mb-6">Hanya field aman yang bisa diubah. Status program dikelola lewat aksi aktivasi ops.</p>

<form method="POST" action="{{ route('admin.tenants.sekolahs.update', $sekolah) }}"
      class="bg-white border rounded-lg p-6 grid md:grid-cols-2 gap-4 max-w-3xl">
    @csrf
    @method('PUT')
    <label class="text-sm">Nama yayasan / legal
        <input name="name" required maxlength="100" value="{{ old('name', $sekolah->name) }}" class="w-full border rounded p-2 mt-1">
    </label>
    <label class="text-sm">Brand tampilan
        <input name="brand" required maxlength="100" value="{{ old('brand', $sekolah->brand) }}" class="w-full border rounded p-2 mt-1">
    </label>
    <label class="text-sm">Tingkat
        <select name="tingkat" class="w-full border rounded p-2 mt-1 bg-white">
            @foreach(['' => '—', 'SMP' => 'SMP', 'SMA' => 'SMA', 'SMK' => 'SMK', 'Kampus' => 'Kampus'] as $val => $label)
                <option value="{{ $val }}" @selected(old('tingkat', $sekolah->tingkat) === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="text-sm">Sesi/siswa/bulan
        <input name="sessions_per_student_month" type="number" min="1" max="30" value="{{ old('sessions_per_student_month', $sekolah->sessions_per_student_month) }}" class="w-full border rounded p-2 mt-1">
    </label>
    <label class="text-sm flex items-center gap-2 md:col-span-2">
        <input type="hidden" name="ai_enabled" value="0">
        <input type="checkbox" name="ai_enabled" value="1" @checked(old('ai_enabled', $sekolah->ai_enabled)) class="rounded">
        AI insight aktif untuk tenant ini
    </label>
    @if($errors->any())
        <p class="md:col-span-2 text-sm text-red-600">{{ $errors->first() }}</p>
    @endif
    <button type="submit" class="md:col-span-2 bg-emerald-600 text-white font-semibold rounded py-2 hover:bg-emerald-700">Simpan</button>
</form>
@endsection
