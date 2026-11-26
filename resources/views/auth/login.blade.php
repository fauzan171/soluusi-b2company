@extends('layouts.app')

@php($bare = true)
@php($title = 'Masuk — ' . config('whitelabel.internal_brand', 'Soluusi'))

@section('content')
<style>
    .login-shell { box-shadow: 0 30px 80px rgba(6, 42, 32, .10); }
    .login-aside {
        background-color: #062a20;
        background-image: linear-gradient(rgba(255,255,255,.035) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.035) 1px, transparent 1px), radial-gradient(circle at 88% 11%, rgba(241,90,35,.28), transparent 22rem), radial-gradient(circle at 4% 88%, rgba(74,163,124,.23), transparent 25rem), linear-gradient(150deg, #062a20 0%, #0c3b2e 56%, #0c5b3e 100%);
        background-size: 32px 32px, 32px 32px, auto, auto, auto;
        background-repeat: repeat, repeat, no-repeat, no-repeat, no-repeat;
    }
    .login-field {
        width: 100%; border: 1px solid #dcd7cd; border-radius: .85rem; background: #fff; padding: .78rem .95rem; color: #1a2420; outline: none;
        transition: border-color .18s ease, box-shadow .18s ease;
    }
    .login-field::placeholder { color: #9c9a94; }
    .login-field:hover { border-color: #b0c9bc; }
    .login-field:focus { border-color: #007648; box-shadow: 0 0 0 4px rgba(0,118,72,.1); }
    .login-field.is-invalid { border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,.08); }
    .login-submit:not(:disabled):hover { transform: translateY(-1px); box-shadow: 0 14px 30px rgba(0,118,72,.22); }
    @keyframes login-rise { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    .login-rise { animation: login-rise .55s ease both; }
    @media (prefers-reduced-motion: reduce) { .login-rise { animation: none; } .login-field, .login-submit { transition: none; } }
</style>

<div class="login-shell min-h-screen overflow-hidden bg-white lg:grid lg:grid-cols-[.78fr_1.22fr]">
    <aside class="login-aside relative overflow-hidden px-7 py-9 text-white sm:px-10 lg:min-h-screen lg:px-12 lg:py-12">
        <div aria-hidden="true" class="absolute -right-16 top-24 h-48 w-48 rounded-full border border-white/10"></div>
        <div aria-hidden="true" class="absolute -right-4 top-36 h-28 w-28 rounded-full border border-terra-400/40"></div>
        <div class="relative lg:sticky lg:top-24">
            <div class="inline-flex w-fit items-center rounded-lg bg-white px-3 py-2 shadow-sm">
                <img src="{{ asset('assets/brand/soluusi.svg') }}" alt="Soluusi" class="h-7 w-auto">
            </div>

            <div class="login-rise mt-14 max-w-md">
                <div class="mb-5 flex items-center gap-3"><span class="h-px w-10 bg-terra-400"></span><p class="text-xs font-semibold uppercase tracking-[.22em] text-pine-200">Portal Soluusi Mitra</p></div>
                <h1 class="text-[2.6rem] font-semibold leading-[1.03] tracking-[-.045em] sm:text-5xl">Selamat datang<br><span class="text-pine-200">kembali.</span></h1>
                <p class="mt-6 max-w-sm text-[15px] leading-7 text-white/70">Masuk untuk mengelola program wellbeing Anda — jadwal, konsultan, dan sesi di satu tempat.</p>
            </div>

            <div class="mt-9 flex flex-wrap gap-x-6 gap-y-2 border-t border-white/10 pt-5 text-[11px] text-white/45">
                <span class="inline-flex items-center gap-2"><svg class="h-4 w-4 text-pine-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>Akses terenkripsi</span>
                <span>SSO Google tersedia</span>
            </div>
        </div>
    </aside>

    <section class="bg-[#fcfbf8] px-6 py-9 sm:px-10 lg:flex lg:items-center lg:px-14 lg:py-12">
        <div class="mx-auto w-full max-w-md">
            <div class="login-rise border-b border-[#ded9cf] pb-8">
                <p class="text-xs font-semibold uppercase tracking-[.18em] text-pine-700">Masuk ke Portal</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-[-.035em] text-pine-950">Akun Anda.</h2>
                <p class="mt-2 text-sm leading-6 text-[#66716c]">Gunakan email dan password yang terdaftar, atau lanjutkan dengan Google.</p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="mt-9 space-y-6">
                @csrf
                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-[#26342e]">Email <span class="text-terra-600">*</span></label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" inputmode="email" placeholder="nama@perusahaan.com" class="login-field @error('email') is-invalid @enderror">
                    @error('email')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-[#26342e]">Password <span class="text-terra-600">*</span></label>
                    <div class="relative">
                        <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Masukkan password" class="login-field pr-12 @error('password') is-invalid @enderror">
                        <button type="button" data-password-toggle="password" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-md p-1 text-[#7c827f] hover:bg-pine-50 hover:text-pine-700" aria-label="Tampilkan password">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                        </button>
                    </div>
                    @error('password')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                    <p class="mt-2 text-right"><a href="{{ route('password.request') }}" class="text-xs font-semibold text-pine-700 underline decoration-pine-200 underline-offset-4 hover:text-pine-900">Lupa password?</a></p>
                </div>

                <div class="border-t border-[#e5e0d6] pt-7">
                    <button type="submit" class="login-submit group flex w-full items-center justify-center gap-3 rounded-xl bg-pine-700 px-5 py-4 text-sm font-semibold text-white transition">
                        <span>Masuk</span>
                        <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </button>
                </div>
            </form>

            <div class="mt-6">
                <div class="relative flex items-center gap-4">
                    <span class="h-px flex-1 bg-[#e5e0d6]"></span>
                    <span class="text-xs font-medium text-[#7c827f]">atau</span>
                    <span class="h-px flex-1 bg-[#e5e0d6]"></span>
                </div>
                <a href="{{ route('login.google', 'google') }}" class="mt-6 flex w-full items-center justify-center gap-3 rounded-xl border border-[#dcd7cd] bg-white px-5 py-3.5 text-sm font-semibold text-[#26342e] transition hover:border-pine-300 hover:bg-pine-50">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                    Masuk dengan Google
                </a>
            </div>

            <p class="mt-8 text-center text-xs text-[#7c827f]">Belum punya akun? <a href="{{ route('partner.register') }}" class="font-semibold text-pine-700 underline decoration-pine-200 underline-offset-4 hover:text-pine-900">Daftar sebagai mitra</a></p>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const input = document.getElementById(toggle.dataset.passwordToggle);
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            toggle.setAttribute('aria-label', showing ? 'Tampilkan password' : 'Sembunyikan password');
        });
    });
});
</script>
@endsection
