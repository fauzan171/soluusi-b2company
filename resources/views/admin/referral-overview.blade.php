@extends('layouts.app')

@section('content')
<div class="flex items-baseline justify-between mb-2">
    <h1 class="text-xl font-bold">Referral HPA → Klinik</h1>
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-emerald-700 font-semibold hover:underline">← Dashboard</a>
</div>
<p class="text-sm text-gray-500 mb-6">
    Afiliasi: HPA mana membawa klinik siapa. Payout performa harus sebanding dengan
    aktivitas order — klinik tanpa sesi selesai 90 hari terakhir ditandai untuk dicek.
</p>

<table class="w-full bg-white border rounded-lg text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">HPA (referral)</th>
        <th class="p-3">Klinik</th>
        <th class="p-3">Sesi selesai (90 hr)</th>
        <th class="p-3">Sesi selesai total</th>
        <th class="p-3">Gross verified</th>
        <th class="p-3">Order terakhir</th>
        <th class="p-3">Performa paid</th>
        <th class="p-3">Performa due</th>
        <th class="p-3">Status</th>
    </tr></thead>
    <tbody>
        @forelse($kliniks as $k)
            @php
                $s = $stats->get($k->id);
                $p = $pervorma->get($k->id);
                $c90 = (int) ($s->completed_90 ?? 0);
                $paid = (int) ($p->paid_total ?? 0);
                $due = (int) ($p->due_total ?? 0);
                // Anti-bodong: uang keluar tapi klinik tidak ada aktivitas.
                $flag = $c90 === 0 ? (($paid + $due) > 0 ? 'mencurigakan' : 'sepi') : ($c90 >= 5 ? 'aktif' : 'rendah');
            @endphp
            <tr class="border-b {{ $flag === 'mencurigakan' ? 'bg-red-50' : '' }}">
                <td class="p-3">{{ $k->associate ? trim($k->associate->firstname . ' ' . $k->associate->lastname) : '—' }}</td>
                <td class="p-3 font-semibold"><a class="text-emerald-700 hover:underline" href="{{ route('klinik.dashboard', ['klinik_id' => $k->id]) }}">{{ $k->brand }} →</a><div class="text-xs text-gray-400">{{ $k->name }} · <a class="text-emerald-700 hover:underline" href="{{ route('admin.kliniks.packages.index', $k) }}">kelola paket</a></div></td>
                <td class="p-3 font-semibold {{ $c90 === 0 ? 'text-red-600' : '' }}">{{ $c90 }}</td>
                <td class="p-3">{{ (int) ($s->completed_total ?? 0) }}</td>
                <td class="p-3">Rp{{ number_format((int) ($s->revenue_verified ?? 0), 0, ',', '.') }}</td>
                <td class="p-3">{{ $s && $s->last_order_at ? \Illuminate\Support\Carbon::parse($s->last_order_at)->translatedFormat('d M Y') : '—' }}</td>
                <td class="p-3">Rp{{ number_format($paid, 0, ',', '.') }}</td>
                <td class="p-3">Rp{{ number_format($due, 0, ',', '.') }}</td>
                <td class="p-3">
                    @if($flag === 'mencurigakan')
                        <span class="text-xs px-2 py-0.5 rounded bg-red-100 text-red-700 font-semibold">Cek — 0 sesi tapi ada performa</span>
                    @elseif($flag === 'sepi')
                        <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-500">Belum ada aktivitas</span>
                    @elseif($flag === 'rendah')
                        <span class="text-xs px-2 py-0.5 rounded bg-amber-100 text-amber-800">Aktivitas rendah</span>
                    @else
                        <span class="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-800">Aktif</span>
                    @endif
                </td>
            </tr>
        @empty
        <tr><td colspan="9" class="p-3 text-gray-400">Belum ada mitra klinik.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
