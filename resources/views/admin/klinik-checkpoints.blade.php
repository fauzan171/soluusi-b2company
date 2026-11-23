@extends('layouts.app')

@section('title', 'Checkpoint 40 Konsultasi — Admin')

@section('content')
<div class="flex items-baseline justify-between mb-2">
    <h1 class="text-xl font-bold">Checkpoint 40 Konsultasi</h1>
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-emerald-700 font-semibold hover:underline">← Dashboard</a>
</div>
<p class="text-sm text-gray-500 mb-6">
    Progress sesi konsultasi completed-verified per klinik menuju Checkpoint 40 (target awal kemitraan). Hitungan mengikuti definisi payout engine HAP.
</p>

<div class="bg-white border rounded-lg overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b text-left text-gray-500">
                <th class="p-3">Klinik</th>
                <th class="p-3">Konsultasi selesai</th>
                <th class="p-3 w-64">Progress ke 40</th>
                <th class="p-3">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($kliniks as $k)
                <tr class="border-b">
                    <td class="p-3 font-semibold">{{ $k->brand }}<span class="block text-xs font-normal text-gray-400">{{ $k->name }}</span></td>
                    <td class="p-3 font-semibold">{{ $k->completed }} / 40</td>
                    <td class="p-3">
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden" aria-hidden="true">
                            <div class="h-full rounded-full {{ $k->reached ? 'bg-emerald-600' : 'bg-terra-500' }}" style="width: {{ min($k->progress, 100) }}%"></div>
                        </div>
                        <span class="mt-1 block text-xs text-gray-400">{{ $k->progress }}%</span>
                    </td>
                    <td class="p-3">
                        @if($k->reached)
                            <span class="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 font-semibold">Checkpoint tercapai</span>
                        @else
                            <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-500">Berjalan</span>
                        @endif
                        @unless($k->is_active)
                            <span class="ml-1 text-xs px-2 py-0.5 rounded bg-amber-100 text-amber-700 font-semibold">Non-aktif</span>
                        @endunless
                    </td>
                </tr>
            @empty
                <tr><td class="p-3 text-gray-400" colspan="4">Belum ada klinik terdaftar.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
