{{-- Local-only dummy payment page for presentation. Remove with dummy routes after real Midtrans keys are ready. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $brand }} - Dummy Payment</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper text-ink antialiased">
    <main class="mx-auto flex min-h-screen max-w-5xl items-center px-4 py-8 sm:px-6">
        <section class="grid w-full overflow-hidden rounded-2xl border border-[#e7e2d9] bg-white shadow-[0_24px_70px_rgba(6,42,32,.12)] lg:grid-cols-[.92fr_1.08fr]">
            <aside class="bg-pine-950 px-6 py-8 text-white sm:px-8">
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-pine-200">Mode Presentasi</p>
                <h1 class="mt-3 text-3xl font-semibold leading-tight">{{ $brand }}</h1>
                <p class="mt-4 text-sm leading-6 text-white/65">Halaman ini hanya aktif di lokal saat konfigurasi Midtrans belum diisi. Dipakai untuk demo alur booking sampai status sesi.</p>

                <div class="mt-8 rounded-xl border border-white/10 bg-white/10 p-4">
                    <p class="text-xs text-white/50">Order</p>
                    <p class="mt-1 font-semibold">{{ $session->order_number }}</p>
                </div>
            </aside>

            <div class="px-6 py-8 sm:px-8">
                <div class="flex items-start justify-between gap-4 border-b border-[#e7e2d9] pb-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-pine-700">Pembayaran Dummy</p>
                        <h2 class="mt-2 text-2xl font-semibold text-pine-950">Konfirmasi pembayaran</h2>
                    </div>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">Demo</span>
                </div>

                <dl class="mt-6 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Customer</dt>
                        <dd class="text-right font-semibold">{{ $session->customer_display_name }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Konsultan</dt>
                        <dd class="text-right font-semibold">{{ trim(($link->consultant->user->firstname ?? '') . ' ' . ($link->consultant->user->lastname ?? '')) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Paket</dt>
                        <dd class="text-right font-semibold">{{ $link->package->name }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Jadwal</dt>
                        <dd class="text-right font-semibold">{{ $link->schedule->started_at->translatedFormat('D, d M Y H:i') }} WIB</dd>
                    </div>
                </dl>

                <div class="mt-6 rounded-xl bg-pine-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-pine-700">Total pembayaran</p>
                    <p class="mt-2 text-3xl font-semibold text-pine-950">Rp{{ number_format($session->amount, 0, ',', '.') }}</p>
                    @if($session->voucher_amount > 0)
                        <p class="mt-1 text-xs text-gray-500">Sudah termasuk voucher Rp{{ number_format($session->voucher_amount, 0, ',', '.') }}.</p>
                    @endif
                </div>

                @if($session->payment_status === \App\Models\ConsultationSession::PAYMENT_STATUS_WAITING)
                    <form method="POST" action="{{ route('checkout.dummyPayment.success', $link->token) }}" class="mt-6">
                        @csrf
                        <button class="w-full rounded-xl bg-pine-700 px-5 py-3 text-sm font-semibold text-white hover:bg-pine-800">
                            Simulasikan Pembayaran Berhasil
                        </button>
                    </form>
                @else
                    <a href="{{ route('checkout.status', $link->token) }}" class="mt-6 block w-full rounded-xl bg-pine-700 px-5 py-3 text-center text-sm font-semibold text-white hover:bg-pine-800">
                        Lihat Status Sesi
                    </a>
                @endif

                <p class="mt-4 text-center text-xs text-gray-400">Dummy page lokal. Tidak memproses pembayaran asli.</p>
            </div>
        </section>
    </main>
</body>
</html>
