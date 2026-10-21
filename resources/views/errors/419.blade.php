<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sesi berakhir</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-50 text-slate-900">
<div class="max-w-md mx-auto text-center px-6 py-24">
    <h1 class="text-2xl font-semibold mb-2">Sesi berakhir</h1>
    <p class="text-slate-600 mb-6">
        Halaman yang kamu kirim sudah kedaluwarsa. Ini terjadi kalau tab dibiarkan
        terbuka lebih dari 2 jam, atau kamu login di tab lain.
    </p>
    <a href="{{ route('loginIndex') }}" class="inline-block px-5 py-2 rounded-lg bg-slate-900 text-white">
        Masuk lagi
    </a>
</div>
</body>
</html>
