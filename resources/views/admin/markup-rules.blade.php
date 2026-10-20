@extends('layouts.app')

@section('content')
<div class="flex flex-wrap items-start justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl font-bold">Markup Paket — {{ $klinik->brand_name }}</h1>
        <p class="text-sm text-gray-500 mt-1">Harga dikunci saat checkout link dibuat. Nilai di atas cap otomatis ditolak.</p>
    </div>
    <a href="{{ route('klinik.dashboard', ['klinik_id' => $klinik->id]) }}" class="text-sm text-emerald-700 font-semibold hover:underline">← Dashboard klinik</a>
</div>

<form method="GET" class="mb-4 flex gap-2 max-w-xl">
    <input name="q" value="{{ request('q') }}" placeholder="Cari paket atau konsultan" class="flex-1 border rounded-lg px-3 py-2 text-sm">
    <button class="bg-emerald-700 text-white rounded-lg px-4 py-2 text-sm font-semibold">Cari</button>
</form>

<div class="overflow-x-auto bg-white border rounded-lg">
    <table class="w-full text-sm">
        <thead><tr class="border-b text-left text-gray-500">
            <th class="p-3">Konsultan</th><th class="p-3">Paket</th><th class="p-3">Net</th><th class="p-3">Markup</th><th class="p-3">Final</th><th class="p-3">Aksi</th>
        </tr></thead>
        <tbody>
        @forelse($packages as $package)
            @php
                $markup = (int) optional($rules->get($package->id))->markup_amount;
                $formId = 'markup-' . $package->id;
            @endphp
            <tr class="border-b">
                <td class="p-3">{{ $package->consultant->user->full_name ?? '—' }}</td>
                <td class="p-3"><span class="font-medium">{{ $package->name }}</span><div class="text-xs text-gray-400">{{ strtoupper($package->mode) }} · {{ $package->duration }} menit</div></td>
                <td class="p-3">Rp{{ number_format($package->price, 0, ',', '.') }}</td>
                <td class="p-3">
                    <form id="{{ $formId }}" method="POST" action="{{ route('admin.kliniks.markups.store', $klinik) }}">@csrf
                        <input type="hidden" name="consultant_package_id" value="{{ $package->id }}">
                        <input type="number" name="markup_amount" min="0" step="1000" value="{{ old('consultant_package_id') == $package->id ? old('markup_amount') : $markup }}" class="w-36 border rounded px-2 py-1.5">
                    </form>
                </td>
                <td class="p-3 font-semibold">Rp{{ number_format((int) $package->price + $markup, 0, ',', '.') }}</td>
                <td class="p-3"><button form="{{ $formId }}" class="text-xs bg-emerald-700 text-white rounded px-3 py-1.5 font-semibold">Simpan</button></td>
            </tr>
        @empty
            <tr><td colspan="6" class="p-6 text-center text-gray-400">Tidak ada paket konsultan aktif.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $packages->links() }}</div>
@endsection
