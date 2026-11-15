@extends('layouts.app')

@php($title = 'Lupa Password — ' . config('whitelabel.internal_brand', 'Soluusi'))

@section('content')
<div class="mx-auto max-w-md border border-[#dcd7cd] bg-white rounded-2xl px-7 py-9 sm:px-10">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-pine-700">Reset Password</p>
    <h1 class="mt-2 text-2xl font-semibold tracking-[-.03em] text-pine-950">Lupa password?</h1>
    <p class="mt-2 text-sm leading-6 text-[#66716c]">Masukkan email yang terdaftar. Kami kirim link untuk membuat password baru.</p>

    @if(session('status'))
    <div class="mt-5 rounded-xl border border-pine-200 bg-pine-50 p-3 text-sm text-pine-800" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
        @csrf
        <div>
            <label for="email" class="mb-2 block text-sm font-medium text-[#26342e]">Email <span class="text-terra-600">*</span></label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" inputmode="email"
                class="w-full rounded-xl border px-4 py-3 text-sm bg-white outline-none transition
                    {{ $errors->has('email') ? 'border-red-400 focus:border-red-500' : 'border-[#dcd7cd] focus:border-pine-500' }}">
            @error('email')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="w-full rounded-xl bg-pine-700 px-5 py-3.5 text-sm font-semibold text-white transition hover:bg-pine-800">Kirim link reset</button>
    </form>

    <p class="mt-6 text-center text-xs text-[#7c827f]">Ingat passwordnya? <a href="{{ route('loginIndex') }}" class="font-semibold text-pine-700 underline decoration-pine-200 underline-offset-4 hover:text-pine-900">Kembali masuk</a></p>
</div>
@endsection
