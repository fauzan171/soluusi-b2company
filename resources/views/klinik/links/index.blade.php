@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold">Checkout Link</h1>
    <a href="{{ route('klinik.links.create') }}" class="bg-emerald-700 text-white rounded px-4 py-2 text-sm font-semibold">+ Buat Link</a>
</div>

<section class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-sm font-semibold text-emerald-950">Link booking customer klinik</p>
            <p class="mt-1 text-xs leading-5 text-emerald-800">Kirim link ini ke customer agar mereka bisa memilih konsultan, jadwal, paket, lalu bayar sendiri.</p>
        </div>
        <a href="{{ route('checkout.klinik', $klinik->uuid) }}" target="_blank" class="shrink-0 rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Buka Link Customer</a>
    </div>
    <code class="mt-3 block break-all rounded-xl border border-emerald-200 bg-white px-3 py-2 text-xs text-emerald-900">{{ route('checkout.klinik', $klinik->uuid) }}</code>
</section>

@if($createdLink)
<section class="mb-5 rounded-lg border border-emerald-300 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-sm font-bold text-emerald-900">Link customer berhasil dibuat</p>
            <p class="mt-1 text-xs leading-5 text-gray-500">
                {{ trim(($createdLink->consultant->user->firstname ?? '') . ' ' . ($createdLink->consultant->user->lastname ?? '')) }}
                · {{ $createdLink->package->name ?? 'Paket konsultasi' }}
                · {{ $createdLink->schedule->started_at->translatedFormat('d M Y H.i') }}
            </p>
            <div class="mt-3 space-y-2">
                <code class="block break-all rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-950">{{ route('checkout.show', $createdLink->token) }}</code>
                <code class="block break-all rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600">Dashboard: {{ route('checkout.status', $createdLink->token) }}</code>
            </div>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2">
            <a href="{{ route('checkout.show', $createdLink->token) }}" target="_blank" class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Buka Checkout</a>
            <a href="{{ route('checkout.status', $createdLink->token) }}" target="_blank" class="rounded-lg border border-emerald-200 px-4 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">Dashboard</a>
        </div>
    </div>
</section>
@endif

<table class="w-full bg-white border rounded-lg text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Konsultan</th><th class="p-3">Jadwal</th><th class="p-3">Paket</th><th class="p-3">Harga</th><th class="p-3">Status</th><th class="p-3">Link customer</th>
    </tr></thead>
    <tbody>
        @forelse($links as $l)
        <tr class="border-b">
            <td class="p-3">{{ trim(($l->consultant->user->firstname ?? '') . ' ' . ($l->consultant->user->lastname ?? '')) }}</td>
            <td class="p-3">{{ $l->schedule->started_at->translatedFormat('d M Y H.i') }}</td>
            <td class="p-3">{{ $l->package->name ?? '-' }}</td>
            <td class="p-3">Rp{{ number_format($l->final_price, 0, ',', '.') }}</td>
            <td class="p-3">
                @if($l->used_at) <span class="text-emerald-700">Terpakai</span>
                @elseif($l->expires_at->isPast()) <span class="text-red-600">Kedaluwarsa</span>
                @else <span class="text-gray-600">Aktif</span> @endif
            </td>
            <td class="p-3">
                <div class="space-y-2">
                    @if($l->isUsable() || $l->used_at)
                        <code class="block break-all text-xs">{{ route('checkout.show', $l->token) }}</code>
                    @else
                        <span class="text-gray-300">Checkout tidak aktif</span>
                    @endif
                    <a href="{{ route('checkout.status', $l->token) }}" target="_blank" class="inline-flex rounded border border-emerald-200 px-2 py-1 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-50">Dashboard customer</a>
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="p-3 text-gray-400">Belum ada link.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="mt-4">{{ $links->links() }}</div>
@endsection
