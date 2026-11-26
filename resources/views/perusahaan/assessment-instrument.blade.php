@extends('layouts.app')

@php($title = $instrument->name)

@section('content')
<div class="max-w-3xl">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-xl font-bold">{{ $instrument->name }}</h1>
            <p class="text-sm text-gray-500">{{ $items->count() }} pertanyaan. {{ $instrument->interpretation }}</p>
        </div>
        <a href="{{ route('perusahaan.assessment.instruments') }}" class="text-sm text-pine-700 hover:underline">← Pilih instrumen lain</a>
    </div>

    <form method="POST" action="{{ route('perusahaan.assessment.instrument.store', $instrument->code) }}" class="space-y-4">
        @csrf
        @foreach($items as $i => $item)
        <div class="portal-card p-4">
            @if($item->type === 'forced-choice')
            <p class="text-sm font-medium mb-3">{{ $i + 1 }}. Pilih pernyataan yang paling cocok dan paling tidak cocok dengan Anda.</p>
            @foreach(['p' => 'Paling cocok', 'k' => 'Paling tidak cocok'] as $suffix => $groupLabel)
            <p class="text-xs font-semibold text-gray-500 mt-2 mb-1">{{ $groupLabel }}</p>
            <div class="grid sm:grid-cols-2 gap-2">
                @foreach($item->options ?? [] as $val => $label)
                <label class="flex items-center gap-2 text-sm border-[#e7e2d9] rounded-lg rounded px-3 py-2 cursor-pointer hover:border-pine-600 has-checked:border-pine-600 has-checked:bg-pine-50">
                    <input type="radio" name="answers[{{ $item->code.$suffix }}]" value="{{ $val }}" required class="accent-pine-700">
                    <span class="leading-snug">{{ $label }}</span>
                </label>
                @endforeach
            </div>
            @endforeach
            @error("answers.{$item->code}p")<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            @error("answers.{$item->code}k")<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            @else
            <p class="text-sm font-medium mb-3">{{ $i + 1 }}. {{ $item->text }}</p>
            <div class="grid sm:grid-cols-2 gap-2">
                @foreach($item->options ?? [] as $val => $label)
                <label class="flex items-center gap-2 text-sm border-[#e7e2d9] rounded-lg rounded px-3 py-2 cursor-pointer hover:border-pine-600 has-checked:border-pine-600 has-checked:bg-pine-50">
                    <input type="radio" name="answers[{{ $item->code }}]" value="{{ $val }}" required class="accent-pine-700">
                    <span class="leading-snug">{{ $label }}</span>
                </label>
                @endforeach
            </div>
            @error("answers.{$item->code}")<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            @endif
        </div>
        @endforeach

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-800">
            Ini skrining awal, <b>bukan diagnosis</b>. Jika skor rendah atau kamu sedang dalam kesulitan, hubungi konsultan lewat fitur booking atau hotline krisis <b>119 ext. 8</b>.
        </div>

        <button type="submit" class="w-full bg-pine-700 text-white text-sm font-semibold rounded px-4 py-2.5 hover:bg-pine-800">Simpan Hasil</button>
    </form>
</div>
@endsection
