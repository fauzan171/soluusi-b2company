<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun — {{ config('whitelabel.default_brand') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-paper text-ink min-h-screen grid place-items-center">
    <div class="w-full max-w-md p-6">
        <div class="portal-card p-6">
            <h1 class="text-lg font-bold text-pine-900">Daftar akun {{ $type === 'siswa' ? 'siswa' : 'karyawan' }}</h1>
            <p class="text-sm text-gray-500 mt-1 mb-5">
                @if($type === 'siswa')
                Gunakan email yang terdaftar di roster sekolah (dari guru BK). Izin orang tua wajib untuk mendaftar.
                @else
                Gunakan email yang terdaftar di roster perusahaan (dari HR).
                @endif
            </p>

            @if(session('error'))<div class="mb-4 p-3 rounded-xl bg-red-100 text-red-800 text-sm">{{ session('error') }}</div>@endif
            @if(isset($errors) && $errors->any())
            <div class="mb-4 p-3 rounded-xl bg-red-100 text-red-800 text-sm">
                @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
            @endif

            <form method="POST" action="{{ request()->fullUrl() }}" class="space-y-3">
                @csrf
                <input type="text" name="name" placeholder="Nama lengkap (sesuai roster)" value="{{ old('name') }}"
                       class="w-full border border-[#e7e2d9] rounded-xl px-3 py-2 text-sm" required>
                <input type="email" name="email" placeholder="Email terdaftar" value="{{ old('email') }}"
                       class="w-full border border-[#e7e2d9] rounded-xl px-3 py-2 text-sm" required>
                <input type="password" name="password" placeholder="Password (min. 8 karakter)"
                       class="w-full border border-[#e7e2d9] rounded-xl px-3 py-2 text-sm" required minlength="8">
                <p class="text-xs text-gray-600">Undangan untuk {{ $invitation->email }}. Akun existing wajib login sebagai pemilik email; password existing tidak diubah. Consent wali/program dicatat HR/BK terpisah.</p>
                <button class="w-full bg-pine-700 text-white text-sm font-semibold py-3 rounded-xl">Daftar & Masuk</button>
            </form>
            <p class="text-xs text-gray-400 mt-4">Sudah punya akun? <a href="{{ route('loginIndex') }}" class="text-pine-700 font-semibold">Login di sini</a></p>
        </div>
    </div>
</body>
</html>
