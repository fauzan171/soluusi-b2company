@extends('layouts.app')

@section('content')
<div class="max-w-sm mx-auto mt-16 bg-white rounded-xl border p-8">
    <h1 class="text-lg font-bold mb-6">Masuk</h1>
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="w-full border rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm mb-1">Password</label>
            <input type="password" name="password" required class="w-full border rounded px-3 py-2">
        </div>
        <button class="w-full bg-emerald-700 text-white rounded py-2 font-semibold">Masuk</button>
    </form>
    <div class="mt-4 text-center">
        <a href="{{ route('login.google', 'google') }}" class="text-sm text-emerald-700 hover:underline">Masuk dengan Google</a>
    </div>
</div>
@endsection
