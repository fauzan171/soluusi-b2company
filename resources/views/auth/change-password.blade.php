@extends('layouts.app')

@php($title = 'Ganti Password — ' . config('whitelabel.internal_brand', 'Soluusi'))

@section('content')
<div class="mx-auto max-w-md border border-[#dcd7cd] bg-white rounded-2xl px-7 py-9 sm:px-10">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-pine-700">Keamanan Akun</p>
    <h1 class="mt-2 text-2xl font-semibold tracking-[-.03em] text-pine-950">Buat password baru.</h1>
    <p class="mt-2 text-sm leading-6 text-[#66716c]">
        Password awal dari file onboarding hanya dipakai sekali. Ganti sekarang sebelum masuk dashboard.
    </p>

    <form method="POST" action="{{ route('password.change.store') }}" class="mt-6 space-y-5">
        @csrf
        <div>
            <label for="current_password" class="mb-2 block text-sm font-medium text-[#26342e]">Password saat ini <span class="text-terra-600">*</span></label>
            <input id="current_password" type="password" name="current_password" required autocomplete="current-password"
                class="w-full rounded-xl border px-4 py-3 text-sm bg-white outline-none transition
                    {{ $errors->has('current_password') ? 'border-red-400 focus:border-red-500' : 'border-[#dcd7cd] focus:border-pine-500' }}">
            @error('current_password')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="mb-2 block text-sm font-medium text-[#26342e]">Password baru <span class="text-terra-600">*</span></label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                class="w-full rounded-xl border px-4 py-3 text-sm bg-white outline-none transition
                    {{ $errors->has('password') ? 'border-red-400 focus:border-red-500' : 'border-[#dcd7cd] focus:border-pine-500' }}">
            <p class="mt-1.5 text-xs text-[#8a938e]">Minimal 8 karakter, berbeda dari password lama.</p>
            @error('password')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="mb-2 block text-sm font-medium text-[#26342e]">Ulangi password baru <span class="text-terra-600">*</span></label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                class="w-full rounded-xl border border-[#dcd7cd] px-4 py-3 text-sm bg-white outline-none transition focus:border-pine-500">
        </div>
        <button type="submit" class="w-full rounded-xl bg-pine-700 px-5 py-3.5 text-sm font-semibold text-white transition hover:bg-pine-800">Simpan password baru</button>
    </form>
</div>
@endsection