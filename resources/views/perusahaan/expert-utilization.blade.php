@extends('layouts.app')

@php
    $title = 'Utilisasi Expert';
@endphp

@section('content')
<div class="max-w-4xl">
    <div class="mb-4">
        <h1 class="text-xl font-bold">Utilisasi Expert</h1>
        <p class="text-sm text-gray-500">Agregat sesi per expert pool — data individual karyawan tidak ditampilkan.</p>
    </div>

    <div class="dash-card p-5 mb-4">
        <div class="grid gap-4 sm:grid-cols-2 text-sm">
            <div class="flex items-center justify-between">
                <span class="text-gray-500">Kuota Chat Expert</span>
                <span class="font-semibold text-pine-900">{{ $quotaChat['used'] }} / {{ $quotaChat['total'] }} sesi</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-500">Kuota Deep Support</span>
                <span class="font-semibold text-pine-900">{{ $quotaDeep['used'] }} / {{ $quotaDeep['total'] }} sesi</span>
            </div>
        </div>
    </div>

    <div class="dash-card p-5">
        <h2 class="text-[0.95rem] font-semibold text-gray-900 mb-3">Expert pool ({{ $rows->count() }})</h2>
        @if($rows->isEmpty())
            <p class="text-sm text-gray-400">Belum ada expert di pool perusahaan Anda.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-400 border-b border-gray-100">
                            <th class="py-2 pr-4 font-medium">Expert</th>
                            <th class="py-2 pr-4 font-medium">Kategori</th>
                            <th class="py-2 pr-4 font-medium text-right">Sesi selesai</th>
                            <th class="py-2 pr-4 font-medium text-right">Terjadwal</th>
                            <th class="py-2 font-medium text-right">Rating rata-rata</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $r)
                        <tr class="border-b border-gray-50 last:border-0">
                            <td class="py-2.5 pr-4 text-gray-700">{{ $r['name'] }}</td>
                            <td class="py-2.5 pr-4 text-gray-500">{{ $r['category'] ?: '—' }}</td>
                            <td class="py-2.5 pr-4 text-right text-gray-700">{{ $r['sessions'] }}</td>
                            <td class="py-2.5 pr-4 text-right text-gray-500">{{ $r['scheduled'] }}</td>
                            <td class="py-2.5 text-right {{ $r['avgRating'] !== null ? 'text-pine-700 font-medium' : 'text-gray-400' }}">{{ $r['avgRating'] ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
