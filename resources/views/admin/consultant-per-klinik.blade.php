@extends('layouts.app')

@section('content')
<h1 class="text-xl font-bold mb-1">Konsultan per Mitra Klinik — <a href="{{ route('admin.dashboard') }}" class="text-sm text-emerald-700 font-normal hover:underline">← Dashboard</a></h1>
<p class="text-sm text-gray-500 mb-6">Jumlah konsultasi tiap konsultan, dikelompokkan per klinik. Urut: completed terbanyak.</p>

<div class="overflow-x-auto bg-white border rounded-lg"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Klinik</th>
        <th class="p-3">Konsultan</th>
        <th class="p-3">Total sesi</th>
        <th class="p-3">Completed</th>
        <th class="p-3">No-show</th>
        <th class="p-3">Revenue verified</th>
        <th class="p-3">Sesi terakhir</th>
    </tr></thead>
    <tbody>
        @forelse($rows as $r)
        @php
            $klinik = $kliniks->get($r->klinik_id);
            $consultant = $consultants->get($r->consultant_id);
            $cname = $consultant?->user ? trim(($consultant->user->firstname ?? '') . ' ' . ($consultant->user->lastname ?? '')) : '—';
        @endphp
        <tr class="border-b">
            <td class="p-3"><a class="text-emerald-700 hover:underline" href="{{ route('klinik.dashboard', ['klinik_id' => $r->klinik_id]) }}">{{ $klinik->brand_name ?? '—' }}</a></td>
            <td class="p-3"><a class="text-emerald-700 hover:underline" href="{{ route('klinik.consultants.show', $r->consultant_id) }}">{{ $cname }}</a></td>
            <td class="p-3">{{ $r->total_sessions }}</td>
            <td class="p-3 font-semibold text-emerald-700">{{ $r->completed_sessions }}</td>
            <td class="p-3">{{ $r->no_show_sessions }}</td>
            <td class="p-3">Rp{{ number_format((int) $r->verified_revenue, 0, ',', '.') }}</td>
            <td class="p-3">{{ $r->last_session_at ? \Carbon\Carbon::parse($r->last_session_at)->translatedFormat('d M Y H.i') : '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="7" class="p-3 text-gray-400">Belum ada sesi dari mitra klinik.</td></tr>
        @endforelse
    </tbody>
</table></div>
@endsection
