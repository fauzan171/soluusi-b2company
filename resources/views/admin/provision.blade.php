@extends('layouts.app')

@section('title', 'Provisioning tenant — Admin')

@section('content')
<div class="flex items-baseline justify-between mb-2">
    <h1 class="text-xl font-bold">Provisioning Tenant (1 Pintu)</h1>
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-emerald-700 font-semibold hover:underline">← Dashboard</a>
</div>
<p class="text-sm text-gray-500 mb-6">Buat tenant + akun admin tenant dalam 1 transaksi. Self-register /daftar-mitra tetap masuk approval queue terpisah.</p>

<form method="POST" action="{{ route('admin.provision.store') }}"
      class="bg-white border rounded-lg p-6 grid md:grid-cols-2 gap-4 max-w-3xl" x-data="{ vertical: '{{ old('vertical', 'klinik') }}' }">
    @csrf

    <fieldset class="md:col-span-2">
        <legend class="text-sm font-semibold mb-1">Vertikal</legend>
        <div class="flex flex-wrap gap-3 text-sm">
            @foreach(['klinik' => 'Klinik (HAP)', 'perusahaan' => 'Perusahaan (CAP)', 'sekolah' => 'Sekolah (SAP)'] as $val => $label)
                <label class="flex items-center gap-1.5 border rounded px-3 py-1.5 cursor-pointer" :class="vertical === '{{ $val }}' ? 'border-emerald-600 bg-emerald-50' : ''">
                    <input type="radio" name="vertical" value="{{ $val }}" x-model="vertical" @checked(old('vertical') === $val)>
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </fieldset>

    <label class="text-sm">Nama legal
        <input name="name" required maxlength="100" value="{{ old('name') }}" class="w-full border rounded p-2 mt-1" placeholder="Klinik Sehat Sentosa">
    </label>
    <label class="text-sm">Brand tampilan
        <input name="brand" required maxlength="100" value="{{ old('brand') }}" class="w-full border rounded p-2 mt-1" placeholder="SehatCare">
    </label>

    <label class="text-sm" x-show="vertical === 'sekolah'" :class="vertical === 'sekolah' ? '' : 'hidden'">
        Tingkat
        <select name="tingkat" class="w-full border rounded p-2 mt-1 bg-white">
            @foreach(['' => '— pilih —', 'SMP' => 'SMP', 'SMA' => 'SMA', 'SMK' => 'SMK', 'Kampus' => 'Kampus'] as $val => $label)
                <option value="{{ $val }}" @selected(old('tingkat') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="text-sm" x-show="vertical === 'perusahaan'" :class="vertical === 'perusahaan' ? '' : 'hidden'">
        Rate PEPM (Rp)
        <input type="number" name="pepm_rate" min="0" value="{{ old('pepm_rate', 15000) }}" class="w-full border rounded p-2 mt-1">
    </label>

    <div class="md:col-span-2" x-show="vertical === 'perusahaan'" :class="vertical === 'perusahaan' ? '' : 'hidden'">
        <p class="text-sm font-semibold mb-1">Pool konsultan (opsional)</p>
        <div class="grid sm:grid-cols-2 gap-1.5 max-h-44 overflow-y-auto border rounded p-3 text-sm">
            @forelse($consultants as $c)
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="consultant_ids[]" value="{{ $c->id }}" @checked(in_array($c->id, old('consultant_ids', [])))>
                    {{ $c->user?->fullname ?? 'Konsultan #' . $c->id }}
                </label>
            @empty
                <p class="text-gray-500">Belum ada konsultan berstatus accept.</p>
            @endforelse
        </div>
    </div>

    <p class="md:col-span-2 text-xs text-gray-400 -mb-2">Akun admin tenant</p>
    <label class="text-sm">Nama admin
        <input name="admin_name" required maxlength="100" value="{{ old('admin_name') }}" class="w-full border rounded p-2 mt-1" placeholder="Ani Wijaya">
    </label>
    <label class="text-sm">Email admin
        <input type="email" name="admin_email" required maxlength="150" value="{{ old('admin_email') }}" class="w-full border rounded p-2 mt-1" placeholder="admin@tenant.co.id">
    </label>
    <label class="text-sm">Password awal
        <input type="password" name="admin_password" required minlength="8" class="w-full border rounded p-2 mt-1">
    </label>

    @if($errors->any())
        <p class="md:col-span-2 text-sm text-red-600">{{ $errors->first() }}</p>
    @endif
    <button type="submit" class="md:col-span-2 bg-emerald-600 text-white font-semibold rounded py-2 hover:bg-emerald-700">
        Buat tenant + akun admin
    </button>
</form>
@endsection

@push('scripts')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
@endpush

