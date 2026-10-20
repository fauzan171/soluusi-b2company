@extends('layouts.app')

@section('content')
<h1 class="text-xl font-bold mb-1">Sesi Saya</h1>
<p class="text-sm text-gray-500 mb-6">Rate kamu mengikuti paket konsultan. Harga jual klinik ke client tidak ditampilkan di portal konsultan.</p>

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
