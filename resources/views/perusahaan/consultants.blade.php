@extends('layouts.app')

@section('content')
<div class="max-w-4xl">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <h1 class="text-xl font-bold text-pine-900">Pilih Konsultan</h1>
            <p class="text-sm text-gray-500 mt-1">Chat langsung tanpa menunggu jadwal.</p>
        </div>
        <a href="{{ route('perusahaan.employee') }}" class="text-sm text-pine-700 font-medium hover:underline">&larr; Kembali</a>
    </div>

    {{-- Search & filter --}}
    <form method="GET" action="{{ route('perusahaan.consultants') }}" class="mb-6">
        <div class="flex flex-wrap gap-3">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama konsultan..."
                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm bg-white transition focus:outline-none focus:ring-2 focus:ring-pine-500 focus:border-pine-500">
            </div>
            <div class="w-full sm:w-auto">
                <select name="category"
                    class="w-full sm:w-auto rounded-lg border border-gray-300 px-3 py-2.5 text-sm bg-white transition focus:outline-none focus:ring-2 focus:ring-pine-500 focus:border-pine-500">
                    <option value="">Semua kategori</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat }}" @selected($activeCategory === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit"
                class="rounded-lg bg-pine-700 text-white text-sm font-semibold px-4 py-2.5 transition hover:bg-pine-800 focus:outline-none focus:ring-2 focus:ring-pine-500 focus:ring-offset-2">
                Filter
            </button>
        </div>
    </form>

    @if($consultants->isEmpty())
    <div class="portal-card p-8 text-center">
        <p class="text-gray-500 text-sm">Tidak ada konsultan yang cocok dengan pencarian kamu.</p>
        <a href="{{ route('perusahaan.consultants') }}" class="text-sm text-pine-700 font-medium hover:underline mt-2 inline-block">Reset filter</a>
    </div>
    @else
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($consultants as $c)
        <div class="portal-card p-5 flex flex-col">
            <div class="flex-1">
                <div class="w-12 h-12 rounded-full bg-pine-100 flex items-center justify-center text-pine-700 font-bold text-lg mb-3">
                    {{ strtoupper(substr($c['name'], 0, 1)) }}
                </div>
                <h2 class="font-semibold text-pine-900 text-sm">{{ $c['name'] }}</h2>
                <p class="text-xs text-gray-500 mt-1">{{ $c['category'] ?: 'Umum' }}</p>
            </div>
            <form method="POST" action="{{ route('perusahaan.consultants.start-chat', $c['id']) }}" class="mt-4">
                @csrf
                <button type="submit"
                    class="w-full rounded-lg bg-pine-700 text-white text-sm font-semibold px-4 py-2.5 transition hover:bg-pine-800 focus:outline-none focus:ring-2 focus:ring-pine-500 focus:ring-offset-2">
                    Mulai Chat
                </button>
            </form>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
