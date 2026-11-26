@extends('layouts.app')

@php
    $title = 'Campaign Keterlibatan';
@endphp

@section('content')
<div class="max-w-3xl">
    <div class="mb-4">
        <h1 class="text-xl font-bold">Campaign Keterlibatan</h1>
        <p class="text-sm text-gray-500">Pengumuman tampil di Aktivitasku karyawan; email terjadwal dikirim otomatis harian.</p>
    </div>

    <div class="dash-card p-5 mb-4">
        <h2 class="text-[0.95rem] font-semibold text-gray-900 mb-3">Buat campaign</h2>
        <form method="POST" action="{{ route('perusahaan.campaigns.store') }}" class="space-y-3">
            @csrf
            <div>
                <label for="title" class="block text-xs font-medium text-gray-500 mb-1">Judul</label>
                <input id="title" name="title" type="text" required maxlength="120"
                       class="w-full rounded-xl border border-[#e7e2d9] px-3 py-2 text-sm" placeholder="mis. Webinar Mindfulness bulan depan">
                @error('title')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="body" class="block text-xs font-medium text-gray-500 mb-1">Isi</label>
                <textarea id="body" name="body" rows="4" required maxlength="4000"
                          class="w-full rounded-xl border border-[#e7e2d9] px-3 py-2 text-sm" placeholder="Isi pesan untuk karyawan…"></textarea>
                @error('body')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="kind" class="block text-xs font-medium text-gray-500 mb-1">Jenis campaign</label>
                <select id="kind" name="kind" class="w-full rounded-xl border border-[#e7e2d9] px-3 py-2 text-sm">
                    <option value="announcement">Pengumuman / aktivitas mingguan</option>
                    <option value="edukasi">Edukasi berkala</option>
                    <option value="pengumuman">Pengumuman program</option>
                    <option value="reminder">Pengingat assessment</option>
                </select>
                @error('kind')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="channel" class="block text-xs font-medium text-gray-500 mb-1">Kanal</label>
                    <select id="channel" name="channel" class="w-full rounded-xl border border-[#e7e2d9] px-3 py-2 text-sm">
                        <option value="announcement">Pengumuman (langsung tampil)</option>
                        <option value="email">Email (terjadwal)</option>
                    </select>
                    @error('channel')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="scheduled_at" class="block text-xs font-medium text-gray-500 mb-1">Terkirim pada (email saja)</label>
                    <input id="scheduled_at" name="scheduled_at" type="datetime-local"
                           class="w-full rounded-xl border border-[#e7e2d9] px-3 py-2 text-sm">
                    <p class="mt-1 text-[11px] text-gray-400">Email hanya dikirim ke karyawan yang sudah memberi consent.</p>
                    @error('scheduled_at')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <button type="submit" class="rounded-xl bg-pine-700 px-4 py-2 text-sm font-semibold text-white hover:bg-pine-800">Simpan</button>
        </form>
    </div>

    <div class="dash-card p-5">
        <h2 class="text-[0.95rem] font-semibold text-gray-900 mb-3">Riwayat campaign</h2>
        @if($campaigns->isEmpty())
            <p class="text-sm text-gray-400">Belum ada campaign.</p>
        @else
            <div class="space-y-2">
                @foreach($campaigns as $c)
                <div class="panel-surface bg-white p-4">
                    <div class="flex items-center justify-between gap-3">
                        <p class="font-medium text-gray-700">{{ $c->title }}</p>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-xs text-gray-400">{{ ['announcement' => 'Pengumuman / aktivitas mingguan', 'edukasi' => 'Edukasi berkala', 'pengumuman' => 'Pengumuman program', 'reminder' => 'Pengingat assessment', ][$c->kind] ?? 'Pengumuman / aktivitas mingguan' }} · {{ $c->channel === 'email' ? 'Email' : 'Pengumuman' }}</span>
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $c->sent_at ? 'bg-pine-100 text-pine-800' : 'bg-gray-100 text-gray-500' }}">{{ $c->sent_at ? 'Terkirim '.optional($c->sent_at)->format('d M') : 'Terjadwal '.optional($c->scheduled_at)->format('d M') }}</span>
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 line-clamp-2">{{ \Illuminate\Support\Str::limit($c->body, 160) }}</p>
                </div>
                @endforeach
            </div>
            {{ $campaigns->links() }}
        @endif
    </div>
</div>
@endsection
