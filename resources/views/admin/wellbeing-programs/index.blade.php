@extends('layouts.app')

@php
    $title = 'Program Wellbeing';
    $statusLabels = ['active' => 'Aktif', 'ended' => 'Berakhir', 'cancelled' => 'Dibatalkan'];
@endphp

@section('content')
<div class="max-w-6xl">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-xl font-bold">Program Wellbeing</h1>
            <p class="text-sm text-gray-500">Daftar kontrak program per perusahaan.</p>
        </div>
        <a href="{{ route('admin.wellbeing-programs.create') }}" class="rounded-xl bg-pine-700 px-4 py-2 text-sm font-semibold text-white hover:bg-pine-800">Assign Program</a>
    </div>

    <div class="dash-card p-5">
        @if($programs->isEmpty())
            <p class="text-sm text-gray-400">Belum ada program. Mulai dengan wizard Assign Program.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-400 border-b border-gray-100">
                            <th class="py-2 pr-4 font-medium">Perusahaan</th>
                            <th class="py-2 pr-4 font-medium">Paket</th>
                            <th class="py-2 pr-4 font-medium">Periode</th>
                            <th class="py-2 pr-4 font-medium">Karyawan</th>
                            <th class="py-2 pr-4 font-medium">Ringkasan kuota</th>
                            <th class="py-2 pr-4 font-medium">Status</th>
                            <th class="py-2 font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($programs as $p)
                        <tr class="border-b border-gray-50 last:border-0">
                            <td class="py-2.5 pr-4 text-gray-700">{{ $p->perusahaan?->brand ?? '—' }}{{ $p->perusahaan?->is_active ? '' : ' (nonaktif)' }}</td>
                            <td class="py-2.5 pr-4 text-gray-500">{{ $p->wellbeingPackage?->name ?? '—' }}</td>
                            <td class="py-2.5 pr-4 text-gray-500">{{ optional($p->starts_at)->format('d M y') }} – {{ optional($p->ends_at)->format('d M y') }}</td>
                            <td class="py-2.5 pr-4 text-gray-500">{{ $p->employees_count }}</td>
                            <td class="py-2.5 pr-4 text-xs text-gray-500">{{ $p->quota_summary }}</td>
                            <td class="py-2.5">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $p->status === 'active' ? 'bg-pine-100 text-pine-800' : 'bg-gray-100 text-gray-500' }}">{{ $statusLabels[$p->status] ?? $p->status }}</span>
                            </td>
                            <td class="py-2.5">
                                <a href="{{ route('admin.programs.assessments', $p) }}" class="text-xs text-emerald-700 font-semibold hover:underline">Assignment</a>
                                @if($p->status === 'active' && $p->notice_requested_at)
                                    <div class="mt-1 text-xs text-red-600 font-semibold">Terminasi efektif {{ optional($p->terminated_at)->format('d M Y') }}</div>
                                    <form method="POST" action="{{ route('admin.wellbeing-programs.cancel-termination', $p) }}" class="mt-1">
                                        @csrf
                                        <button type="submit" class="text-xs text-emerald-700 font-semibold hover:underline"
                                                onclick="return confirm('Batalkan pengajuan terminasi?')">Batalkan terminasi</button>
                                    </form>
                                @elseif($p->status === 'active')
                                    <form method="POST" action="{{ route('admin.wellbeing-programs.request-termination', $p) }}" class="mt-1 flex items-center gap-1">
                                        @csrf
                                        <input type="date" name="effective_date" required min="{{ now()->addDays(30)->toDateString() }}"
                                               value="{{ now()->addDays(30)->toDateString() }}" class="border rounded p-0.5 text-xs" aria-label="Tanggal efektif">
                                        <button type="submit" class="text-xs text-red-600 font-semibold hover:underline"
                                                onclick="return confirm('Ajukan terminasi 30-day notice?')">Terminasi</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $programs->links() }}
        @endif
    </div>
</div>
@endsection
