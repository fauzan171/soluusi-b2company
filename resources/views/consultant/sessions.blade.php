@extends('layouts.app')

@section('content')
@php
    // Badge status konsisten: tone pine/terra/slate sesuai makna status.
    $badge = function (string $status) {
        return match ($status) {
            'scheduled' => 'bg-pine-100 text-pine-800',
            'completed', 'done', 'settled' => 'bg-terra-100 text-terra-800',
            'cancelled', 'canceled' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-700',
        };
    };
    $label = fn (string $status) => ucfirst(str_replace('_', ' ', $status));
@endphp

<div class="mb-6">
    <h1 class="text-xl font-bold text-pine-900">Sesi Saya</h1>
    <p class="text-sm text-gray-500 mt-1">Rate kamu mengikuti paket konsultan. Harga jual klinik ke client tidak ditampilkan di portal konsultan.</p>
</div>

@if($companySessions->isNotEmpty())
<section class="portal-card overflow-hidden mb-6">
    <header class="px-5 py-4 border-b border-[#e7e2d9] flex items-center justify-between">
        <div>
            <h2 class="text-sm font-semibold text-ink">Sesi karyawan perusahaan (CAP)</h2>
            <p class="text-xs text-gray-500 mt-0.5">{{ $companySessions->count() }} sesi dari program perusahaan</p>
        </div>
    </header>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-[#e7e2d9] text-left text-xs uppercase tracking-wide text-gray-500">
                    <th class="px-5 py-3 font-medium">Jadwal</th>
                    <th class="px-5 py-3 font-medium">Karyawan</th>
                    <th class="px-5 py-3 font-medium">Perusahaan</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#f1ede4]">
                @foreach($companySessions as $cs)
                <tr class="{{ !$cs->scheduled_at ? 'bg-terra-50/60' : 'hover:bg-pine-50/40' }} transition-colors">
                    <td class="px-5 py-3 whitespace-nowrap">
                        @if($cs->scheduled_at)
                            <span class="font-medium text-ink">{{ $cs->scheduled_at->format('d M Y') }}</span>
                            <span class="text-gray-500 text-xs block">{{ $cs->scheduled_at->format('H:i') }} WIB</span>
                        @else
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-terra-100 text-terra-800">Belum dijadwalkan</span>
                        @endif
                    </td>
                    <td class="px-5 py-3">Anonim #{{ $cs->employee_id }}</td>
                    <td class="px-5 py-3">{{ $cs->perusahaan?->brand ?? '—' }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $badge($cs->status) }}">{{ $label($cs->status) }}</span>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('consultant.chat', $cs) }}" class="inline-flex items-center rounded-lg bg-pine-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-pine-800 transition-colors">Chat</a>
                            @if(!$cs->scheduled_at && $cs->status === 'scheduled')
                            <form method="POST" action="{{ route('consultant.sessions.schedule', $cs) }}">
                                @csrf
                                <select name="schedule_id" required class="text-xs border border-[#e7e2d9] rounded-lg px-2 py-1.5 focus:outline-none focus:border-pine-400" onchange="this.form.submit()">
                                    <option value="">Set jadwal…</option>
                                    @foreach(\App\Services\BookingService::available('perusahaan', $cs->perusahaan_id) as $slot)
                                    <option value="{{ $slot->id }}">{{ $slot->started_at->format('d M H:i') }}</option>
                                    @endforeach
                                </select>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endif

<section class="portal-card overflow-hidden">
    <header class="px-5 py-4 border-b border-[#e7e2d9] flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-sm font-semibold text-ink">Sesi klinik</h2>
            <p class="text-xs text-gray-500 mt-0.5">{{ $sessions->total() }} sesi terdaftar</p>
        </div>
        {{-- ponytail: filter client-side via data-status; ganti query param server-side kalau perlu paginasi per-status. --}}
        <div id="session-filter" class="flex flex-wrap gap-1.5">
            <button data-filter="all" class="filter-btn rounded-full px-3 py-1 text-xs font-medium bg-pine-700 text-white">Semua</button>
            <button data-filter="scheduled" class="filter-btn rounded-full px-3 py-1 text-xs font-medium bg-gray-100 text-gray-700 hover:bg-pine-100">Terjadwal</button>
            <button data-filter="completed" class="filter-btn rounded-full px-3 py-1 text-xs font-medium bg-gray-100 text-gray-700 hover:bg-pine-100">Selesai</button>
            <button data-filter="cancelled" class="filter-btn rounded-full px-3 py-1 text-xs font-medium bg-gray-100 text-gray-700 hover:bg-pine-100">Batal</button>
        </div>
    </header>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-[#e7e2d9] text-left text-xs uppercase tracking-wide text-gray-500">
                    <th class="px-5 py-3 font-medium">Jadwal</th>
                    <th class="px-5 py-3 font-medium">Customer</th>
                    <th class="px-5 py-3 font-medium">Klinik</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#f1ede4]" id="session-rows">
                @forelse($sessions as $s)
                <tr class="hover:bg-pine-50/40 transition-colors align-top" data-status="{{ $s->status }}">
                    <td class="px-5 py-3 whitespace-nowrap">
                        @if(optional($s->schedule)->started_at)
                            <span class="font-medium text-ink">{{ $s->schedule->started_at->format('d M Y') }}</span>
                            <span class="text-gray-500 text-xs block">{{ $s->schedule->started_at->format('H:i') }} WIB</span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-3">{{ $s->customer_display_name }}</td>
                    <td class="px-5 py-3">{{ $s->klinik->brand_name ?? '-' }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $badge($s->status) }}">{{ $label($s->status) }}</span>
                        @if($s->konsultan_done_at && !$s->completion_verified_at)
                            <span class="block text-[11px] text-terra-600 mt-1">Menunggu konfirmasi customer</span>
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        @if($s->status === 'scheduled')
                        <div class="flex flex-col gap-2 max-w-[240px]">
                            @if(!$s->konsultan_checked_in_at)
                            <form method="POST" action="{{ route('consultant.sessions.checkIn', $s) }}">@csrf
                                <button class="w-full rounded-lg border border-pine-300 text-pine-700 px-3 py-1.5 text-xs font-semibold hover:bg-pine-50 transition-colors">Check-in</button>
                            </form>
                            @endif
                            @if(!$s->konsultan_done_at)
                            <form method="POST" action="{{ route('consultant.sessions.markDone', $s) }}" class="space-y-1.5">@csrf
                                <textarea name="note" rows="2" placeholder="Catatan sesi (rahasia)" class="w-full border border-[#e7e2d9] rounded-lg px-2.5 py-1.5 text-xs focus:outline-none focus:border-pine-400"></textarea>
                                <textarea name="solution" rows="2" placeholder="Ringkasan untuk customer" class="w-full border border-[#e7e2d9] rounded-lg px-2.5 py-1.5 text-xs focus:outline-none focus:border-pine-400"></textarea>
                                <button class="rounded-lg bg-pine-700 text-white px-3 py-1.5 text-xs font-semibold hover:bg-pine-800 transition-colors">Tandai Selesai</button>
                            </form>
                            @endif
                            @if($s->link_meeting)
                                <a href="{{ $s->link_meeting }}" class="text-xs font-medium text-pine-700 hover:text-pine-900">Link Meet →</a>
                            @endif
                        </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-5 py-8 text-center text-gray-400">Belum ada sesi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<div class="mt-4">{{ $sessions->links() }}</div>

<script>
(() => {
    const btns = document.querySelectorAll('#session-filter .filter-btn');
    const rows = document.querySelectorAll('#session-rows tr[data-status]');
    btns.forEach(btn => btn.addEventListener('click', () => {
        const f = btn.dataset.filter;
        btns.forEach(b => {
            const active = b === btn;
            b.className = 'filter-btn rounded-full px-3 py-1 text-xs font-medium ' + (active ? 'bg-pine-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-pine-100');
        });
        rows.forEach(r => {
            r.style.display = (f === 'all' || r.dataset.status === f || (f === 'cancelled' && r.dataset.status === 'canceled')) ? '' : 'none';
        });
    }));
})();
</script>
@endsection
