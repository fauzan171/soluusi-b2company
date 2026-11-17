@extends('layouts.app')

@section('title', 'System Health — Admin')

@section('content')
<div class="flex items-baseline justify-between mb-2">
    <h1 class="text-xl font-bold">System Health</h1>
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-emerald-700 font-semibold hover:underline">← Dashboard</a>
</div>
<p class="text-sm text-gray-500 mb-6">Failed jobs, ukuran antrean, dan last-run task scheduler.</p>

@if(session('success'))
    <div class="mb-4 rounded bg-emerald-100 text-emerald-800 text-sm px-4 py-2">{{ session('success') }}</div>
@endif

<div class="grid gap-4 mb-8 md:grid-cols-2 max-w-3xl">
    <div class="bg-white border rounded-lg p-4">
        <div class="text-xs text-gray-500 uppercase font-semibold">Queue size</div>
        <div class="text-2xl font-bold mt-1">{{ $queueSize }} <span class="text-sm font-normal text-gray-500">job pending</span></div>
    </div>
    <div class="bg-white border rounded-lg p-4">
        <div class="text-xs text-gray-500 uppercase font-semibold">Failed jobs</div>
        <div class="text-2xl font-bold mt-1 {{ $failedJobs->count() ? 'text-red-600' : '' }}">{{ $failedJobs->count() }}</div>
    </div>
</div>

<h2 class="font-bold mb-3">Failed Jobs</h2>
<div class="overflow-x-auto bg-white border rounded-lg mb-8"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Job</th>
        <th class="p-3">Queue</th>
        <th class="p-3">Gagal pada</th>
        <th class="p-3">Error</th>
        <th class="p-3"></th>
    </tr></thead>
    <tbody>
        @forelse($failedJobs as $job)
        <tr class="border-b">
            <td class="p-3 font-semibold">{{ $job->displayName }}</td>
            <td class="p-3">{{ $job->queue }}</td>
            <td class="p-3">{{ \Illuminate\Support\Facades\Date::parse($job->failed_at)->translatedFormat('d M Y H:i') }}</td>
            <td class="p-3 text-xs text-red-600">{{ $job->exception }}</td>
            <td class="p-3 text-right whitespace-nowrap">
                <form method="POST" action="{{ route('admin.system-health.failed-jobs.retry', $job->uuid) }}" class="inline">
                    @csrf
                    <button class="text-emerald-700 font-semibold hover:underline mr-3">Retry</button>
                </form>
                <form method="POST" action="{{ route('admin.system-health.failed-jobs.delete', $job->uuid) }}" class="inline">
                    @csrf
                    <button class="text-red-600 font-semibold hover:underline">Hapus</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td class="p-3 text-gray-400">Tidak ada failed job.</td></tr>
        @endforelse
    </tbody>
</table></div>

<h2 class="font-bold mb-3">Schedule Last-Run</h2>
<div class="bg-white border rounded-lg max-w-3xl"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Command</th>
        <th class="p-3">Last run</th>
    </tr></thead>
    <tbody>
        @foreach($schedule as $command => $at)
        <tr class="border-b">
            <td class="p-3 font-mono text-xs">{{ $command }}</td>
            <td class="p-3">{{ $at ? $at->translatedFormat('d M Y H:i') : '<span class="text-gray-400">Belum pernah</span>' }}</td>
        </tr>
        @endforeach
    </tbody>
</table></div>
@endsection
