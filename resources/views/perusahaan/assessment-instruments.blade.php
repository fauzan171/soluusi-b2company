@extends('layouts.app')

@php($title = 'Asesmen Kesejahteraan')

@section('content')
<div class="max-w-3xl">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-xl font-bold">Asesmen Kesejahteraan</h1>
            <p class="text-sm text-gray-500">Kerjakan satu per satu, ~1-3 menit. Hasil hanya untuk kamu; HR melihat agregat anonim.</p>
        </div>
        <a href="{{ route('perusahaan.employee') }}" class="text-sm text-pine-700 hover:underline">← Kembali</a>
    </div>

    @if($assignments->isEmpty() && $grants->isEmpty())
    <div class="portal-card p-6 text-center">
        <p class="text-sm text-gray-500">Belum ada asesmen yang dibuka untuk Anda saat ini.</p>
    </div>
    @else
    <div class="space-y-3">
        @foreach($assignments as $a)
        <a href="{{ route('perusahaan.assessment.instrument.form', $a->instrument_code) }}" class="portal-card p-4 flex items-center justify-between transition hover:shadow-md hover:border-pine-300">
            <div>
                <p class="text-sm font-semibold text-gray-900">
                    {{ $a->card_name }}
                    @if($a->source === \App\Models\AssessmentAssignment::SOURCE_ADDON)
                        <span class="ml-1 align-middle text-xs px-2 py-0.5 rounded bg-amber-100 text-amber-700 font-semibold">Tambahan</span>
                    @endif
                </p>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $a->card_domain }}
                    @if($a->round > 1) · Putaran {{ $a->round }}@endif
                    @if($a->closes_at) · Tutup {{ $a->closes_at->translatedFormat('d M Y') }}@endif
                </p>
                <p class="text-xs mt-0.5 @if($a->card_done_at) text-pine-700 font-medium @else text-gray-400 @endif">
                    @if($a->card_done_at)
                        Selesai ({{ $a->card_done_at->translatedFormat('d M Y') }})
                    @else
                        Belum dikerjakan
                    @endif
                </p>
            </div>
            <span class="text-xs text-pine-700 font-semibold whitespace-nowrap">@if($a->card_done_at) Lihat → @else Kerjakan → @endif</span>
        </a>
        @endforeach

        @foreach($grants as $instrument)
        <a href="{{ route('perusahaan.assessment.instrument.form', $instrument->code) }}" class="portal-card p-4 flex items-center justify-between transition hover:shadow-md hover:border-pine-300">
            <div>
                <p class="text-sm font-semibold text-gray-900">{{ $instrument->name }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Ditugaskan khusus untuk Anda</p>
            </div>
            <span class="text-xs text-pine-700 font-semibold whitespace-nowrap">Kerjakan →</span>
        </a>
        @endforeach
    </div>
    @endif

    @if($history->isNotEmpty())
    <h2 class="text-sm font-semibold text-gray-900 mt-6 mb-2">Riwayat</h2>
    <div class="space-y-2">
        @foreach($history as $row)
        <a href="{{ route('perusahaan.assessment.result', $row) }}" class="portal-card p-3 flex items-center justify-between text-sm transition hover:shadow-md hover:border-pine-300">
            <div>
                <p class="font-medium text-gray-800">{{ $row->history_name }}</p>
                <p class="text-xs text-gray-500">{{ $row->history_band }}</p>
            </div>
            <span class="text-xs text-gray-400">{{ $row->taken_at->translatedFormat('d M Y') }}</span>
        </a>
        @endforeach
    </div>
    @endif

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-800 mt-4">
        Semua instrumen adalah skrining awal, <b>bukan diagnosis</b>. Jika skor rendah atau kamu sedang dalam kesulitan, hubungi konsultan lewat fitur booking atau hotline krisis <b>119 ext. 8</b>.
    </div>
</div>
@endsection
