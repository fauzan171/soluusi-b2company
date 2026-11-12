@extends('layouts.app')

@php($title = 'Asesmen Kesejahteraan')

@section('content')
<div class="max-w-3xl">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-xl font-bold">Asesmen Kesejahteraan (WHO-5)</h1>
            <p class="text-sm text-gray-500">5 pertanyaan, ~1 menit. Hasil hanya untuk kamu — HR melihat agregat anonim saja.</p>
        </div>
        <a href="{{ route('perusahaan.employee') }}" class="text-sm text-pine-700 hover:underline">← Kembali</a>
    </div>

    @if($latest)
    <div class="portal-card p-4 mb-4 flex items-center justify-between">
        <div class="text-sm text-gray-500">Hasil terakhir: <span class="font-semibold text-gray-900">{{ $latest->score }}/100</span> ({{ $latest->band() }}) — {{ $latest->taken_at->translatedFormat('d M Y') }}</div>
        <a href="{{ route('perusahaan.employee') }}" class="text-xs text-pine-700 font-semibold hover:underline">Lihat tren</a>
    </div>
    @endif

    <form method="POST" action="{{ route('perusahaan.assessment.store') }}" class="space-y-4">
        @csrf
        @foreach($items as $i => $item)
        <div class="portal-card p-4">
            <p class="text-sm font-medium mb-3">{{ $i + 1 }}. {{ $item }}</p>
            <div class="grid sm:grid-cols-2 gap-2">
                @foreach($options as $val => $label)
                <label class="flex items-center gap-2 text-sm border-[#e7e2d9] rounded-lg rounded px-3 py-2 cursor-pointer hover:border-pine-600 has-checked:border-pine-600 has-checked:bg-pine-50">
                    <input type="radio" name="answers[{{ $i }}]" value="{{ $val }}" required class="accent-pine-700">
                    {{ $label }}
                </label>
                @endforeach
            </div>
            @error("answers.$i")<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        @endforeach

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-800">
            Ini skrining awal kesejahteraan, <b>bukan diagnosis</b>. Jika skor rendah atau kamu sedang dalam kesulitan, hubungi konsultan lewat fitur booking atau hotline krisis <b>119 ext. 8</b>.
        </div>

        <button type="submit" class="w-full bg-pine-700 text-white text-sm font-semibold rounded px-4 py-2.5 hover:bg-pine-800">Simpan Hasil</button>
    </form>
</div>
@endsection
