@extends('layouts.app')

@php
    $title = 'Lengkapi Profil — ' . config('whitelabel.internal_brand', 'Soluusi');
    $isSekolah = $vertical === 'sekolah';
@endphp

@section('content')
<div class="mx-auto max-w-lg border border-[#dcd7cd] bg-white rounded-2xl px-7 py-9 sm:px-10">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-pine-700">Selamat Datang</p>
    <h1 class="mt-2 text-2xl font-semibold tracking-[-.03em] text-pine-950">Konfirmasi profil kamu.</h1>
    <p class="mt-2 text-sm leading-6 text-[#66716c]">
        Data ini kami terima dari {{ $isSekolah ? 'sekolah' : 'HR' }}. Periksa namanya, lalu
        tentukan {{ $isSekolah ? 'kelas' : 'divisi' }} kamu.
    </p>

    <form method="POST" action="{{ $isSekolah ? route('sekolah.profile.store') : route('perusahaan.profile.store') }}" class="mt-6 space-y-5">
        @csrf
        <div>
            <label for="name" class="mb-2 block text-sm font-medium text-[#26342e]">Nama lengkap <span class="text-terra-600">*</span></label>
            <input id="name" type="text" name="name" value="{{ old('name', $row->name) }}" required maxlength="255"
                class="w-full rounded-xl border px-4 py-3 text-sm bg-white outline-none transition
                    {{ $errors->has('name') ? 'border-red-400 focus:border-red-500' : 'border-[#dcd7cd] focus:border-pine-500' }}">
            @error('name')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
        </div>

        @if($isSekolah)
            <div>
                @php
                    // ONB.6: staf (guru/karyawan) tidak punya kelas — kolom opsional.
                    $isStaff = $row->type === \App\Models\Sekolah\SekolahStudent::TYPE_STAFF;
                @endphp
                <label for="kelas" class="mb-2 block text-sm font-medium text-[#26342e]">Kelas
                    @if($isStaff)<span class="text-gray-400">(opsional untuk staf)</span>@else<span class="text-terra-600">*</span>@endif
                </label>
                <input id="kelas" type="text" name="kelas" list="kelas-options" value="{{ old('kelas', $row->kelas) }}"
                    @unless($isStaff) required @endunless maxlength="100" autocomplete="off"
                    class="w-full rounded-xl border px-4 py-3 text-sm bg-white outline-none transition
                        {{ $errors->has('kelas') ? 'border-red-400 focus:border-red-500' : 'border-[#dcd7cd] focus:border-pine-500' }}">
                <datalist id="kelas-options">
                    @foreach($kelasOptions as $kelas)
                        <option value="{{ $kelas }}"></option>
                    @endforeach
                </datalist>
                <p class="mt-1.5 text-xs text-[#8a938e]">Pilih dari daftar atau tulis kelas kamu sendiri.</p>
                @error('kelas')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
            </div>
        @else
            <div>
                <label for="departement_id" class="mb-2 block text-sm font-medium text-[#26342e]">Divisi <span class="text-gray-400">(opsional)</span></label>
                <select id="departement_id" name="departement_id"
                    class="w-full rounded-xl border border-[#dcd7cd] px-4 py-3 text-sm bg-white outline-none transition focus:border-pine-500">
                    <option value="">— Belum ditentukan —</option>
                    @foreach($departements as $dept)
                        <option value="{{ $dept->id }}" @selected(old('departement_id', $row->departement_id) == $dept->id)>{{ $dept->name }}</option>
                    @endforeach
                </select>
                @error('departement_id')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
            </div>
        @endif

        <button type="submit" class="w-full rounded-xl bg-pine-700 px-5 py-3.5 text-sm font-semibold text-white transition hover:bg-pine-800">
            Simpan dan masuk
        </button>
    </form>
</div>
@endsection