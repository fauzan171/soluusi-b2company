@extends('layouts.app')

@section('title', 'Portal Siswa — ' . $sekolah->brand)

@section('content')
@php
$pendingRequest = $history->first(fn ($h) => $h->status === 'scheduled' && $h->scheduled_at === null);
@endphp
<div x-data="{ detail: null, rescheduleId: null, requestConsult: false }" @keydown.escape.window="detail = null; requestConsult = false">
    <div class="flex items-center justify-between mb-1">
        <h1 class="text-xl font-bold text-pine-900">Halo, {{ $student->name }}</h1>
        @if($isPreview)
        <span class="text-[10px] font-semibold bg-terra-100 text-terra-800 rounded-lg px-2 py-1">Preview Super Admin</span>
        @endif
    </div>
    <p class="text-sm text-gray-500 mb-6">Semua sesi kamu bersifat rahasia — hanya kamu dan konsultan yang tahu isinya. Sekolah hanya melihat data agregat.</p>

    <div class="grid md:grid-cols-3 gap-4 mb-6">
        {{-- Kuota --}}
        <div class="portal-card p-5">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-2xl bg-pine-50 text-pine-700">
                    @include('perusahaan.partials.icon', ['name' => 'calendar'])
                </span>
                <div>
                    <div class="text-2xl font-bold text-pine-700">{{ max(0, $quota - $usedMonth) }}<span class="text-sm font-normal text-gray-400"> / {{ $quota }}</span></div>
                    <div class="text-xs text-gray-500">Sisa sesi bulan ini</div>
                </div>
            </div>
            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden mt-3">
                <div class="h-full bg-pine-600 rounded-full transition-all duration-700" style="width: {{ min(100, round($usedMonth / max(1, $quota) * 100)) }}%"></div>
            </div>
        </div>

        {{-- Wellbeing score --}}
        <div class="portal-card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500">Skor kesejahteraan (WHO-5)</p>
                    @if($assessment)
                    <p class="text-2xl font-bold mt-1 {{ $assessment->band() === 'Sehat' ? 'text-pine-700' : ($assessment->band() === 'Perlu Perhatian' ? 'text-terra-500' : 'text-red-600') }}">{{ $assessment->score }}<span class="text-sm text-gray-400">/100</span></p>
                    <p class="text-[11px] text-gray-400">{{ $assessment->band() }} · {{ $assessment->taken_at->translatedFormat('d M') }}</p>
                    @else
                    <p class="text-sm text-gray-500 mt-1">Belum ada</p>
                    @endif
                </div>
                <a href="{{ route('sekolah.assessment.form') }}" class="text-xs bg-pine-700 text-white px-3 py-1.5 rounded-lg">{{ $assessment ? 'Ulangi' : 'Isi' }}</a>
            </div>
            @if($needsAssessment)
            <p class="text-[11px] text-terra-700 mt-2">Asesmen terakhir &gt; 30 hari lalu — isi ulang ya.</p>
            @endif
        </div>

        {{-- Booking CTA --}}
        <a href="{{ route('sekolah.booking') }}" class="portal-card p-5 hover:shadow-md transition flex flex-col justify-between">
            <div>
                <span class="grid h-10 w-10 place-items-center rounded-2xl bg-pine-50 text-pine-700">
                    @include('perusahaan.partials.icon', ['name' => 'sparkles'])
                </span>
                <p class="text-sm font-semibold text-pine-900 mt-3">Booking konseling</p>
                <p class="text-xs text-gray-500">Pilih topik & jadwal. Online atau di ruang BK.</p>
            </div>
            <span class="text-xs font-semibold text-pine-700 mt-3">Buat jadwal →</span>
        </a>

        {{-- Minta konseling CTA --}}
        <button type="button" @click="requestConsult = true" class="portal-card p-5 hover:shadow-md transition flex flex-col justify-between text-left">
            <div>
                <span class="grid h-10 w-10 place-items-center rounded-2xl bg-pine-50 text-pine-700">
                    @include('perusahaan.partials.icon', ['name' => 'chat'])
                </span>
                <p class="text-sm font-semibold text-pine-900 mt-3">Minta Konseling</p>
                <p class="text-xs text-gray-500">Butuh teman cerita? Konselor siap dengerin.</p>
            </div>
            <span class="text-xs font-semibold text-pine-700 mt-3">Kirim request →</span>
        </button>
    </div>

    {{-- Modal: minta konseling --}}
    <div x-show="requestConsult" x-cloak x-transition.opacity class="fixed inset-0 z-50 grid place-items-center bg-pine-950/50 p-4">
        <div @click.outside="requestConsult = false" x-show="requestConsult" x-transition.scale class="portal-card w-full max-w-md p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="font-semibold text-pine-900">Minta Konseling</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Request kamu direview guru BK dulu. Setelah disetujui, sistem carikan konselor yang paling siap.</p>
                </div>
                <button type="button" @click="requestConsult = false" aria-label="Tutup" class="text-gray-400 hover:text-gray-700 text-lg leading-none">&times;</button>
            </div>

            @if($errors->any())
            <div class="mt-4 bg-terra-50 border border-terra-200 rounded-xl p-3 text-xs text-terra-800" role="alert">
                <p class="font-semibold">Periksa lagi isian kamu.</p>
                <ul class="mt-1 space-y-0.5 list-disc list-inside">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('sekolah.siswa.request') }}" class="mt-4 space-y-4">
                @csrf

                <div>
                    <label for="req_konsultan_type" class="block text-sm font-semibold text-pine-900 mb-1.5">
                        Jenis konselor <span class="text-terra-600" aria-hidden="true">*</span>
                    </label>
                    <select id="req_konsultan_type" name="konsultan_type" required
                        aria-invalid="{{ $errors->has('konsultan_type') ? 'true' : 'false' }}"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm bg-white transition
                            {{ $errors->has('konsultan_type') ? 'border-terra-400 focus:border-terra-500 focus:ring-terra-500' : 'border-gray-300 focus:border-pine-500 focus:ring-pine-500' }}
                            focus:outline-none focus:ring-2 focus:ring-offset-0">
                        <option value="">— Pilih jenis konselor —</option>
                        <option value="psikolog" @selected(old('konsultan_type') === 'psikolog')>Psikolog Anak & Remaja</option>
                        <option value="karir" @selected(old('konsultan_type') === 'karir')>Konselor Karir</option>
                    </select>
                    @error('konsultan_type')
                    <p class="text-xs text-terra-600 mt-1.5 font-medium" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="req_topic_category" class="block text-sm font-semibold text-pine-900 mb-1.5">
                        Topik <span class="text-xs font-normal text-gray-400">(opsional)</span>
                    </label>
                    <input id="req_topic_category" name="topic_category" maxlength="100" value="{{ old('topic_category') }}"
                        aria-invalid="{{ $errors->has('topic_category') ? 'true' : 'false' }}"
                        placeholder="Misal: Cemas ujian, Pertemanan, Keluarga"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm bg-white transition
                            {{ $errors->has('topic_category') ? 'border-terra-400 focus:border-terra-500 focus:ring-terra-500' : 'border-gray-300 focus:border-pine-500 focus:ring-pine-500' }}
                            focus:outline-none focus:ring-2 focus:ring-offset-0">
                    @error('topic_category')
                    <p class="text-xs text-terra-600 mt-1.5 font-medium" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="req_request_notes" class="block text-sm font-semibold text-pine-900 mb-1.5">
                        Cerita singkat <span class="text-terra-600" aria-hidden="true">*</span>
                    </label>
                    <p class="text-xs text-gray-500 mb-2">Tulis apa yang mau kamu bicarakan. Maks. 2.000 karakter.</p>
                    <textarea id="req_request_notes" name="request_notes" rows="4" maxlength="2000" required
                        aria-invalid="{{ $errors->has('request_notes') ? 'true' : 'false' }}"
                        placeholder="Apa yang mau kamu bicarakan?"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm bg-white transition
                            {{ $errors->has('request_notes') ? 'border-terra-400 focus:border-terra-500 focus:ring-terra-500' : 'border-gray-300 focus:border-pine-500 focus:ring-pine-500' }}
                            focus:outline-none focus:ring-2 focus:ring-offset-0">{{ old('request_notes') }}</textarea>
                    @error('request_notes')
                    <p class="text-xs text-terra-600 mt-1.5 font-medium" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="bg-pine-50 border border-pine-200 rounded-xl p-3 text-xs text-pine-800 leading-relaxed">
                    <strong class="font-semibold">Rahasia penuh.</strong> Hanya kamu dan konselor yang tahu isi chat ini. Guru BK hanya melihat jumlah agregat.
                </div>

                <button type="submit"
                    class="w-full rounded-lg bg-pine-700 text-white text-sm font-semibold px-4 py-3 transition hover:bg-pine-800 focus:outline-none focus:ring-2 focus:ring-pine-500 focus:ring-offset-2">
                    Kirim Request
                </button>
            </form>
        </div>
    </div>

    {{-- Permintaan menunggu review BK --}}
    @if($pendingRequest)
    <section class="portal-card p-5 mb-6 border-pine-300 bg-pine-50/40">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="dash-section-title">Permintaan konseling kamu sedang diproses</h3>
                <p class="text-sm font-medium text-pine-900 mt-2">{{ ucfirst($pendingRequest->konsultan_type) }}
                    @if($pendingRequest->topic_category) · {{ $pendingRequest->topic_category }}@endif
                </p>
                <p class="text-xs text-gray-500 mt-1">
                    {{ $pendingRequest->consultant_id ? 'Menunggu konfirmasi konselor' : 'Menunggu review guru BK' }}
                    — kamu dapat kabar begitu jadwal disetujui.
                </p>
            </div>
            <form method="POST" action="{{ route('sekolah.siswa.request.cancel', $pendingRequest) }}"
                onsubmit="return confirm('Batalkan permintaan konseling ini?')">
                @csrf
                <button type="submit" class="shrink-0 text-xs font-semibold text-terra-700 border border-terra-300 rounded-lg px-3 py-2 hover:bg-terra-50 transition">Batalkan permintaan</button>
            </form>
        </div>
    </section>
    @endif

    {{-- Jadwal mendatang — termasuk yang dijadwalkan guru BK --}}
    @php
    $upcoming = $history->where('status', 'scheduled')->filter(fn ($h) => $h->scheduled_at && $h->scheduled_at->isFuture());
    @endphp
    @if($upcoming->isNotEmpty())
    <section class="portal-card p-5 mb-6 border-pine-300 bg-pine-50/40">
        <h3 class="dash-section-title">Jadwal konseling kamu</h3>
        <p class="text-xs text-gray-500 mt-1">@if($upcoming->contains(fn ($h) => $h->origin === 'bk_referral'))Sebagian dijadwalkan oleh guru BK kamu.@endif</p>
        <div class="mt-3 space-y-2">
            @foreach($upcoming as $h)
            <div class="flex items-center justify-between rounded-xl border border-pine-200 bg-white px-4 py-3">
                <div>
                    <p class="text-sm font-medium">{{ ucfirst($h->konsultan_type) }} · {{ $h->mode === 'offline' ? 'Tatap muka' : 'Online' }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $h->scheduled_at->translatedFormat('l, d M Y, H.i') }}
                        @if($h->consultant?->user) · {{ $h->consultant->user->fullname }}@endif
                        @if($h->origin === 'bk_referral')
                        <span class="ml-1 text-[10px] font-semibold bg-terra-100 text-terra-800 rounded px-1.5 py-0.5">dijadwalkan guru BK</span>
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    @if($h->consultant_id)
                    <a href="{{ route('sekolah.chat', $h) }}"
                        class="text-xs font-semibold bg-pine-700 text-white rounded-lg px-3 py-1.5 hover:bg-pine-800">Chat</a>
                    @endif
                    <a href="{{ \App\Support\GCalLink::addLink('Konseling — ' . ucfirst($h->konsultan_type), $h->scheduled_at, 'Sesi konseling rahasia via program ' . $sekolah->brand . ($h->mode === 'offline' ? '. Lokasi: ruang BK sekolah.' : '. Link online dikirim mendekati jadwal.'), $h->mode === 'offline' ? 'Ruang BK ' . $sekolah->brand : null) }}"
                        target="_blank" rel="noopener"
                        class="text-xs font-semibold bg-blue-600 text-white rounded-lg px-3 py-1.5 hover:bg-blue-700 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19 3h-1V1h-2v2H8V1H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Zm0 16H5V9h14v10Zm0-12H5V5h14v2Z"/></svg>
                        Google Calendar
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Saran AI per sesi (siswa lihat miliknya sendiri) — C.3 --}}
    @if(isset($advices) && $advices->isNotEmpty())
    <h2 class="font-semibold text-sm text-pine-900 mb-2">Saran dari sesi konseling kamu</h2>
    <div class="space-y-3 mb-6">
        @foreach($history as $s)
            @php $adv = $advices[$s->id] ?? null; @endphp
            @if($adv && $adv->client_advice)
            <div class="portal-card p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs text-gray-500">
                            {{ ($s->completed_at ?? $s->scheduled_at)?->translatedFormat('d M Y') ?? '' }}
                            · {{ ucfirst($s->konsultan_type) }}
                        </p>
                        <p class="text-sm text-gray-800 mt-2 whitespace-pre-wrap leading-relaxed">{{ $adv->client_advice }}</p>
                    </div>
                    @if($s->consultant_id)
                    <a href="{{ route('sekolah.chat', $s) }}" class="text-xs font-semibold text-pine-700 hover:text-pine-900 whitespace-nowrap">Lihat sesi →</a>
                    @endif
                </div>
            </div>
            @endif
        @endforeach
    </div>
    @endif

    {{-- Riwayat --}}
    <section class="portal-card p-5">
        <h3 class="dash-section-title">Riwayat sesi kamu</h3>
        <div class="mt-4 space-y-2">
            @forelse($history->filter(fn ($h) => !($h->status === 'scheduled' && $h->scheduled_at === null)) as $h)
            <div class="flex items-center justify-between rounded-xl border border-[#e7e2d9] px-4 py-3">
                <div>
                    <p class="text-sm font-medium">{{ ucfirst($h->konsultan_type) }} · {{ $h->mode === 'offline' ? 'Tatap muka' : 'Online' }}</p>
                    <p class="text-xs text-gray-500">{{ $h->scheduled_at?->translatedFormat('d M Y, H.i') }}@if($h->consultant?->user) · {{ $h->consultant->user->fullname }}@endif</p>
                </div>
                @if($h->status === 'scheduled')
                <form method="POST" action="{{ route('sekolah.booking.cancel', $h) }}">@csrf
                    <button class="text-xs text-red-600 hover:underline">Batalkan</button>
                </form>
                @else
                <span class="text-xs font-semibold px-2 py-1 rounded-lg {{ $h->status === 'done' ? 'bg-pine-50 text-pine-700' : 'bg-gray-100 text-gray-500' }}">{{ ucfirst($h->status) }}</span>
                @endif
            </div>
            @empty
            <p class="text-sm text-gray-500">Belum ada sesi. Mulai dengan booking pertamamu.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
