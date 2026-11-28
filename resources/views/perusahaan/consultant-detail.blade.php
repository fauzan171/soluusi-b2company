@extends('layouts.app')

@section('title', ($consultant->user?->fullname ?? 'Konsultan').' — '.$perusahaan->brand)

@section('content')
<div class="max-w-4xl">
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <a href="{{ route('perusahaan.consultants.catalog') }}" class="text-sm text-pine-700 font-medium hover:underline">&larr; Katalog konsultan</a>
    </div>

    <div class="portal-card p-6">
        <div class="flex flex-wrap items-start gap-5">
            @if($consultant->photo_url)
            <img src="{{ $consultant->photo_url }}" alt="" class="w-20 h-20 rounded-2xl object-cover bg-pine-100 shrink-0"
                onerror="this.replaceWith(Object.assign(document.createElement('div'),{className:'w-20 h-20 rounded-2xl bg-pine-100 flex items-center justify-center text-pine-700 font-bold text-2xl shrink-0',textContent:'{{ strtoupper(substr($consultant->user?->firstname ?? 'K', 0, 1)) }}'}))">
            @else
            <div class="w-20 h-20 rounded-2xl bg-pine-100 flex items-center justify-center text-pine-700 font-bold text-2xl shrink-0">
                {{ strtoupper(substr($consultant->user?->firstname ?? 'K', 0, 1)) }}
            </div>
            @endif
            <div class="flex-1 min-w-0">
                <h1 class="text-xl font-bold text-pine-900">{{ $consultant->user?->fullname ?? 'Konsultan' }}</h1>
                @if($consultant->headline)
                <p class="text-sm text-pine-700 font-medium mt-1">{{ $consultant->headline }}</p>
                @endif
                <p class="text-sm text-gray-500 mt-1">{{ $consultant->categories->pluck('name')->implode(', ') ?: 'Umum' }}</p>
                @if($consultant->ratings_count > 0)
                <p class="mt-2 text-sm"><span class="font-semibold text-pine-700">★ {{ number_format($consultant->ratings->avg('rating'), 1) }}</span> <span class="text-gray-400">dari {{ $consultant->ratings_count }} review</span></p>
                @endif
            </div>
        </div>

        @if($consultant->about)
        <div class="mt-5">
            <p class="text-[11px] uppercase tracking-[0.16em] text-gray-400 mb-2">Tentang</p>
            <p class="text-sm leading-6 text-gray-700">{{ $consultant->about }}</p>
        </div>
        @endif

        @php($profile = $consultant->profile ?? [])
        @php($profileSkills = collect($profile['skills'] ?? []))
        @if($profileSkills->isNotEmpty() && $consultant->skills->isEmpty())
        <div class="mt-5">
            <p class="text-[11px] uppercase tracking-[0.16em] text-gray-400 mb-2">Keahlian</p>
            <div class="flex flex-wrap gap-2">
                @foreach($profileSkills as $skill)
                <span class="rounded-full bg-pine-50 text-pine-700 text-xs font-semibold px-3 py-1.5">{{ $skill }}</span>
                @endforeach
            </div>
        </div>
        @endif

        @if(!empty($profile['experience']))
        <div class="mt-5">
            <p class="text-[11px] uppercase tracking-[0.16em] text-gray-400 mb-2">Pengalaman</p>
            <ul class="space-y-2">
                @foreach($profile['experience'] as $exp)
                <li class="text-sm text-gray-700">
                    <span class="font-medium">{{ $exp['position'] }}</span> — <span>{{ $exp['company'] }}</span>
                    <span class="text-gray-400 text-xs"> ({{ substr((string) $exp['start'], 0, 4) }}–{{ $exp['end'] ? substr((string) $exp['end'], 0, 4) : 'sekarang' }})</span>
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        @if(!empty($profile['packages']))
        <div class="mt-5">
            <p class="text-[11px] uppercase tracking-[0.16em] text-gray-400 mb-2">Paket Konsultasi (B2C)</p>
            <ul class="space-y-2">
                @foreach($profile['packages'] as $pkg)
                <li class="text-sm text-gray-700">
                    <span class="font-medium">{{ $pkg['name'] }}</span>
                    <span class="text-gray-400 text-xs"> — {{ number_format($pkg['price'], 0, ',', '.') }} · {{ $pkg['duration'] }} menit{{ $pkg['quota'] ? ' · kuota '.$pkg['quota'] : '' }}</span>
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="mt-5 grid gap-4 sm:grid-cols-3">
            @if($consultant->skills->count())
            <div>
                <p class="text-[11px] uppercase tracking-[0.16em] text-gray-400 mb-2">Keahlian</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($consultant->skills as $skill)
                    <span class="rounded-full bg-pine-50 text-pine-700 text-xs font-semibold px-3 py-1.5">{{ $skill->name }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            @if($consultant->educations->count())
            <div>
                <p class="text-[11px] uppercase tracking-[0.16em] text-gray-400 mb-2">Pendidikan</p>
                <ul class="space-y-2">
                    @foreach($consultant->educations as $edu)
                    <li class="text-sm text-gray-700">
                        <span class="font-medium">{{ $edu->education?->name }}</span>
                        <span class="text-gray-400 text-xs"> — {{ $edu->degree }} ({{ substr((string) $edu->end_date, 0, 4) }})</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @elseif(!empty($profile['education']))
            <div>
                <p class="text-[11px] uppercase tracking-[0.16em] text-gray-400 mb-2">Pendidikan</p>
                <ul class="space-y-2">
                    @foreach($profile['education'] as $edu)
                    <li class="text-sm text-gray-700">
                        <span class="font-medium">{{ $edu['institution'] }}</span>
                        <span class="text-gray-400 text-xs"> — {{ $edu['degree'] }}{{ $edu['department'] ? ' · '.$edu['department'] : '' }} ({{ substr((string) $edu['end'], 0, 4) }})</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if($consultant->certificates->count())
            <div>
                <p class="text-[11px] uppercase tracking-[0.16em] text-gray-400 mb-2">Sertifikasi</p>
                <ul class="space-y-2">
                    @foreach($consultant->certificates as $cert)
                    <li class="text-sm text-gray-700">
                        <span class="font-medium">{{ $cert->name }}</span>
                        <span class="text-gray-400 text-xs"> ({{ $cert->year }})</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @elseif(!empty($profile['certificates']))
            <div>
                <p class="text-[11px] uppercase tracking-[0.16em] text-gray-400 mb-2">Sertifikasi</p>
                <ul class="space-y-2">
                    @foreach($profile['certificates'] as $cert)
                    <li class="text-sm text-gray-700">
                        <span class="font-medium">{{ $cert['name'] }}</span>
                        <span class="text-gray-400 text-xs"> ({{ $cert['year'] }})</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
    </div>

    {{-- Statistik sesi di perusahaan ini --}}
    <div class="grid gap-3 sm:grid-cols-4 mt-4">
        <div class="portal-card p-4 text-center">
            <p class="text-2xl font-bold text-pine-900">{{ $stats['done'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Sesi selesai di perusahaan Anda</p>
        </div>
        <div class="portal-card p-4 text-center">
            <p class="text-2xl font-bold text-pine-900">{{ $stats['scheduled'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Terjadwal</p>
        </div>
        <div class="portal-card p-4 text-center">
            <p class="text-2xl font-bold text-pine-900">{{ $stats['avgRating'] ? number_format($stats['avgRating'], 1) : '—' }}</p>
            <p class="text-xs text-gray-500 mt-1">Rating rata-rata sesi</p>
        </div>
        <div class="portal-card p-4 text-center">
            <p class="text-2xl font-bold text-pine-900">{{ $stats['rated'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Sesi diberi rating</p>
        </div>
    </div>

    {{-- Request ke pool --}}
    <div class="portal-card p-6 mt-4">
        <h3 class="font-semibold text-pine-900">Minta konsultan ini untuk program Anda</h3>
        <p class="text-sm text-gray-500 mt-1">Request diteruskan ke tim Soluusi untuk ditinjau dan diassign ke pool perusahaan Anda.</p>
        <form method="POST" action="{{ route('perusahaan.consultants.request', $consultant) }}" class="mt-4">
            @csrf
            <textarea name="note" rows="2" maxlength="500" placeholder="Catatan opsional — mis. kebutuhan khusus, jadwal, jumlah karyawan…"
                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm bg-white transition focus:outline-none focus:ring-2 focus:ring-pine-500 focus:border-pine-500"></textarea>
            <button type="submit" class="mt-3 rounded-lg bg-pine-700 text-white text-sm font-semibold px-5 py-2.5 transition hover:bg-pine-800 focus:outline-none focus:ring-2 focus:ring-pine-500 focus:ring-offset-2">
                Kirim request konsultan
            </button>
        </form>
    </div>
</div>
@endsection
