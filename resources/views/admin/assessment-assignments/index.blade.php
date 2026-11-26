@extends('layouts.app')

@section('title', 'Assessment Program — '.$program->perusahaan->brand)

@section('content')
<div class="max-w-6xl">
    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="text-xl font-bold">Assignment Instrumen — {{ $program->perusahaan->brand }}</h1>
            <p class="text-sm text-gray-500">
                Program {{ $program->wellbeingPackage?->name }} ·
                {{ $program->starts_at->translatedFormat('d M Y') }} s.d. {{ $program->ends_at->translatedFormat('d M Y') }}
            </p>
        </div>
        <a href="{{ route('admin.wellbeing-programs.index') }}" class="text-sm text-emerald-700 font-semibold hover:underline">← Daftar Program</a>
    </div>


    {{-- Tabel assignment --}}
    <div class="dash-card p-5 mb-6">
        <h2 class="font-semibold mb-3">Assignment Berjalan</h2>
        @if($assignments->isEmpty())
            <p class="text-sm text-gray-400">Belum ada assignment untuk program ini.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-400 border-b border-gray-100">
                            <th class="py-2 pr-4 font-medium">Instrumen</th>
                            <th class="py-2 pr-4 font-medium">Sumber</th>
                            <th class="py-2 pr-4 font-medium">Round</th>
                            <th class="py-2 pr-4 font-medium">Window</th>
                            <th class="py-2 pr-4 font-medium">Harga add-on</th>
                            <th class="py-2 pr-4 font-medium">Penyelesai</th>
                            <th class="py-2 pr-4 font-medium">Status</th>
                            <th class="py-2 font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignments as $a)
                        <tr class="border-b border-gray-50 last:border-0">
                            <td class="py-2.5 pr-4 font-mono text-xs text-gray-700">{{ $a->instrument_code }}</td>
                            <td class="py-2.5 pr-4">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $a->source === 'core' ? 'bg-pine-100 text-pine-800' : 'bg-amber-100 text-amber-800' }}">{{ ucfirst($a->source) }}</span>
                            </td>
                            <td class="py-2.5 pr-4 text-gray-500">R{{ $a->round }}</td>
                            <td class="py-2.5 pr-4 text-gray-500 text-xs">{{ $a->opens_at->format('d M y') }} – {{ $a->closes_at?->format('d M y') ?? '—' }}</td>
                            <td class="py-2.5 pr-4 text-gray-500">{{ $a->addon_price !== null ? 'Rp ' . number_format((float) $a->addon_price, 0, ',', '.') : '—' }}</td>
                            <td class="py-2.5 pr-4 text-gray-500">{{ $a->completers_count }}</td>
                            <td class="py-2.5 pr-4">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $a->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">{{ $a->status === 'active' ? 'Aktif' : 'Ditutup' }}</span>
                            </td>
                            <td class="py-2.5">
                                @if($a->status === 'active')
                                    <form method="POST" action="{{ route('admin.programs.assessments.close', [$program, $a]) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-xs text-terra-700 font-semibold hover:underline"
                                                onclick="return confirm('Tutup assignment {{ $a->instrument_code }} R{{ $a->round }}? Karyawan tidak bisa mengisi lagi.')">
                                            Tutup
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Tambah assignment / ubah window --}}
    <div class="grid md:grid-cols-2 gap-6">
        <div class="dash-card p-5">
            <h2 class="font-semibold mb-3">Tambah Assignment</h2>
            <p class="text-xs text-gray-400 mb-3">Add-on berbayar atau round tambahan instrumen yang sudah ada. MCMI-III tidak bisa di-assign massal.</p>
            <form method="POST" action="{{ route('admin.programs.assessments.store', $program) }}" class="space-y-3">
                @csrf
                <label class="text-sm block">Instrumen
                    <select name="instrument_code" required class="w-full border rounded p-2 mt-1 bg-white text-sm">
                        @foreach($addonCodes as $code)
                            <option value="{{ $code }}">{{ $code }} (add-on)</option>
                        @endforeach
                        @foreach($assignments->pluck('instrument_code')->unique() as $code)
                            <option value="{{ $code }}">{{ $code }} (round tambahan)</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm block">Sumber
                    <select name="source" required class="w-full border rounded p-2 mt-1 bg-white text-sm">
                        <option value="addon">Add-on (berbayar)</option>
                        <option value="core">Core (round tambahan)</option>
                    </select>
                </label>
                <label class="text-sm block">Harga add-on (Rp, 0 = core)
                    <input type="number" name="addon_price" min="0" max="999999999" step="1" value="0"
                           class="w-full border rounded p-2 mt-1 text-sm">
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="text-sm">Round
                        <input type="number" name="round" required min="1" max="12" value="{{ $assignments->max('round') + 1 }}"
                               class="w-full border rounded p-2 mt-1 text-sm">
                    </label>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <label class="text-sm">Buka
                        <input type="date" name="opens_at" required value="{{ $program->starts_at->toDateString() }}"
                               min="{{ $program->starts_at->toDateString() }}" max="{{ $program->ends_at->toDateString() }}"
                               class="w-full border rounded p-2 mt-1 text-sm">
                    </label>
                    <label class="text-sm">Tutup
                        <input type="date" name="closes_at" required value="{{ $program->ends_at->toDateString() }}"
                               min="{{ $program->starts_at->toDateString() }}" max="{{ $program->ends_at->toDateString() }}"
                               class="w-full border rounded p-2 mt-1 text-sm">
                    </label>
                </div>
                <button type="submit" class="bg-pine-700 text-white text-sm font-semibold rounded px-4 py-2 hover:bg-pine-800">Tambah Assignment</button>
            </form>
        </div>

        <div class="dash-card p-5">
            <h2 class="font-semibold mb-3">Ubah Window</h2>
            <p class="text-xs text-gray-400 mb-3">Window harus di dalam periode program ({{ $program->starts_at->translatedFormat('d M Y') }} s.d. {{ $program->ends_at->translatedFormat('d M Y') }}).</p>
            @forelse($assignments->where('status', 'active') as $a)
                <form method="POST" action="{{ route('admin.programs.assessments.update', [$program, $a]) }}" class="flex items-end gap-2 mb-3 flex-wrap">
                    @csrf
                    @method('PUT')
                    <span class="text-xs font-mono text-gray-500 w-24">{{ $a->instrument_code }} R{{ $a->round }}</span>
                    <input type="date" name="opens_at" required value="{{ $a->opens_at->toDateString() }}"
                           min="{{ $program->starts_at->toDateString() }}" max="{{ $program->ends_at->toDateString() }}"
                           class="border rounded p-1.5 text-xs">
                    <input type="date" name="closes_at" required value="{{ $a->closes_at?->toDateString() }}"
                           min="{{ $program->starts_at->toDateString() }}" max="{{ $program->ends_at->toDateString() }}"
                           class="border rounded p-1.5 text-xs">
                    <button type="submit" class="text-xs bg-emerald-700 text-white rounded px-3 py-1.5 font-semibold">Simpan</button>
                </form>
            @empty
                <p class="text-sm text-gray-400">Tidak ada assignment aktif.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
