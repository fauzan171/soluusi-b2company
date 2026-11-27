@extends('layouts.app')

@section('title', 'Katalog Konsultan — '.$perusahaan->brand)

@section('content')
<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-xl font-bold text-pine-900">Katalog Konsultan Soluusi</h1>
        <p class="text-sm text-gray-500 mt-1">Profil konsultan profesional yang tersedia untuk program wellbeing perusahaan Anda. Klik kartu untuk detail lengkap.</p>
    </div>
    <a href="{{ route('perusahaan.dashboard') }}" class="text-sm text-pine-700 font-medium hover:underline">&larr; Dashboard</a>
</div>

@if(session('success'))
<div class="portal-card p-3 mb-4 bg-pine-50 text-pine-800 text-sm" role="status">{{ session('success') }}</div>
@endif

{{-- Search & filter --}}
<form method="GET" action="{{ route('perusahaan.consultants.catalog') }}" class="mb-6">
    <div class="flex flex-wrap gap-3">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama konsultan..."
                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm bg-white transition focus:outline-none focus:ring-2 focus:ring-pine-500 focus:border-pine-500">
        </div>
        <div class="w-full sm:w-auto">
            <select name="category" class="w-full sm:w-auto rounded-lg border border-gray-300 px-3 py-2.5 text-sm bg-white transition focus:outline-none focus:ring-2 focus:ring-pine-500 focus:border-pine-500">
                <option value="">Semua kategori</option>
                @foreach($categories as $cat)
                <option value="{{ $cat }}" @selected($activeCategory === $cat)>{{ $cat }}</option>
                @endforeach
            </select>
        </div>
        @if($scopeAll)<input type="hidden" name="scope" value="all">@endif
        <button type="submit" class="rounded-lg bg-pine-700 text-white text-sm font-semibold px-4 py-2.5 transition hover:bg-pine-800 focus:outline-none focus:ring-2 focus:ring-pine-500 focus:ring-offset-2">Filter</button>
    </div>
    <p class="mt-2 text-sm">
        @if($scopeAll)
            Menampilkan semua konsultan Soluusi. <a href="{{ route('perusahaan.consultants.catalog') }}" class="text-pine-700 font-semibold hover:underline">Hanya pool perusahaan</a>
        @else
            Menampilkan konsultan di pool perusahaan. <a href="{{ route('perusahaan.consultants.catalog', ['scope' => 'all']) }}" class="text-pine-700 font-semibold hover:underline">Lihat semua konsultan</a>
        @endif
    </p>
</form>

@if($consultants->isEmpty())
<div class="portal-card p-8 text-center">
    <p class="text-gray-500 text-sm">Tidak ada konsultan yang cocok dengan pencarian kamu.</p>
    <a href="{{ route('perusahaan.consultants.catalog') }}" class="text-sm text-pine-700 font-medium hover:underline mt-2 inline-block">Reset filter</a>
</div>
@else
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
    @foreach($consultants as $c)
    <a href="{{ route('perusahaan.consultants.detail', $c) }}" class="portal-card p-5 flex flex-col transition hover:shadow-md hover:border-pine-300">
        <div class="flex items-start justify-between">
            @if($c->photo_url)
            <img src="{{ $c->photo_url }}" alt="" class="w-12 h-12 rounded-full object-cover bg-pine-100"
                onerror="this.replaceWith(Object.assign(document.createElement('div'),{className:'w-12 h-12 rounded-full bg-pine-100 flex items-center justify-center text-pine-700 font-bold text-lg',textContent:'{{ strtoupper(substr($c->user?->firstname ?? 'K', 0, 1)) }}'}))">
            @else
            <div class="w-12 h-12 rounded-full bg-pine-100 flex items-center justify-center text-pine-700 font-bold text-lg">
                {{ strtoupper(substr($c->user?->firstname ?? 'K', 0, 1)) }}
            </div>
            @endif
            @if($c->ratings_count > 0)
            <span class="text-xs font-semibold text-pine-700 bg-pine-50 rounded-full px-2.5 py-1">★ {{ number_format($c->ratings->avg('rating'), 1) }} ({{ $c->ratings_count }})</span>
            @endif
        </div>
        <h2 class="font-semibold text-pine-900 text-sm mt-3">{{ $c->user?->fullname ?? 'Konsultan' }}</h2>
        @if($c->headline)
        <p class="text-xs text-pine-700 font-medium mt-0.5">{{ $c->headline }}</p>
        @endif
        <p class="text-xs text-gray-500 mt-1">{{ ($c->profile['categories'] ?? []) ? collect($c->profile['categories'])->pluck('name')->implode(', ') : ($c->categories->pluck('name')->implode(', ') ?: 'Umum') }}</p>
        <p class="text-xs text-gray-400 mt-2 line-clamp-2">{{ Str::limit($c->about, 100) }}</p>
        <span class="mt-4 text-xs font-semibold text-pine-700">Lihat detail &rarr;</span>
    </a>
    @endforeach
</div>

<div class="mt-6">{{ $consultants->links() }}</div>
@endif

{{-- Riwayat request konsultan --}}
@if($requests->count())
<div class="portal-card p-5 mt-8">
    <h3 class="font-semibold text-pine-900 mb-3">Request konsultan Anda</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b text-left text-gray-500 text-xs">
                    <th class="p-2">Konsultan</th>
                    <th class="p-2">Catatan</th>
                    <th class="p-2">Status</th>
                    <th class="p-2">Tanggal</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($requests as $r)
                <tr>
                    <td class="p-2 font-medium">{{ $r->consultant?->user?->fullname ?? 'Konsultan #'.$r->consultant_id }}</td>
                    <td class="p-2 text-gray-500">{{ $r->note ?? '—' }}</td>
                    <td class="p-2">
                        @if($r->status === 'pending')
                        <span class="text-xs px-2 py-0.5 rounded bg-terra-100 text-terra-800">Menunggu review Soluusi</span>
                        @elseif($r->status === 'approved')
                        <span class="text-xs px-2 py-0.5 rounded bg-pine-100 text-pine-800">Disetujui</span>
                        @else
                        <span class="text-xs px-2 py-0.5 rounded bg-red-100 text-red-800">Ditolak</span>
                        @endif
                    </td>
                    <td class="p-2 text-gray-400 text-xs">{{ $r->created_at->translatedFormat('d M Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
