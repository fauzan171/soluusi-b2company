@extends('layouts.app')

@section('content')
<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Katalog layanan klinik</p>
        <h1 class="mt-1 text-2xl font-bold text-gray-900">Pilih Konsultan</h1>
        <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-500">
            Pilih dari katalog konsultan aktif untuk {{ $klinik->name }}. Lihat kategori, mode layanan, harga mulai, dan slot yang bisa langsung dibuat checkout link.
            @if($defaultRegency)
                <a href="{{ route('klinik.consultants.index', ['semua_daerah' => 1]) }}" class="font-semibold text-emerald-700">Lihat semua daerah</a>
            @endif
        </p>
    </div>
    @if($consultants->total() > 0)
        <div class="rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
            <span class="font-bold">{{ $consultants->total() }}</span> konsultan tersedia
        </div>
    @endif
</div>

{{-- Filters --}}
<form method="GET" class="mb-6 rounded-lg border border-[#e7e2d9] bg-white p-4 shadow-sm">
    <div class="grid gap-3 lg:grid-cols-[minmax(220px,1fr),190px,170px,190px,190px,auto] lg:items-end">
        <div>
            <label class="mb-1.5 block text-xs font-semibold text-gray-500">Cari kebutuhan</label>
            <input name="q" value="{{ request('q') }}" placeholder="Contoh: anxiety, anak, gizi, keluarga" class="w-full rounded-lg border border-[#ddd8cf] px-3 py-2.5 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-semibold text-gray-500">Kategori</label>
            <select name="category_id" class="w-full rounded-lg border border-[#ddd8cf] px-3 py-2.5 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                <option value="">Semua</option>
                @foreach($categories as $c)
                <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-semibold text-gray-500">Mode</label>
            <select name="mode" class="w-full rounded-lg border border-[#ddd8cf] px-3 py-2.5 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                <option value="">Semua</option>
                <option value="online" @selected(request('mode') === 'online')>Online</option>
                <option value="offline" @selected(request('mode') === 'offline')>Tatap muka</option>
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-semibold text-gray-500">Provinsi</label>
            <select name="province_id" class="w-full rounded-lg border border-[#ddd8cf] px-3 py-2.5 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100" onchange="this.form.submit()">
                <option value="">Semua</option>
                @foreach($provinces as $p)
                <option value="{{ $p->id }}" @selected(request('province_id') == $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-semibold text-gray-500">Kota/Kabupaten @if($klinik->regency_id)<a href="{{ route('klinik.consultants.index', array_merge(request()->query(), ['regency_id' => $klinik->regency_id])) }}" class="text-emerald-700">(daerah klinik)</a>@endif</label>
            <select name="regency_id" class="w-full rounded-lg border border-[#ddd8cf] px-3 py-2.5 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                <option value="">Semua</option>
                @foreach($regencies as $r)
                <option value="{{ $r->id }}" @selected(request('regency_id') == $r->id)>{{ $r->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Filter</button>
            <a href="{{ route('klinik.consultants.index') }}" class="rounded-lg border border-[#ddd8cf] px-4 py-2.5 text-sm font-semibold text-gray-500 hover:bg-gray-50">Reset</a>
        </div>
    </div>
</form>

{{-- Results --}}
<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    @forelse($consultants as $c)
    @php
        $name = trim(($c->user->firstname ?? '') . ' ' . ($c->user->lastname ?? ''));
        $startingPrice = $c->packages
            ->map(fn ($package) => (int) $package->price + (int) ($markups[$package->id] ?? 0))
            ->filter()
            ->min();
        $primaryCategory = $c->categories->first()?->name ?? 'Konsultasi';
    @endphp
    <article class="flex min-h-[310px] flex-col rounded-lg border border-[#e7e2d9] bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md">
        <div class="flex items-start gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg bg-emerald-700 text-lg font-bold text-white">
                {{ strtoupper(substr($name ?: 'K', 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-800">{{ $primaryCategory }}</span>
                    <span class="rounded bg-gray-100 px-2 py-1 text-[11px] font-semibold text-gray-600">{{ $c->schedules_count }} slot</span>
                </div>
                <h3 class="mt-2 line-clamp-2 text-base font-bold leading-6 text-gray-900">{{ $name ?: 'Konsultan Klinik' }}</h3>
                <p class="mt-1 text-xs text-gray-500">{{ $c->user->regency->name ?? 'Lokasi fleksibel' }}{{ $c->user->regency?->province ? ', ' . $c->user->regency->province->name : '' }}</p>
            </div>
        </div>

        <p class="mt-4 line-clamp-3 text-sm leading-6 text-gray-600">{{ $c->about ?: 'Profil konsultan tersedia untuk sesi klinik.' }}</p>

        <div class="mt-4 grid grid-cols-3 gap-2">
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                <div class="text-[11px] font-semibold uppercase text-gray-400">Mulai</div>
                <div class="mt-1 text-sm font-bold text-gray-900">
                    {{ $startingPrice ? 'Rp' . number_format($startingPrice, 0, ',', '.') : '-' }}
                </div>
            </div>
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                <div class="text-[11px] font-semibold uppercase text-gray-400">Online</div>
                <div class="mt-1 text-sm font-bold text-gray-900">{{ $c->online_schedules_count }} slot</div>
            </div>
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                <div class="text-[11px] font-semibold uppercase text-gray-400">Offline</div>
                <div class="mt-1 text-sm font-bold text-gray-900">{{ $c->offline_schedules_count }} slot</div>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-1.5">
            @foreach($c->categories->take(3) as $cat)
                <span class="rounded border border-gray-200 px-2 py-1 text-[11px] font-medium text-gray-600">{{ $cat->name }}</span>
            @endforeach
        </div>

        <div class="mt-auto flex items-center justify-between gap-3 border-t pt-4">
            <span class="text-xs text-gray-500">{{ $c->packages_count }} paket layanan</span>
            <a href="{{ route('klinik.consultants.show', $c) }}" class="rounded-lg bg-emerald-700 px-4 py-2.5 text-xs font-semibold text-white hover:bg-emerald-800">Pilih Paket</a>
        </div>
    </article>
    @empty
    <div class="col-span-full rounded-lg border border-dashed border-[#d8d2c8] bg-white p-8 text-center">
        <div class="text-base font-semibold text-gray-800">Katalog belum menemukan konsultan.</div>
        <p class="mt-2 text-sm text-gray-500">Coba reset filter, pilih semua daerah, atau tambahkan slot jadwal untuk konsultan klinik.</p>
        <a href="{{ route('klinik.consultants.index') }}" class="mt-4 inline-flex rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Reset katalog</a>
    </div>
    @endforelse
</div>

<div class="mt-6">{{ $consultants->links() }}</div>
@endsection
