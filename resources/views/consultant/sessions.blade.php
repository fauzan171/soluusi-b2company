@extends('layouts.app')

@section('content')
<h1 class="text-xl font-bold mb-1">Sesi Saya</h1>
<p class="text-sm text-gray-500 mb-6">Rate kamu mengikuti paket konsultan. Harga jual klinik ke client tidak ditampilkan di portal konsultan.</p>

@if($companySessions->isNotEmpty())
<h2 class="font-semibold text-sm mb-2">Sesi karyawan perusahaan (CAP)</h2>
<table class="w-full bg-white border rounded-lg text-sm mb-6">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Jadwal</th><th class="p-3">Karyawan</th><th class="p-3">Perusahaan</th><th class="p-3">Status</th><th class="p-3"></th>
    </tr></thead>
    <tbody>
        @foreach($companySessions as $cs)
        <tr class="border-b {{ !$cs->scheduled_at ? 'bg-amber-50' : '' }}">
            <td class="p-3">
                @if($cs->scheduled_at)
                    {{ $cs->scheduled_at->format('d M Y H:i') }}
                @else
                    <span class="text-amber-700 font-medium">Belum dijadwalkan</span>
                @endif
            </td>
            <td class="p-3">Anonim #{{ $cs->employee_id }}</td>
            <td class="p-3">{{ $cs->perusahaan?->brand ?? '—' }}</td>
            <td class="p-3">{{ ucfirst($cs->status) }}</td>
            <td class="p-3 space-y-1">
                <a href="{{ route('consultant.chat', $cs) }}" class="text-xs bg-pine-700 text-white rounded px-2 py-1 inline-block">Chat</a>
                @if(!$cs->scheduled_at && $cs->status === 'scheduled')
                <form method="POST" action="{{ route('consultant.sessions.schedule', $cs) }}" class="inline-block">
                    @csrf
                    <select name="schedule_id" required class="text-xs border rounded px-1 py-0.5" onchange="this.form.submit()">
                        <option value="">Set jadwal…</option>
                        @foreach(\App\Services\BookingService::available('perusahaan', $cs->perusahaan_id) as $slot)
                        <option value="{{ $slot->id }}">{{ $slot->started_at->format('d M H:i') }}</option>
                        @endforeach
                    </select>
                </form>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<table class="w-full bg-white border rounded-lg text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Jadwal</th><th class="p-3">Customer</th><th class="p-3">Klinik</th><th class="p-3">Status</th><th class="p-3">Aksi</th>
    </tr></thead>
    <tbody>
        @forelse($sessions as $s)
        <tr class="border-b align-top">
            <td class="p-3">{{ optional($s->schedule)->started_at?->format('d M Y H:i') }}</td>
            <td class="p-3">{{ $s->customer_display_name }}</td>
            <td class="p-3">{{ $s->klinik->brand_name ?? '-' }}</td>
            <td class="p-3">
                {{ ucfirst(str_replace('_', ' ', $s->status)) }}
                @if($s->konsultan_done_at && !$s->completion_verified_at)
                    <div class="text-xs text-amber-600">Menunggu konfirmasi customer</div>
                @endif
            </td>
            <td class="p-3 space-y-2">
                @if($s->status === 'scheduled')
                    @if(!$s->konsultan_checked_in_at)
                    <form method="POST" action="{{ route('consultant.sessions.checkIn', $s) }}">@csrf
                        <button class="text-xs bg-gray-200 rounded px-2 py-1">Check-in</button>
                    </form>
                    @endif
                    @if(!$s->konsultan_done_at)
                    <form method="POST" action="{{ route('consultant.sessions.markDone', $s) }}" class="space-y-1">@csrf
                        <textarea name="note" rows="2" placeholder="Catatan sesi (rahasia)" class="w-full border rounded px-2 py-1 text-xs"></textarea>
                        <textarea name="solution" rows="2" placeholder="Ringkasan untuk customer" class="w-full border rounded px-2 py-1 text-xs"></textarea>
                        <button class="text-xs bg-emerald-700 text-white rounded px-2 py-1">Tandai Selesai</button>
                    </form>
                    @endif
                    @if($s->link_meeting)
                        <a href="{{ $s->link_meeting }}" class="block text-xs text-emerald-700">Link Meet →</a>
                    @endif
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="5" class="p-3 text-gray-400">Belum ada sesi.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="mt-4">{{ $sessions->links() }}</div>
@endsection
