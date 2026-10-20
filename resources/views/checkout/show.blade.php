{{-- White-label checkout page (guest, Model C). ZERO Soluusi brand (§10.8). --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $brand }} — Checkout Konsultasi</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen">
    <main class="max-w-lg mx-auto p-6">
        <div class="text-xl font-bold text-emerald-700 mb-6">{{ $brand }}</div>

        @if(session('error'))
            <div class="mb-4 p-3 rounded bg-red-100 text-red-800 text-sm">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-4 p-3 rounded bg-red-100 text-red-800 text-sm">
                @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
        @endif

        {{-- Profil konsultan — white-label atas brand klinik, ZERO mention Soluusi (§10.8). --}}
        <div class="bg-white border rounded-xl p-6 mb-4">
            <h2 class="font-bold text-lg mb-4">Konsultan Kamu</h2>
            <div class="flex gap-4">
                <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center text-xl font-bold shrink-0">
                    {{ substr(trim($link->consultant->user->firstname ?? '?'), 0, 1) }}
                </div>
                <div class="min-w-0">
                    <div class="font-bold">{{ trim(($link->consultant->user->firstname ?? '') . ' ' . ($link->consultant->user->lastname ?? '')) }}</div>
                    <div class="text-sm text-gray-500">
                        {{ $link->consultant->user->regency->name ?? '' }}{{ $link->consultant->user->regency?->province ? ', ' . $link->consultant->user->regency->province->name : '' }}
                    </div>
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        @foreach($link->consultant->categories as $category)
                            <span class="text-[11px] bg-emerald-50 text-emerald-800 rounded px-2 py-0.5">{{ $category->name }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            @if($link->consultant->about)
                <p class="text-sm text-gray-600 leading-relaxed mt-4">{{ $link->consultant->about }}</p>
            @endif
        </div>

        <div class="bg-white border rounded-xl p-6 mb-6">
            <h1 class="font-bold text-lg mb-4">Detail Sesi</h1>
            <dl class="text-sm space-y-2">
                <div class="flex justify-between"><dt class="text-gray-500">Konsultan</dt><dd class="font-semibold">{{ trim(($link->consultant->user->firstname ?? '') . ' ' . ($link->consultant->user->lastname ?? '')) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Paket</dt><dd class="font-semibold">{{ $link->package->name ?? '-' }} ({{ $link->package->duration ?? '-' }} mnt)</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Jadwal</dt><dd class="font-semibold">{{ $link->schedule->started_at->translatedFormat('l, d M Y H:i') }} WIB</dd></div>
                <div class="flex justify-between border-t pt-2 mt-2"><dt class="text-gray-500">Total</dt><dd class="font-bold text-emerald-700">Rp{{ number_format($link->final_price, 0, ',', '.') }}</dd></div>
            </dl>
        </div>

        <form method="POST" action="{{ route('checkout.pay', $link->token) }}" class="bg-white border rounded-xl p-6 space-y-4">
            @csrf
            <h2 class="font-bold">Data Kamu</h2>
            <div>
                <label class="block text-sm mb-1">Nama lengkap</label>
                <input name="customer_name" value="{{ old('customer_name') }}" required class="w-full border rounded px-3 py-2">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm mb-1">Email</label>
                    <input type="email" name="customer_email" value="{{ old('customer_email') }}" required class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm mb-1">No. HP / WA</label>
                    <input name="customer_phone" value="{{ old('customer_phone') }}" required class="w-full border rounded px-3 py-2">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm mb-1">Tanggal lahir</label>
                    <input type="date" name="customer_dob" value="{{ old('customer_dob') }}" required class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm mb-1">Jenis kelamin</label>
                    <select name="customer_gender" required class="w-full border rounded px-3 py-2">
                        <option value="">—</option>
                        <option value="male" @selected(old('customer_gender') === 'male')>Laki-laki</option>
                        <option value="female" @selected(old('customer_gender') === 'female')>Perempuan</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm mb-1">Kota/Kabupaten domisili</label>
                <select name="customer_regency_id" required class="w-full border rounded px-3 py-2">
                    <option value="">— pilih —</option>
                    @foreach($regencies as $r)
                    <option value="{{ $r->id }}" @selected(old('customer_regency_id') == $r->id)>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm mb-1">Keluhan singkat (untuk konsultan)</label>
                <textarea name="complaint" rows="3" class="w-full border rounded px-3 py-2">{{ old('complaint') }}</textarea>
            </div>
            <div>
                <label class="block text-sm mb-1">Kode voucher (opsional)</label>
                <input name="voucher" value="{{ old('voucher') }}" class="w-full border rounded px-3 py-2">
            </div>
            <label class="flex items-start gap-2 text-xs text-gray-600">
                <input type="checkbox" name="consent" value="1" required class="mt-0.5">
                <span>Saya menyetujui pemrosesan data pribadi saya untuk keperluan pemesanan dan penyelenggaraan sesi konsultasi ini (UU PDP).</span>
            </label>
            <button class="w-full bg-emerald-700 text-white rounded py-3 font-bold">Bayar Rp{{ number_format($link->final_price, 0, ',', '.') }}</button>
        </form>
    </main>
</body>
</html>
