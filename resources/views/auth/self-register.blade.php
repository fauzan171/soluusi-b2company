@extends('layouts.app')

@php($title = 'Daftar Akun — ' . config('whitelabel.default_brand'))

@section('content')
<style>
    .selfreg-field {
        width: 100%; border: 1px solid #dcd7cd; border-radius: .85rem; background: #fff; padding: .78rem .95rem; color: #1a2420; outline: none;
        transition: border-color .18s ease, box-shadow .18s ease;
    }
    .selfreg-field::placeholder { color: #9c9a94; }
    .selfreg-field:hover { border-color: #b0c9bc; }
    .selfreg-field:focus { border-color: #007648; box-shadow: 0 0 0 4px rgba(0,118,72,.1); }
    .selfreg-field.is-invalid { border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,.08); }
    .selfreg-submit:not(:disabled):hover { transform: translateY(-1px); box-shadow: 0 14px 30px rgba(0,118,72,.22); }
    @media (prefers-reduced-motion: reduce) { .selfreg-field, .selfreg-submit { transition: none; } }
</style>

<div class="mx-auto max-w-md">
    <div class="portal-card overflow-hidden">
        <div class="border-b border-[#e5e0d6] bg-[#fbfaf7] px-6 py-5">
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-pine-700">Aktivasi Akun</p>
            <h1 class="mt-1.5 text-2xl font-semibold tracking-[-.03em] text-pine-950">Daftar akun {{ $type === 'siswa' ? 'siswa' : 'karyawan' }}</h1>
            <p class="mt-2 text-sm leading-6 text-[#66716c]">
                @if($type === 'siswa')
                    Gunakan email yang terdaftar di roster sekolah (dari guru BK). Izin orang tua wajib untuk mendaftar.
                @else
                    Gunakan email yang terdaftar di roster perusahaan (dari HR).
                @endif
            </p>
        </div>

        <div class="px-6 py-6">
            <form method="POST" action="{{ request()->fullUrl() }}" class="space-y-4">
                @csrf
                <div>
                    <label for="name" class="mb-2 block text-sm font-medium text-[#26342e]">Nama lengkap <span class="text-terra-600">*</span></label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autocomplete="name" placeholder="Sesuai roster" class="selfreg-field @error('name') is-invalid @enderror">
                    @error('name')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-[#26342e]">Email terdaftar <span class="text-terra-600">*</span></label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" inputmode="email" placeholder="nama@email.com" class="selfreg-field @error('email') is-invalid @enderror">
                    @error('email')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-[#26342e]">Password <span class="text-terra-600">*</span></label>
                    <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="Minimal 8 karakter" class="selfreg-field @error('password') is-invalid @enderror">
                    @error('password')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="rounded-xl border border-[#e5e0d6] bg-[#fbfaf7] px-4 py-3 text-xs leading-5 text-[#59645f]">
                    Undangan untuk <span class="font-semibold text-pine-900">{{ $invitation->email }}</span>. Akun existing wajib login sebagai pemilik email; password existing tidak diubah. Consent wali/program dicatat HR/BK terpisah.
                </div>

                <button type="submit" class="selfreg-submit flex w-full items-center justify-center gap-2 rounded-xl bg-pine-700 px-5 py-3.5 text-sm font-semibold text-white transition">
                    Daftar &amp; Masuk
                </button>
            </form>

            <p class="mt-5 text-center text-xs text-[#7c827f]">Sudah punya akun? <a href="{{ route('loginIndex') }}" class="font-semibold text-pine-700 underline decoration-pine-200 underline-offset-4 hover:text-pine-900">Masuk di sini</a></p>
        </div>
    </div>
</div>
@endsection
