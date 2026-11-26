@extends('layouts.app')

@php
    $title = 'Asesmen';
@endphp

@section('content')
<div class="max-w-4xl">
    <div class="mb-4">
        <h1 class="text-xl font-bold">Asesmen</h1>
        <p class="text-sm text-gray-500">Instrumen asesmen wellbeing aktif di platform (read-only).</p>
    </div>

    <div class="dash-card p-5">
        @if($instruments->isEmpty())
            <p class="text-sm text-gray-400">Belum ada instrumen.</p>
        @else
            <div class="space-y-2">
                @foreach($instruments as $i)
                <div class="panel-surface bg-white flex items-center justify-between text-sm">
                    <div class="min-w-0">
                        <p class="font-medium text-gray-700">{{ $i->name }} <span class="text-xs text-gray-400">({{ $i->code }})</span></p>
                        <p class="text-xs text-gray-400 truncate">{{ $i->interpretation }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $i->questions()->count() }} item · {{ $i->scoring_type }}{{ $i->is_addon ? ' · add-on' : '' }}</p>
                    </div>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $i->is_active ? 'bg-pine-100 text-pine-800' : 'bg-gray-100 text-gray-500' }}">{{ $i->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
