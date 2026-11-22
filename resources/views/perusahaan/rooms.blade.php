@extends('layouts.app')

@php($title = 'Ruang 1-on-1')

@section('content')
<div class="max-w-3xl">
    <div class="mb-5">
        <h1 class="text-xl font-bold text-pine-900">Ruang Konsultasi 1-on-1</h1>
        <p class="text-sm text-gray-500 mt-1">Kelola ruang fisik untuk sesi tatap muka. Ruang aktif otomatis di-assign saat karyawan memilih tipe tatap muka, dengan cek anti-konflik jadwal.</p>
    </div>

    @if(session('success'))
    <div class="bg-pine-50 border border-pine-200 rounded-xl p-3 mb-4 text-sm text-pine-800" role="status">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('perusahaan.rooms.store') }}" class="portal-card p-5 mb-4 flex items-end gap-3">
        @csrf
        <div class="flex-1">
            <label for="name" class="block text-sm font-semibold text-pine-900 mb-1.5">Nama ruang <span class="text-terra-600">*</span></label>
            <input type="text" id="name" name="name" required maxlength="100" value="{{ old('name') }}"
                class="w-full rounded-lg border px-3 py-2.5 text-sm bg-white {{ $errors->has('name') ? 'border-terra-400' : 'border-gray-300 focus:border-pine-500 focus:ring-pine-500' }} focus:outline-none focus:ring-2">
            @error('name')
            <p class="text-xs text-terra-600 mt-1.5 font-medium" role="alert">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="rounded-lg bg-pine-700 text-white text-sm font-semibold px-4 py-2.5 hover:bg-pine-800 transition-colors">Tambah</button>
    </form>

    <div class="portal-card overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-[#e7e2d9] text-left text-xs text-gray-500 uppercase tracking-wide">
                    <th class="px-5 py-3 font-semibold">Ruang</th>
                    <th class="px-5 py-3 font-semibold">Status</th>
                    <th class="px-5 py-3 font-semibold text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rooms as $room)
                <tr class="border-b border-[#f0ede6]">
                    <td class="px-5 py-3 text-ink">{{ $room->name }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-medium {{ $room->status === 'active' ? 'bg-pine-100 text-pine-800' : 'bg-gray-100 text-gray-500' }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $room->status === 'active' ? 'bg-pine-600' : 'bg-gray-400' }}"></span>
                            {{ $room->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <form method="POST" action="{{ route('perusahaan.rooms.toggle', $room) }}">
                            @csrf
                            <button type="submit" class="text-sm font-medium text-pine-700 hover:underline">
                                {{ $room->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-5 py-6 text-center text-gray-400">Belum ada ruang. Tambahkan di form atas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
