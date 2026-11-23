@extends('layouts.app')

@section('title', $instrument->name . ' — ' . $sekolah->brand)

@section('content')
<div class="max-w-3xl">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-xl font-bold text-pine-900">{{ $instrument->name }}</h1>
            <p class="text-sm text-gray-500">{{ $items->count() }} pertanyaan. {{ $instrument->interpretation }}</p>
        </div>
        <a href="{{ $listUrl }}" class="text-sm text-pine-700 hover:underline">← Pilih instrumen lain</a>
    </div>

    @if($latest)
    <div class="portal-card p-4 mb-4 text-sm">
        <p class="text-gray-500 text-xs">Terakhir diisi {{ $latest->taken_at->translatedFormat('d M Y') }} — skor <b>{{ $latest->score }}</b> ({{ $instrument->bandLabelFor((int) $latest->score) }})</p>
    </div>
    @endif

    <form method="POST" action="{{ $action }}" class="space-y-4">
        @csrf
        @foreach($items as $i => $item)
        <div class="portal-card p-4">
            <p class="text-sm font-medium mb-3">{{ $i + 1 }}. {{ $item->text }}</p>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                @foreach($item->options ?? [] as $val => $label)
                <label class="flex items-center gap-2 text-xs border border-[#e7e2d9] rounded-xl px-3 py-2 cursor-pointer hover:border-pine-600 has-checked:border-pine-600 has-checked:bg-pine-50">
                    <input type="radio" name="answers[{{ $item->code }}]" value="{{ $val }}" required class="accent-pine-700">
                    <span class="leading-snug">{{ $label }}</span>
                </label>
                @endforeach
            </div>
            @error("answers.{$item->code}")<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        @endforeach

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-800">
            Ini skrining awal, <b>bukan diagnosis</b>. Jika skor rendah atau kamu sedang dalam kesulitan, hubungi konselor lewat fitur booking atau hotline krisis <b>119 ext. 8</b>.
        </div>

        <button type="submit" class="w-full bg-pine-700 text-white text-sm font-semibold rounded px-4 py-2.5 hover:bg-pine-800">Kirim Jawaban</button>
    </form>
</div>
@endsection
