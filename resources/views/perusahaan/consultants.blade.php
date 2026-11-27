@extends('layouts.app')

@php($title = 'Pilih Konsultan')

@section('content')
<div class="max-w-4xl">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <h1 class="text-xl font-bold text-pine-900">Pilih Konsultan</h1>
            <p class="text-sm text-gray-500 mt-1">Lihat jadwal konsultan dan booking slot konsultasi.</p>
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
                @if($c['photo_url'])
                <img src="{{ $c['photo_url'] }}" alt="Foto {{ $c['name'] }}" class="w-12 h-12 rounded-full object-cover bg-pine-100 mb-3"
                    onerror="this.replaceWith(Object.assign(document.createElement('div'),{className:'w-12 h-12 rounded-full bg-pine-100 flex items-center justify-center text-pine-700 font-bold text-lg mb-3',textContent:'{{ strtoupper(substr($c['name'], 0, 1)) }}'}))">
                @else
                <div class="w-12 h-12 rounded-full bg-pine-100 flex items-center justify-center text-pine-700 font-bold text-lg mb-3">
                    {{ strtoupper(substr($c['name'], 0, 1)) }}
                </div>
                @endif
                <h2 class="font-semibold text-pine-900 text-sm">{{ $c['name'] }}</h2>
                @if($c['headline'])
                <p class="text-xs text-pine-700 font-medium mt-0.5">{{ $c['headline'] }}</p>
                @endif
                <p class="text-xs text-gray-500 mt-1">{{ $c['category'] ?: 'Umum' }}</p>
                @if(!empty($c['profile']['education']))
                <p class="text-xs text-gray-500 mt-1">{{ collect($c['profile']['education'])->pluck('institution')->implode(', ') }}</p>
                @endif
                @if(!empty($c['profile']['skills']))
                <p class="text-xs text-gray-400 mt-1">{{ collect($c['profile']['skills'])->implode(', ') }}</p>
                @endif
                @if(!empty($c['profile']['certificates']))
                <p class="text-xs text-gray-400 mt-1">Sertifikasi: {{ collect($c['profile']['certificates'])->pluck('name')->implode(', ') }}</p>
                @endif
            </div>
            <div class="mt-4 flex flex-col gap-2">
                <a href="{{ route('perusahaan.consultants.show', $c['id']) }}" class="block w-full text-center rounded-lg bg-pine-700 text-white text-sm font-semibold px-4 py-2.5 transition hover:bg-pine-800 focus:outline-none focus:ring-2 focus:ring-pine-500 focus:ring-offset-2">
                    Lihat Profil &amp; Jadwal
                </a>
                <a href="{{ route('perusahaan.booking', ['consultant_id' => $c['id']]) }}" class="block w-full text-center rounded-lg border border-pine-300 text-pine-800 text-sm font-semibold px-4 py-2.5 transition hover:bg-pine-50">
                    Booking
                </a>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
