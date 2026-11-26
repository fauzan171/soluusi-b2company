@extends('layouts.app')

@section('content')
<div class="flex items-baseline justify-between mb-2">
    <h1 class="text-xl font-bold">Katalog Paket Wellbeing</h1>
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-emerald-700 font-semibold hover:underline">← Dashboard</a>
</div>
<p class="text-sm text-gray-500 mb-6">
    Empat paket siap pakai: Discovery, Foundation, Growth, Impact. Atur isi dan
    harga per band karyawan di sini.
</p>

<div class="overflow-x-auto bg-white border rounded-lg"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Paket</th><th class="p-3">Durasi</th><th class="p-3">Kuota Chat</th>
        <th class="p-3">Deep Support</th><th class="p-3">Webinar</th><th class="p-3">Harga</th>
        <th class="p-3">Status</th><th class="p-3">Aksi</th>
    </tr></thead>
    <tbody>
        @forelse($packages as $package)
            <tr class="border-b">
                <td class="p-3 font-semibold">{{ $package->name }}</td>
                <td class="p-3">
                    @if($package->duration_days >= 365)
                        {{ round($package->duration_days / 365) }} tahun
                    @elseif($package->duration_days >= 90)
                        {{ round($package->duration_days / 30) }} bulan
                    @else
                        {{ $package->duration_days }} hari
                    @endif
                </td>
                <td class="p-3">{{ $package->expert_chat_experts }} expert</td>
                <td class="p-3">
                    @if($package->deep_support_quota > 0)
                        {{ $package->deep_support_quota }}
                        {{ $package->deep_support_mode === 'mhcu' ? 'MHCU' : 'sesi 1-on-1' }}
                    @else
                        —
                    @endif
                </td>
                <td class="p-3">{{ $package->webinar_count ?: '—' }}</td>
                <td class="p-3">
                    @if($package->is_free)
                        Gratis
                    @elseif($package->pricingBands->count())
                        <span class="font-semibold">Rp{{ number_format($package->pricingBands->min('monthly_price'), 0, ',', '.') }}</span>
                        <span class="text-xs text-gray-400">– Rp{{ number_format($package->pricingBands->max('monthly_price'), 0, ',', '.') }}+ / bulan, exclude pajak</span>
                    @else
                        —
                    @endif
                </td>
                <td class="p-3">
                    @if($package->is_active)
                        <span class="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 font-semibold">Aktif</span>
                    @else
                        <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-500">Nonaktif</span>
                    @endif
                </td>
                <td class="p-3">
                    <a href="{{ route('admin.wellbeing-packages.edit', $package) }}"
                       class="text-xs bg-emerald-700 text-white rounded px-3 py-1.5 font-semibold">Edit</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="p-6 text-center text-gray-400">Belum ada paket. Jalankan WellbeingPackageSeeder.</td></tr>
        @endforelse
    </tbody>
</table></div>
@endsection
