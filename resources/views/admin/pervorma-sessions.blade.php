@extends('layouts.app')

@section('content')
<h1 class="text-xl font-bold mb-1">Sesi Kontributor Pervorma HPA — <a href="{{ route('admin.dashboard') }}" class="text-sm text-emerald-700 font-normal hover:underline">← Dashboard</a></h1>
<p class="text-sm text-gray-500 mb-6">Sesi yang di-tag berkontribusi ke stage pervorma (1/2/3). Sesi tanpa tag = di atas stage-3 trigger atau di luar window 90 hari.</p>

<form method="GET" class="mb-4 flex gap-3 items-end">
    <div>
        <label class="block text-xs text-gray-500 mb-1">Klinik</label>
        <select name="klinik_id" class="border rounded px-3 py-2 text-sm">
            <option value="">— semua —</option>
            @foreach($kliniks as $k)
            <option value="{{ $k->id }}" @selected(request('klinik_id') == $k->id)>{{ $k->brand_name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Stage</label>
        <select name="stage" class="border rounded px-3 py-2 text-sm">
            <option value="">— semua —</option>
            @foreach([1,2,3] as $s)
            <option value="{{ $s }}" @selected(request('stage') == $s)>Stage {{ $s }}</option>
            @endforeach
        </select>
    </div>
    <button class="bg-emerald-700 text-white rounded px-4 py-2 text-sm font-semibold">Filter</button>
</form>

<table class="w-full bg-white border rounded-lg text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Klinik</th>
        <th class="p-3">Konsultan</th>
        <th class="p-3">Stage</th>
        <th class="p-3">Selesai pada</th>
        <th class="p-3">Jadwal</th>
        <th class="p-3">Bayar</th>
    </tr></thead>
    <tbody>
        @forelse($sessions as $s)
        <tr class="border-b">
            <td class="p-3"><a class="text-emerald-700 hover:underline" href="{{ route('klinik.dashboard', ['klinik_id' => $s->klinik_id]) }}">{{ $s->klinik->brand_name ?? '—' }}</a></td>
            <td class="p-3">{{ trim(($s->consultant->user->firstname ?? '') . ' ' . ($s->consultant->user->lastname ?? '')) }}</td>
            <td class="p-3"><span class="bg-emerald-100 text-emerald-800 text-xs font-semibold rounded px-2 py-1">Stage {{ $s->hap_pervorma_stage }}</span></td>
            <td class="p-3">{{ optional($s->completion_verified_at)->format('d M Y H:i') }}</td>
            <td class="p-3">{{ optional($s->schedule)->started_at?->format('d M Y H:i') ?? '—' }}</td>
            <td class="p-3">Rp{{ number_format((int) $s->payment, 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="p-3 text-gray-400">Belum ada sesi yang di-tag.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="mt-4">{{ $sessions->links() }}</div>
@endsection
