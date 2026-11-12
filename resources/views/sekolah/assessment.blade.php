@extends('layouts.app')

@section('title', 'Asesmen WHO-5 — ' . $sekolah->brand)

@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-xl font-bold text-pine-900">Asesmen Kesejahteraan (WHO-5)</h1>
    <p class="text-sm text-gray-500 mt-1 mb-6">5 pertanyaan tentang 2 minggu terakhir. Jawaban kamu rahasia — sekolah hanya melihat data agregat.</p>

    @if($latest)
    <div class="portal-card p-4 mb-4 text-sm">
        <p class="text-gray-500 text-xs">Terakhir diisi {{ $latest->taken_at->translatedFormat('d M Y') }} — skor <b>{{ $latest->score }}/100</b> ({{ $latest->band() }})</p>
    </div>
    @endif

    <form method="POST" action="{{ route('sekolah.assessment.store') }}" class="portal-card p-5 space-y-5">
        @csrf
        @foreach($items as $i => $item)
        <div>
            <p class="text-sm font-medium">{{ $i + 1 }}. {{ $item }}</p>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2 mt-2">
                @foreach($options as $val => $label)
                <label class="flex items-center gap-2 text-xs border border-[#e7e2d9] rounded-xl px-3 py-2 cursor-pointer has-checked:border-pine-600 has-checked:bg-pine-50">
                    <input type="radio" name="answers[{{ $i }}]" value="{{ $val }}" class="accent-pine-700" required>
                    {{ $label }}
                </label>
                @endforeach
            </div>
        </div>
        @endforeach
        <button class="w-full bg-pine-700 text-white text-sm font-semibold py-3 rounded-xl">Kirim Jawaban</button>
    </form>
</div>
@endsection
