@extends('layouts.app')

@section('title', 'Asesmen — ' . $sekolah->brand)

@section('content')
<div class="max-w-3xl">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-xl font-bold text-pine-900">Asesmen Kesejahteraan</h1>
            <p class="text-sm text-gray-500">Instrumen yang di-assign sekolahmu — kerjakan satu per satu, ~1-3 menit. Hasil hanya untuk kamu; sekolah melihat data agregat.</p>
        </div>
        <a href="{{ $backUrl }}" class="text-sm text-pine-700 hover:underline">← Kembali</a>
    </div>

    <div class="space-y-3">
        @forelse($instruments as $instrument)
        <a href="{{ $formRoute($instrument->code) }}" class="portal-card p-4 flex items-center justify-between transition hover:shadow-md hover:border-pine-300 {{ $instrument->done ? 'opacity-60' : '' }}">
            <div>
                <p class="text-sm font-semibold text-gray-900">{{ $instrument->name }}</p>
                <p class="text-xs text-gray-500 mt-0.5">
                    @if($instrument->latest_score !== null)
                        Terakhir: <span class="font-semibold text-gray-700">{{ $instrument->latest_score }}</span> ({{ $instrument->latest_band }}) · {{ $instrument->latest_taken->translatedFormat('d M Y') }}
                    @else
                        Belum pernah dikerjakan
                    @endif
                </p>
            </div>
            <span class="text-xs text-pine-700 font-semibold whitespace-nowrap">{{ $instrument->done ? 'Selesai ✓' : 'Kerjakan →' }}</span>
        </a>
        @empty
        <div class="portal-card p-6 text-center text-sm text-gray-500">Belum ada asesmen yang dibuka untuk kamu saat ini.</div>
        @endforelse
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-800 mt-4">
        Semua instrumen adalah skrining awal, <b>bukan diagnosis</b>. Jika skor rendah atau kamu sedang dalam kesulitan, hubungi konselor lewat fitur booking atau hotline krisis <b>119 ext. 8</b>.
    </div>
</div>
@endsection
