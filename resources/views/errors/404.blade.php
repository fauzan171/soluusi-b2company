<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman tidak ditemukan</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-50 text-slate-900">
<div class="max-w-md mx-auto text-center px-6 py-24">
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 mb-2">404</p>
    <h1 class="text-2xl font-semibold mb-2">Halaman tidak ditemukan</h1>
    <p class="text-slate-600 mb-6">
        Alamat yang kamu buka tidak ada di {{ config('app.name') }}.
        Mungkin link-nya sudah dipindah atau salah ketik.
    </p>
    <a href="{{ url('/') }}" class="inline-block px-5 py-2 rounded-lg bg-slate-900 text-white">
        Kembali ke beranda
    </a>
</div>
</body>
</html>
