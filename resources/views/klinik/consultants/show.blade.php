@extends('layouts.app')

@section('content')
<a href="{{ route('klinik.consultants.index') }}" class="text-sm text-emerald-700 hover:underline">← Kembali ke daftar konsultan</a>

{{-- Profil (mirror B2C: deskripsi langsung terlihat) --}}
<div class="bg-white border rounded-lg p-6 mt-3 mb-6">
    <div class="flex flex-col md:flex-row gap-6">
        <div class="w-24 h-24 rounded-full bg-emerald-100 flex items-center justify-center text-3xl font-bold text-emerald-800 shrink-0">
            {{ substr($consultant->user->firstname ?? '?', 0, 1) }}
        </div>
        <div class="flex-1">
            <h1 class="text-2xl font-bold">{{ $consultant->user->full_name }}</h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ $consultant->user->regency->name ?? 'Lokasi fleksibel' }}{{ $consultant->user->regency?->province ? ', ' . $consultant->user->regency->province->name : '' }}
                · {{ $consultant->experience ?: 0 }} tahun pengalaman
            </p>
            <div class="flex flex-wrap gap-2 mt-3">
                @foreach($consultant->categories as $category)
                    <span class="text-xs bg-emerald-50 text-emerald-800 rounded px-2 py-1">{{ $category->name }}</span>
                @endforeach
                @foreach($consultant->skills->take(6) as $skill)
                    <span class="text-xs bg-gray-100 text-gray-700 rounded px-2 py-1">{{ $skill->name }}</span>
                @endforeach
            </div>
            <p class="text-sm text-gray-700 leading-6 mt-4">{{ $consultant->about ?: 'Belum ada deskripsi profil.' }}</p>
        </div>
        <div class="md:text-right text-sm text-gray-500 shrink-0">
            <div class="font-semibold text-gray-800 text-lg">{{ $packages->count() }}</div>
            <div class="text-xs">paket</div>
            <div class="font-semibold text-gray-800 text-lg mt-3">{{ $upcomingSchedules->count() }}</div>
            <div class="text-xs">slot tersedia</div>
        </div>
    </div>
</div>

{{-- Alur pilih paket -> jadwal -> generate link customer --}}
<form action="{{ route('klinik.links.store') }}" method="POST" class="space-y-6">
    @csrf

    {{-- Step 1: Paket --}}
    <div class="bg-white border rounded-lg p-5">
        <h2 class="font-semibold mb-1">1. Pilih Paket</h2>
        <p class="text-xs text-gray-400 mb-4">Harga sudah termasuk margin klinik ({{ $klinik->brand_name ?? $klinik->name ?? 'brand klinik' }}).</p>
        <div class="grid md:grid-cols-2 gap-3">
            @forelse($packages as $package)
                @php
                    $markup = (int) ($markups[$package->id] ?? 0);
                    $final = (int) $package->price + $markup;
                @endphp
                <label class="package-option border-2 rounded-lg p-4 cursor-pointer transition-colors hover:border-emerald-400 has-checked:border-emerald-600 has-checked:bg-emerald-50">
                    <input type="radio" name="consultant_package_id" value="{{ $package->id }}" class="sr-only" data-mode="{{ $package->mode ?? 'online' }}" @checked($loop->first) required>
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="font-semibold text-sm">{{ $package->name }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">{{ $package->duration }} menit · {{ strtoupper($package->mode ?? 'online') }}</div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-sm font-bold text-gray-800">Rp{{ number_format($final, 0, ',', '.') }}</div>
                            @if($markup > 0)
                                <div class="text-[11px] text-gray-400">margin Rp{{ number_format($markup, 0, ',', '.') }}</div>
                            @endif
                        </div>
                    </div>
                </label>
            @empty
                <p class="text-sm text-gray-400">Belum ada paket aktif untuk konsultan ini.</p>
            @endforelse
        </div>
    </div>

    {{-- Step 2: Jadwal --}}
    <div class="bg-white border rounded-lg p-5">
        <h2 class="font-semibold mb-1">2. Pilih Jadwal</h2>
        <p class="text-xs text-gray-400 mb-4">Slot yang tampil hanya yang mode-nya sama dengan paket terpilih.</p>
        <div id="schedule-list" class="grid md:grid-cols-3 gap-3">
            @forelse($upcomingSchedules as $schedule)
                <label class="schedule-option border-2 rounded-lg p-4 cursor-pointer transition-colors hover:border-emerald-400 has-checked:border-emerald-600 has-checked:bg-emerald-50" data-mode="{{ $schedule->mode ?? 'online' }}">
                    <input type="radio" name="schedule_id" value="{{ $schedule->id }}" class="sr-only" @disabled(!$loop->first)>
                    <div class="font-semibold text-sm">{{ $schedule->started_at->format('d M Y') }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ $schedule->started_at->format('H:i') }} - {{ $schedule->ended_at->format('H:i') }} · {{ strtoupper($schedule->mode ?? 'online') }}</div>
                    @if(($schedule->mode ?? 'online') === 'offline' && $schedule->location)
                        <div class="text-[11px] text-gray-400 mt-1 truncate">{{ $schedule->location }}</div>
                    @endif
                </label>
            @empty
                <p class="text-sm text-gray-400 col-span-3">
                    Belum ada jadwal untuk klinik ini.
                    <a href="{{ route('klinik.schedules.create', ['consultant_id' => $consultant->id]) }}" class="text-emerald-700 font-semibold">Atur jadwal dulu →</a>
                </p>
            @endforelse
        </div>
    </div>

    <button type="submit" id="btn-next" class="bg-emerald-700 text-white rounded px-6 py-3 font-semibold disabled:opacity-40" @disabled($upcomingSchedules->isEmpty())>
        Generate Link Customer →
    </button>
</form>

{{-- Detail pendukung (sidebar) --}}
<div class="grid lg:grid-cols-3 gap-6 mt-8">
    <div class="bg-white border rounded-lg p-5">
        <h2 class="font-semibold mb-3">Pendidikan</h2>
        <div class="space-y-3">
            @forelse($consultant->consultantEducations as $education)
                <div>
                    <div class="font-medium text-sm">{{ $education->name ?? 'Riwayat pendidikan' }}</div>
                    <p class="text-xs text-gray-500">{{ $education->pivot->degree ?? '-' }} · {{ $education->pivot->start_date ?? '-' }} - {{ $education->pivot->end_date ?? 'Sekarang' }}</p>
                </div>
            @empty
                <p class="text-sm text-gray-400">Belum ada data pendidikan.</p>
            @endforelse
        </div>
    </div>
    <div class="bg-white border rounded-lg p-5">
        <h2 class="font-semibold mb-3">Pengalaman</h2>
        <div class="space-y-3">
            @forelse($consultant->consultantCompanies as $company)
                <div>
                    <div class="font-medium text-sm">{{ $company->name }}</div>
                    <p class="text-xs text-gray-500">{{ $company->pivot->position ?? '-' }}</p>
                </div>
            @empty
                <p class="text-sm text-gray-400">Belum ada data pengalaman kerja.</p>
            @endforelse
        </div>
    </div>
    <div class="bg-white border rounded-lg p-5">
        <h2 class="font-semibold mb-3">Sertifikat</h2>
        <div class="space-y-2">
            @forelse($consultant->certificates as $certificate)
                <div class="text-sm text-gray-700">{{ $certificate->name }} <span class="text-xs text-gray-400">({{ $certificate->year }})</span></div>
            @empty
                <p class="text-sm text-gray-400">Belum ada data sertifikat.</p>
            @endforelse
        </div>
    </div>
</div>

<script>
// Filter slot sesuai mode paket terpilih; preselect slot pertama yang cocok.
const radios = document.querySelectorAll('input[name="consultant_package_id"]');
const slots = document.querySelectorAll('.schedule-option');
function syncSlots() {
    const mode = document.querySelector('input[name="consultant_package_id"]:checked')?.dataset.mode;
    let firstVisible = null;
    slots.forEach(slot => {
        const match = slot.dataset.mode === mode;
        slot.style.display = match ? '' : 'none';
        const input = slot.querySelector('input');
        input.disabled = !match;
        input.checked = false;
        if (match && !firstVisible) firstVisible = input;
    });
    if (firstVisible) firstVisible.checked = true;
    document.getElementById('btn-next').disabled = !firstVisible;
}
radios.forEach(r => r.addEventListener('change', syncSlots));
syncSlots();
</script>
@endsection
