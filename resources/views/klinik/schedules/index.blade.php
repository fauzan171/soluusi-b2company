@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold">Jadwal Konsultan</h1>
        <p class="text-sm text-gray-500">Jadwal yang diatur klinik untuk konsultan.</p>
    </div>
    <a href="{{ route('klinik.consultants.index') }}" class="bg-emerald-700 text-white rounded px-4 py-2 text-sm font-semibold">Pilih Konsultan</a>
</div>

<table class="w-full bg-white border rounded-lg text-sm">
    <thead>
        <tr class="border-b text-left text-gray-500">
            <th class="p-3">Konsultan</th>
            <th class="p-3">Mode</th>
            <th class="p-3">Jadwal</th>
            <th class="p-3">Lokasi / Link</th>
            <th class="p-3">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($schedules as $schedule)
            <tr class="border-b align-top">
                <td class="p-3">{{ trim(($schedule->consultant->user->firstname ?? '') . ' ' . ($schedule->consultant->user->lastname ?? '')) }}</td>
                <td class="p-3">
                    <span class="text-xs font-semibold rounded px-2 py-1 {{ ($schedule->mode ?? 'online') === 'offline' ? 'bg-amber-100 text-amber-800' : 'bg-sky-100 text-sky-800' }}">
                        {{ strtoupper($schedule->mode ?? 'online') }}
                    </span>
                </td>
                <td class="p-3">{{ $schedule->started_at->format('d M Y H:i') }} - {{ $schedule->ended_at->format('H:i') }}</td>
                <td class="p-3 text-xs text-gray-500">
                    @if(($schedule->mode ?? 'online') === 'offline')
                        {{ $schedule->location ?: '-' }}
                    @else
                        {{ $schedule->meeting_url ?: '-' }}
                    @endif
                </td>
                <td class="p-3">{{ ucfirst($schedule->status ?? 'confirmed') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="p-3 text-gray-400">Belum ada jadwal. Pilih konsultan lalu atur slot pertama.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="mt-4">{{ $schedules->links() }}</div>
@endsection
