@extends('layouts.app')

@section('title', 'Webinar Program — Admin')

@section('content')
<div class="flex items-baseline justify-between mb-2">
    <h1 class="text-xl font-bold">Webinar — {{ $program->wellbeingPackage->name }} / {{ $program->perusahaan->brand }}</h1>
    <a href="{{ route('admin.wellbeing-programs.create') }}" class="text-sm text-emerald-700 font-semibold hover:underline">← Assign Program</a>
</div>
<p class="text-sm text-gray-500 mb-4">
    Kontrak {{ $program->starts_at->format('d M y') }}–{{ $program->ends_at->format('d M y') }} —
    kuota webinar {{ $webinars->where('status', 'done')->count() }} / {{ $program->webinar_quota }} terpakai.
    Mark done = 1 kuota terpakai (no-show dicatat sebagai forfeited).
</p>

@if(session('success'))
<div class="mb-4 rounded border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-2 text-sm text-red-800">{{ session('error') }}</div>
@endif
@if($errors->any())
<div class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-2 text-sm text-red-800">
    @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
</div>
@endif

{{-- Form jadwalkan webinar baru --}}
<form method="POST" action="{{ route('admin.wellbeing-webinars.store', $program) }}"
      class="bg-white border rounded-lg p-5 mb-8 max-w-3xl">
    @csrf
    <h2 class="font-semibold text-gray-700 mb-3">Jadwalkan Webinar</h2>
    <div class="grid sm:grid-cols-2 gap-4 mb-4">
        <label class="text-sm">Judul
            <input type="text" name="title" required value="{{ old('title') }}" class="w-full border rounded p-2 mt-1">
        </label>
        <label class="text-sm">Jadwal
            <input type="datetime-local" name="scheduled_at" required value="{{ old('scheduled_at') }}" class="w-full border rounded p-2 mt-1">
        </label>
        <label class="text-sm">Pembicara (opsional)
            <select name="consultant_id" class="w-full border rounded p-2 mt-1 bg-white">
                <option value="">— tanpa pembicara konsultan —</option>
                @foreach($consultants as $c)
                <option value="{{ $c->id }}" @selected(old('consultant_id') == $c->id)>{{ $c->user?->fullname ?? 'Konsultan #'.$c->id }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm">Catatan
            <input type="text" name="notes" value="{{ old('notes') }}" class="w-full border rounded p-2 mt-1">
        </label>
    </div>
    <button type="submit" class="bg-emerald-600 text-white font-semibold rounded py-2 px-4 text-sm hover:bg-emerald-700">Jadwalkan</button>
</form>

{{-- Daftar webinar --}}
<div class="flex items-baseline justify-between mb-2">
    <h2 class="font-semibold">Daftar Webinar</h2>
</div>
<div class="overflow-x-auto bg-white border rounded-lg mb-8"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Judul</th>
        <th class="p-3">Jadwal</th>
        <th class="p-3">Pembicara</th>
        <th class="p-3">Status</th>
        <th class="p-3">Catatan</th>
        <th class="p-3">Aksi</th>
    </tr></thead>
    <tbody>
        @forelse($webinars as $w)
        <tr class="border-b">
            <td class="p-3 font-semibold">{{ $w->title }}</td>
            <td class="p-3">{{ $w->scheduled_at->format('d M Y H:i') }}</td>
            <td class="p-3">{{ $w->consultant?->user?->fullname ?? '—' }}</td>
            <td class="p-3">
                @if($w->status === 'planned')
                <span class="text-xs px-2 py-0.5 rounded bg-amber-100 text-amber-700 font-semibold">Planned</span>
                @elseif($w->status === 'done')
                <span class="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 font-semibold">Done</span>
                @else
                <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-500">Cancelled</span>
                @endif
            </td>
            <td class="p-3 text-gray-500">{{ $w->notes ?? '—' }}</td>
            <td class="p-3">
                @if($w->status === 'planned')
                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('admin.wellbeing-webinars.mark-done', [$program, $w]) }}"
                          onsubmit="return confirm('Tandai selesai? 1 kuota webinar terpakai.')">
                        @csrf
                        <button type="submit" class="text-xs text-emerald-700 font-semibold hover:underline">Mark Done</button>
                    </form>
                    <form method="POST" action="{{ route('admin.wellbeing-webinars.cancel', [$program, $w]) }}"
                          onsubmit="return confirm('Batalkan webinar? Tidak mengonsumsi kuota.')">
                        @csrf
                        <button type="submit" class="text-xs text-terra-700 font-semibold hover:underline">Cancel</button>
                    </form>
                    <form method="POST" action="{{ route('admin.wellbeing-webinars.forfeit', $program) }}"
                          onsubmit="return confirm('Catat no-show? Kuota webinar terhitung terpakai.')">
                        @csrf
                        <input type="hidden" name="webinar_id" value="{{ $w->id }}">
                        <input type="hidden" name="reason" value="peserta tidak hadir">
                        <button type="submit" class="text-xs text-red-700 font-semibold hover:underline">No-Show</button>
                    </form>
                </div>
                @else
                <span class="text-xs text-gray-400">—</span>
                @endif
            </td>
        </tr>
        @empty
        <tr><td class="p-3 text-gray-400" colspan="6">Belum ada webinar dijadwalkan.</td></tr>
        @endforelse
    </tbody>
</table></div>
@endsection
