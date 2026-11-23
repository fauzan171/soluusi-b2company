@extends('layouts.app')

@php
    $title = 'Aktivitasku';
@endphp

@section('content')
<div class="max-w-3xl">
    <div class="mb-4">
        <h1 class="text-xl font-bold">Aktivitasku</h1>
        <p class="text-sm text-gray-500">Webinar program dan pengumuman dari {{ $perusahaan?->brand ?? 'perusahaan Anda' }}.</p>
    </div>

    <div class="dash-card p-5 mb-4">
        <h2 class="text-[0.95rem] font-semibold text-gray-900 mb-3">Webinar program</h2>
        @if($webinars->isEmpty())
            <p class="text-sm text-gray-400">Belum ada webinar terjadwal.</p>
        @else
            <div class="space-y-2">
                @foreach($webinars as $w)
                <div class="panel-surface bg-white flex items-center justify-between text-sm">
                    <div class="min-w-0">
                        <p class="font-medium text-gray-700 truncate">{{ $w->title }}</p>
                        <p class="text-xs text-gray-400">{{ optional($w->scheduled_at)->format('d M Y, H:i') ?? '-' }}</p>
                    </div>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $w->status === 'done' ? 'bg-gray-100 text-gray-500' : 'bg-pine-100 text-pine-800' }}">{{ $w->status === 'done' ? 'Selesai' : 'Terjadwal' }}</span>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="dash-card p-5">
        <h2 class="text-[0.95rem] font-semibold text-gray-900 mb-3">Pengumuman</h2>
        @if($campaigns->isEmpty())
            <p class="text-sm text-gray-400">Belum ada pengumuman.</p>
        @else
            <div class="space-y-2">
                @foreach($campaigns as $c)
                <div class="panel-surface bg-white p-4">
                    <div class="flex items-center justify-between gap-3">
                        <p class="font-medium text-gray-700">{{ $c->title }}</p>
                        <span class="text-xs text-gray-400 shrink-0">{{ optional($c->sent_at)->format('d M Y') }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 whitespace-pre-line">{{ $c->body }}</p>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
