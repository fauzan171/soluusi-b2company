{{-- White-label customer dashboard page (guest). ZERO Soluusi brand. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $brand }} - Dashboard Sesi</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $tone = [
        'green' => ['hero' => 'from-emerald-900 via-emerald-800 to-teal-700', 'chip' => 'bg-emerald-50 text-emerald-800 border-emerald-200', 'dot' => 'bg-emerald-500'],
        'amber' => ['hero' => 'from-amber-900 via-orange-800 to-amber-700', 'chip' => 'bg-amber-50 text-amber-800 border-amber-200', 'dot' => 'bg-amber-500'],
        'blue' => ['hero' => 'from-sky-900 via-cyan-800 to-teal-700', 'chip' => 'bg-sky-50 text-sky-800 border-sky-200', 'dot' => 'bg-sky-500'],
        'red' => ['hero' => 'from-red-950 via-red-800 to-rose-700', 'chip' => 'bg-red-50 text-red-800 border-red-200', 'dot' => 'bg-red-500'],
        'slate' => ['hero' => 'from-slate-900 via-slate-800 to-gray-700', 'chip' => 'bg-gray-50 text-gray-700 border-gray-200', 'dot' => 'bg-gray-500'],
    ][$dashboard['tone'] ?? 'slate'];

    $sessionStatusText = [
        'waiting' => 'Menunggu pembayaran',
        'scheduled' => 'Terjadwal',
        'done' => 'Selesai',
        'canceled' => 'Dibatalkan',
        'failed' => 'Gagal',
    ];
    $paymentStatusText = [
        'waiting' => 'Menunggu pembayaran',
        'verified' => 'Terverifikasi',
        'canceled' => 'Dibatalkan',
        'failed' => 'Gagal',
    ];
    $consultant = $session?->consultant ?? $link->consultant;
    $schedule = $session?->schedule ?? $link->schedule;
    $package = $session?->consultantPackage ?? $link->package;
    $consultantName = trim(($consultant->user->firstname ?? '') . ' ' . ($consultant->user->lastname ?? ''));
    $meetingUrl = $session?->link_meeting ?: $schedule?->meeting_url;
    $isOnline = ($schedule?->mode ?? $package?->mode ?? 'online') === 'online';
    $amount = $session?->amount ?? $link->final_price;
    $timeline = [
        ['label' => 'Link diterima', 'active' => true],
        ['label' => 'Data & pembayaran', 'active' => ($dashboard['progress'] ?? 1) >= 2],
        ['label' => 'Sesi terjadwal', 'active' => ($dashboard['progress'] ?? 1) >= 3],
        ['label' => 'Selesai', 'active' => ($dashboard['progress'] ?? 1) >= 4],
    ];
@endphp
<body class="min-h-screen bg-[#f6f3ee] text-gray-900">
    <main class="mx-auto max-w-5xl px-4 py-5 sm:px-6 lg:py-8">
        <section class="overflow-hidden rounded-lg bg-gradient-to-br {{ $tone['hero'] }} text-white shadow-sm">
            <div class="p-5 sm:p-7">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-white/75">{{ $brand }}</p>
                        <h1 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">{{ $dashboard['title'] }}</h1>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-white/78">{{ $dashboard['subtitle'] }}</p>
                    </div>
                    <span class="inline-flex w-fit items-center rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-semibold text-white">
                        Dashboard customer
                    </span>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-lg border border-white/12 bg-white/10 p-4">
                        <div class="text-xs font-semibold uppercase text-white/60">Status sesi</div>
                        <div class="mt-2 text-lg font-bold">{{ $dashboard['sessionLabel'] }}</div>
                    </div>
                    <div class="rounded-lg border border-white/12 bg-white/10 p-4">
                        <div class="text-xs font-semibold uppercase text-white/60">Pembayaran</div>
                        <div class="mt-2 text-lg font-bold">{{ $dashboard['paymentLabel'] }}</div>
                    </div>
                    <div class="rounded-lg border border-white/12 bg-white/10 p-4">
                        <div class="text-xs font-semibold uppercase text-white/60">Total bayar</div>
                        <div class="mt-2 text-lg font-bold">Rp{{ number_format((int) $amount, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </section>

        @if(session('success'))
            <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
        @endif

        <section class="mt-4 grid gap-4 lg:grid-cols-[1.1fr_.9fr]">
            <div class="rounded-lg border border-[#e7e2d9] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold">Jadwal konsultasi</h2>
                        <p class="mt-1 text-xs text-gray-500">Detail yang perlu customer simpan.</p>
                    </div>
                    <span class="rounded-full border px-3 py-1 text-xs font-semibold {{ $tone['chip'] }}">
                        {{ $isOnline ? 'Online' : 'Tatap muka' }}
                    </span>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-lg bg-gray-50 p-4">
                        <div class="text-xs font-semibold uppercase text-gray-400">Tanggal</div>
                        <div class="mt-2 text-sm font-bold text-gray-900">{{ $schedule?->started_at?->translatedFormat('l, d M Y') ?? '-' }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-4">
                        <div class="text-xs font-semibold uppercase text-gray-400">Jam</div>
                        <div class="mt-2 text-sm font-bold text-gray-900">
                            {{ $schedule?->started_at?->format('H:i') ?? '-' }} - {{ $schedule?->ended_at?->format('H:i') ?? '-' }} WIB
                        </div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-4">
                        <div class="text-xs font-semibold uppercase text-gray-400">Konsultan</div>
                        <div class="mt-2 text-sm font-bold text-gray-900">{{ $consultantName ?: '-' }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-4">
                        <div class="text-xs font-semibold uppercase text-gray-400">Paket</div>
                        <div class="mt-2 text-sm font-bold text-gray-900">{{ $package->name ?? '-' }}</div>
                    </div>
                </div>

                <div class="mt-4 rounded-lg border border-[#e7e2d9] p-4">
                    <div class="text-xs font-semibold uppercase text-gray-400">{{ $isOnline ? 'Link meeting' : 'Lokasi' }}</div>
                    @if($isOnline)
                        @if($session?->payment_status === 'verified' && $meetingUrl)
                            <a href="{{ $meetingUrl }}" target="_blank" class="mt-2 inline-flex rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-800">Gabung Sesi</a>
                        @elseif($session?->payment_status === 'verified')
                            <p class="mt-2 text-sm text-gray-600">Link meeting akan tampil di sini setelah klinik/konsultan menyiapkan ruang sesi.</p>
                        @else
                            <p class="mt-2 text-sm text-gray-600">Link meeting dibuka setelah pembayaran terverifikasi.</p>
                        @endif
                    @else
                        <p class="mt-2 text-sm font-semibold text-gray-900">{{ $schedule->location ?? 'Lokasi akan dikonfirmasi oleh klinik.' }}</p>
                    @endif
                </div>
            </div>

            <aside class="space-y-4">
                <div class="rounded-lg border border-[#e7e2d9] bg-white p-5 shadow-sm">
                    <h2 class="text-base font-bold">Langkah berikutnya</h2>
                    <p class="mt-2 text-sm leading-6 text-gray-600">{{ $dashboard['nextAction'] }}</p>

                    <div class="mt-4 space-y-2">
                        @foreach($timeline as $step)
                            <div class="flex items-center gap-3 rounded-lg {{ $step['active'] ? 'bg-gray-50' : 'bg-white' }} px-3 py-2">
                                <span class="h-2.5 w-2.5 rounded-full {{ $step['active'] ? $tone['dot'] : 'bg-gray-300' }}"></span>
                                <span class="text-sm font-medium {{ $step['active'] ? 'text-gray-900' : 'text-gray-400' }}">{{ $step['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-lg border border-[#e7e2d9] bg-white p-5 shadow-sm">
                    <h2 class="text-base font-bold">Aksi customer</h2>
                    <div class="mt-4 space-y-3">
                        @if(!$session && $link->isUsable())
                            <a href="{{ route('checkout.show', $link->token) }}" class="block rounded-lg bg-emerald-700 px-4 py-3 text-center text-sm font-bold text-white hover:bg-emerald-800">Lengkapi Data & Bayar</a>
                        @elseif($session?->payment_status === 'waiting' && $session->url_snap)
                            <a href="{{ $session->url_snap }}" class="block rounded-lg bg-emerald-700 px-4 py-3 text-center text-sm font-bold text-white hover:bg-emerald-800">Lanjutkan Pembayaran</a>
                        @endif

                        @if($session?->payment_status === 'verified' && $session->konsultan_done_at && !$session->completion_verified_at)
                            <form method="POST" action="{{ route('checkout.confirmDone', $link->token) }}">
                                @csrf
                                <button class="w-full rounded-lg border border-emerald-700 px-4 py-3 text-sm font-bold text-emerald-700 hover:bg-emerald-50">Konfirmasi Sesi Selesai</button>
                            </form>
                        @endif

                        <code class="block break-all rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600">{{ route('checkout.status', $link->token) }}</code>
                    </div>
                </div>

                @if($session)
                    <div class="rounded-lg border border-[#e7e2d9] bg-white p-5 shadow-sm">
                        <h2 class="text-base font-bold">Data customer</h2>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-xs font-semibold uppercase text-gray-400">Nama</dt>
                                <dd class="mt-1 font-semibold text-gray-900">{{ $session->customer_display_name ?: '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase text-gray-400">Email</dt>
                                <dd class="mt-1 break-all text-gray-700">{{ $session->customer_email ?: '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase text-gray-400">WhatsApp</dt>
                                <dd class="mt-1 text-gray-700">{{ $session->customer_phone ?: '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase text-gray-400">Domisili</dt>
                                <dd class="mt-1 text-gray-700">{{ $session->customerRegency->name ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                @endif
            </aside>
        </section>

        <section class="mt-4 rounded-lg border border-[#e7e2d9] bg-white p-5 shadow-sm">
            <h2 class="text-base font-bold">Arti status di dashboard ini</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-3">
                <div class="rounded-lg bg-gray-50 p-4">
                    <div class="text-sm font-bold">Belum checkout</div>
                    <p class="mt-1 text-xs leading-5 text-gray-500">Link masih aktif, tapi data customer dan pembayaran belum dibuat.</p>
                </div>
                <div class="rounded-lg bg-amber-50 p-4">
                    <div class="text-sm font-bold text-amber-900">Menunggu pembayaran</div>
                    <p class="mt-1 text-xs leading-5 text-amber-800">Data sudah masuk. Jadwal belum final sampai pembayaran berhasil.</p>
                </div>
                <div class="rounded-lg bg-emerald-50 p-4">
                    <div class="text-sm font-bold text-emerald-900">Terjadwal</div>
                    <p class="mt-1 text-xs leading-5 text-emerald-800">Pembayaran sudah terverifikasi dan sesi siap dijalankan sesuai jadwal.</p>
                </div>
                <div class="rounded-lg bg-sky-50 p-4">
                    <div class="text-sm font-bold text-sky-900">Menunggu konfirmasi</div>
                    <p class="mt-1 text-xs leading-5 text-sky-800">Konsultan sudah selesai. Customer tinggal konfirmasi setelah sesi benar berlangsung.</p>
                </div>
                <div class="rounded-lg bg-emerald-50 p-4">
                    <div class="text-sm font-bold text-emerald-900">Selesai</div>
                    <p class="mt-1 text-xs leading-5 text-emerald-800">Sesi sudah selesai dan dikonfirmasi.</p>
                </div>
                <div class="rounded-lg bg-red-50 p-4">
                    <div class="text-sm font-bold text-red-900">Dibatalkan / gagal</div>
                    <p class="mt-1 text-xs leading-5 text-red-800">Sesi tidak aktif. Customer perlu minta link atau jadwal baru ke klinik.</p>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
