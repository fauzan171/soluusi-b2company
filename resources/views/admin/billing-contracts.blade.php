@extends('layouts.app')

@php
    $title = 'Billing & Kontrak';
    $statusLabels = ['active' => 'Aktif', 'ended' => 'Berakhir', 'cancelled' => 'Dibatalkan'];
@endphp

@section('content')
<div class="max-w-6xl">
    <div class="mb-4">
        <h1 class="text-xl font-bold">Billing &amp; Kontrak</h1>
        <p class="text-sm text-gray-500">Daftar kontrak Wellbeing — harga exclude pajak. Badge "renewal" muncul saat berakhir &lt; 30 hari.</p>
    </div>

    <div class="dash-card p-5">
        @if($contracts->isEmpty())
            <p class="text-sm text-gray-400">Belum ada kontrak.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-400 border-b border-gray-100">
                            <th class="py-2 pr-4 font-medium">Perusahaan</th>
                            <th class="py-2 pr-4 font-medium">Paket</th>
                            <th class="py-2 pr-4 font-medium">Band</th>
                            <th class="py-2 pr-4 font-medium font-right text-right">Harga/bulan</th>
                            <th class="py-2 pr-4 font-medium">Periode</th>
                            <th class="py-2 pr-4 font-medium">Notice</th>
                            <th class="py-2 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contracts as $c)
                        <tr class="border-b border-gray-50 last:border-0">
                            <td class="py-2.5 pr-4 text-gray-700">
                                {{ $c->perusahaan?->brand ?? '—' }}
                                @if($c->renewal_due)<span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800">renewal &lt;30d</span>@endif
                            </td>
                            <td class="py-2.5 pr-4 text-gray-500">{{ $c->wellbeingPackage?->name ?? '—' }}</td>
                            <td class="py-2.5 pr-4 text-gray-500">{{ $c->pricingBand ? $c->pricingBand->band_min.'–'.$c->pricingBand->band_max : '—' }}</td>
                            <td class="py-2.5 pr-4 text-right text-gray-700">Rp {{ number_format((float) $c->monthly_price, 0, ',', '.') }}</td>
                            <td class="py-2.5 pr-4 text-gray-500">{{ optional($c->starts_at)->format('d M y') }} – {{ optional($c->ends_at)->format('d M y') }}</td>
                            <td class="py-2.5 pr-4">
                                @if($c->notice_requested_at)
                                    <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700">Diminta {{ optional($c->notice_requested_at)->format('d M y') }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="py-2.5">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $c->status === 'active' ? 'bg-pine-100 text-pine-800' : 'bg-gray-100 text-gray-500' }}">{{ $statusLabels[$c->status] ?? $c->status }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $contracts->links() }}
        @endif
    </div>
</div>
@endsection
