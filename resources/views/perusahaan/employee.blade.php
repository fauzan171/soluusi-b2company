@extends('layouts.app')

@section('content')
@php
    $typeLabels = ['psikolog' => 'Psikolog', 'nutrisionis' => 'Nutrisionis', 'fisioterapis' => 'Fisioterapis', 'karir' => 'Konselor Karir'];
    $who5Items = \App\Http\Controllers\Perusahaan\EmployeeController::WHO5_ITEMS;
    $answerLabels = \App\Http\Controllers\Perusahaan\EmployeeController::ANSWER_OPTIONS;
    $resources = [
        ['title' => 'Teknik pernapasan 4-7-8 untuk cemas', 'desc' => 'Latihan 3 menit untuk menenangkan sistem saraf sebelum tidur atau meeting.', 'tag' => 'Kecemasan', 'icon' => 'heart'],
        ['title' => 'Istirahat mikro di sela kerja', 'desc' => 'Riset menunjukkan jeda 5 menit tiap jam menjaga fokus dan menurunkan burnout.', 'tag' => 'Burnout', 'icon' => 'bolt'],
        ['title' => 'Bicara dengan atasan soal beban kerja', 'desc' => 'Panduan percakapan asertif tanpa konfrontasi untuk negosiasi prioritas.', 'tag' => 'Karir', 'icon' => 'megaphone'],
        ['title' => 'Sleep hygiene: 6 kebiasaan tidur sehat', 'desc' => 'Rutinitas malam yang terbukti memperbaiki kualitas tidur dalam 2 minggu.', 'tag' => 'Tidur', 'icon' => 'sparkles'],
    ];
@endphp

<div x-data="{ detail: null, wellbeing: false, rescheduleId: null }" @keydown.escape.window="detail = null; wellbeing = false; rescheduleId = null">
    <div class="flex items-center justify-between mb-1">
        <h1 class="text-xl font-bold text-pine-900">Halo, {{ $employee->name }}</h1>
        @if($isPreview)
        <span class="text-[10px] font-semibold bg-terra-100 text-terra-800 rounded-lg px-2 py-1">Preview Super Admin</span>
        @endif
    </div>
    <p class="text-sm text-gray-500 mb-6">Semua sesi kamu bersifat rahasia — hanya kamu dan konsultan yang tahu isinya. HR hanya melihat jumlah agregat.</p>

    {{-- Cards: quota, wellbeing, booking --}}
    <div class="grid md:grid-cols-3 gap-4 mb-6">
        <div class="portal-card p-5 transition hover:shadow-md">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-2xl bg-pine-50 text-pine-700">
                    @include('perusahaan.partials.icon', ['name' => 'calendar'])
                </span>
                <div>
                    <div class="text-2xl font-bold text-pine-700">{{ max(0, $quota - $usedMonth - $scheduledMonth) }}<span class="text-sm font-normal text-gray-400"> / {{ $quota }}</span></div>
                    <div class="text-xs text-gray-500">Sisa sesi bulan ini</div>
                </div>
            </div>
            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden mt-3">
                <div class="h-full bg-pine-600 rounded-full transition-all duration-700" style="width: {{ min(100, round(($usedMonth + $scheduledMonth) / max(1, $quota) * 100)) }}%"></div>
            </div>
            @if($scheduledMonth > 0)
            <p class="text-[11px] text-gray-400 mt-2">{{ $scheduledMonth }} sesi terjadwal</p>
            @endif
        </div>

        <button type="button" @if($assessment) @click="wellbeing = true" @endif class="portal-card p-5 text-left flex flex-col transition hover:shadow-md hover:border-pine-300">
            @if($assessment)
            @php
                $levelClass = $assessment->band() === 'Sehat' ? 'text-pine-700'
                    : ($assessment->band() === 'Perlu Perhatian' ? 'text-terra-500' : 'text-red-600');
            @endphp
            <div class="flex items-baseline justify-between w-full">
                <span class="text-2xl font-bold {{ $levelClass }}">{{ $assessment->score }}</span>
                <span class="text-xs text-gray-400">{{ $assessment->taken_at->format('d M Y') }}</span>
            </div>
            <div class="text-xs text-gray-500 mb-2">Wellbeing score: {{ $assessment->band() }} <span class="text-pine-600 font-medium">· lihat detail</span></div>
            @if(count($assessmentTrend) > 1)
            <div class="h-12 w-full"><canvas id="chartTrend"></canvas></div>
            @endif
            <span class="mt-auto pt-2 text-xs text-pine-700 font-semibold">{{ $needsAssessment ? 'Isi asesmen ulang →' : 'Isi asesmen lagi →' }}</span>
            @else
            <div class="text-2xl font-bold text-gray-300">—</div>
            <div class="text-xs text-gray-500 mb-2">Belum ada hasil wellbeing</div>
            <a href="{{ route('perusahaan.assessment.form') }}" class="mt-auto inline-block text-xs bg-pine-700 text-white font-semibold rounded-lg px-3 py-1.5 hover:bg-pine-800 text-center">Isi asesmen (1 menit) →</a>
            @endif
            <p class="text-[11px] text-gray-400 mt-2">Hasil ini milikmu sendiri, tidak dibagikan ke perusahaan.</p>
        </button>

        <div class="rounded-2xl p-5 flex flex-col justify-between text-white transition hover:shadow-lg" style="background: linear-gradient(135deg, #0c3b2e, #007648);">
            <div>
                <h2 class="font-semibold text-sm">Butuh cerita? Konsultan siap dengerin.</h2>
                <p class="text-xs text-pine-100 mt-1">Online, rahasia, sesuai sisa kuota kamu.</p>
            </div>
            @if(max(0, $quota - $usedMonth - $scheduledMonth) > 0)
            <div class="mt-3 flex flex-col gap-2">
                <button type="button" @click="requestConsult = true" class="bg-white text-pine-800 text-sm font-semibold rounded-lg px-4 py-2 text-center hover:bg-pine-50 transition">Minta Konsultasi →</button>
                <a href="{{ route('perusahaan.booking') }}" class="text-xs text-pine-100 text-center hover:underline">atau pilih jadwal sendiri</a>
            </div>
            @else
            <p class="mt-3 text-xs bg-white/10 rounded-lg px-3 py-2">Kuota bulan ini habis. Booking lagi bulan depan ya.</p>
            @endif
        </div>
    </div>

    {{-- Modal: minta konsultasi --}}
    <div x-show="requestConsult" x-cloak x-transition.opacity class="fixed inset-0 z-50 grid place-items-center bg-pine-950/50 p-4">
        <div @click.outside="requestConsult = false" x-show="requestConsult" x-transition.scale class="portal-card w-full max-w-md p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="font-semibold text-pine-900">Minta Konsultasi</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Sistem carikan konsultan yang paling siap. Kamu langsung bisa chat.</p>
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

            <form method="POST" action="{{ route('perusahaan.request-consultation') }}" class="mt-4 space-y-4">
                @csrf

                <div>
                    <label for="req_konsultan_type" class="block text-sm font-semibold text-pine-900 mb-1.5">
                        Jenis konsultan <span class="text-terra-600" aria-hidden="true">*</span>
                    </label>
                    <select id="req_konsultan_type" name="konsultan_type" required
                        aria-invalid="{{ $errors->has('konsultan_type') ? 'true' : 'false' }}"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm bg-white transition
                            {{ $errors->has('konsultan_type') ? 'border-terra-400 focus:border-terra-500 focus:ring-terra-500' : 'border-gray-300 focus:border-pine-500 focus:ring-pine-500' }}
                            focus:outline-none focus:ring-2 focus:ring-offset-0">
                        <option value="">— Pilih jenis konsultan —</option>
                        <option value="psikolog" @selected(old('konsultan_type') === 'psikolog')>Psikolog</option>
                        <option value="nutrisionis" @selected(old('konsultan_type') === 'nutrisionis')>Nutrisionis</option>
                        <option value="fisioterapis" @selected(old('konsultan_type') === 'fisioterapis')>Fisioterapis</option>
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
                        placeholder="Misal: Stress kerja, Kecemasan, Tidur"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm bg-white transition
                            {{ $errors->has('topic_category') ? 'border-terra-400 focus:border-terra-500 focus:ring-terra-500' : 'border-gray-300 focus:border-pine-500 focus:ring-pine-500' }}
                            focus:outline-none focus:ring-2 focus:ring-offset-0">
                    @error('topic_category')
                    <p class="text-xs text-terra-600 mt-1.5 font-medium" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="req_body" class="block text-sm font-semibold text-pine-900 mb-1.5">
                        Cerita singkat <span class="text-terra-600" aria-hidden="true">*</span>
                    </label>
                    <p class="text-xs text-gray-500 mb-2">Tulis apa yang mau kamu bicarakan. Maks. 2.000 karakter.</p>
                    <textarea id="req_body" name="body" rows="4" maxlength="2000" required
                        aria-invalid="{{ $errors->has('body') ? 'true' : 'false' }}"
                        placeholder="Apa yang mau kamu bicarakan?"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm bg-white transition
                            {{ $errors->has('body') ? 'border-terra-400 focus:border-terra-500 focus:ring-terra-500' : 'border-gray-300 focus:border-pine-500 focus:ring-pine-500' }}
                            focus:outline-none focus:ring-2 focus:ring-offset-0">{{ old('body') }}</textarea>
                    @error('body')
                    <p class="text-xs text-terra-600 mt-1.5 font-medium" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="bg-pine-50 border border-pine-200 rounded-xl p-3 text-xs text-pine-800 leading-relaxed">
                    <strong class="font-semibold">Rahasia penuh.</strong> Hanya kamu dan konsultan yang tahu isi chat ini.
                </div>

                <button type="submit"
                    class="w-full rounded-lg bg-pine-700 text-white text-sm font-semibold px-4 py-3 transition hover:bg-pine-800 focus:outline-none focus:ring-2 focus:ring-pine-500 focus:ring-offset-2">
                    Kirim &amp; Mulai Chat
                </button>
            </form>
        </div>
    </div>

    {{-- Jadwal mendatang + Google Calendar --}}
    @php $upcoming = $history->where('status', 'scheduled')->filter(fn ($s) => $s->scheduled_at?->isFuture()); @endphp
    @if($upcoming->isNotEmpty())
    <section class="portal-card p-5 mb-6 border-pine-300 bg-pine-50/40">
        <h3 class="dash-section-title">Jadwal mendatang kamu</h3>
        <div class="mt-3 space-y-2">
            @foreach($upcoming as $s)
            <div class="flex items-center justify-between rounded-xl border border-pine-200 bg-white px-4 py-3">
                <div>
                    <p class="text-sm font-medium">{{ $typeLabels[$s->konsultan_type] ?? ucfirst($s->konsultan_type) }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $s->scheduled_at->translatedFormat('l, d M Y, H:i') }} WIB
                        @if($s->consultant?->user) · {{ $s->consultant->user->fullname }}@endif
                    </p>
                </div>
                <a href="{{ \App\Support\GCalLink::addLink('Sesi Konsultasi — ' . ($typeLabels[$s->konsultan_type] ?? $s->konsultan_type), $s->scheduled_at, 'Sesi konseling rahasia via program ' . $perusahaan->brand . '. Link online dikirim mendekati jadwal.') }}"
                    target="_blank" rel="noopener"
                    class="shrink-0 text-xs font-semibold bg-blue-600 text-white rounded-lg px-3 py-1.5 hover:bg-blue-700 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19 3h-1V1h-2v2H8V1H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Zm0 16H5V9h14v10Zm0-12H5V5h14v2Z"/></svg>
                    Google Calendar
                </a>
            </div>
            @endforeach
        </div>
        <p class="text-[11px] text-gray-400 mt-3">Klik "Google Calendar" untuk simpan jadwal ke kalender kamu.</p>
    </section>
    @endif

    {{-- Riwayat --}}
    <div class="flex items-center justify-between mb-2">
        <h2 class="font-semibold text-sm text-pine-900">Riwayat sesi kamu</h2>
        <p class="text-[11px] text-gray-400">Klik baris untuk detail & aksi</p>
    </div>
    @if($usedMonth > 0)
    <p class="text-xs text-gray-500 mb-2">{{ $usedMonth }} dari {{ $quota }} sesi selesai bulan ini — bagus, terus jaga ritmenya.</p>
    @endif
    <div class="portal-card text-sm mb-6 overflow-hidden">
        <table class="w-full">
            <thead><tr class="border-b border-[#e7e2d9] text-left text-gray-500 text-xs bg-pine-50/50">
                <th class="p-3">Tanggal</th><th class="p-3">Topik</th><th class="p-3">Jenis konsultan</th><th class="p-3">Status</th><th class="p-3"></th>
            </tr></thead>
            <tbody>
                @forelse($history as $s)
                <tr class="border-b border-[#e7e2d9] last:border-0 hover:bg-pine-50/40 cursor-pointer transition"
                    @click="detail = {{ Js::from([
                        'id' => $s->id,
                        'tanggal' => ($s->completed_at ?? $s->scheduled_at)?->translatedFormat('l, d M Y H:i') ?? '—',
                        'topik' => $s->topic_category ?? '—',
                        'jenis' => ($s->consultant?->user?->fullname ? $s->consultant->user->fullname . ' — ' : '') . ($typeLabels[$s->konsultan_type] ?? ucfirst($s->konsultan_type)),
                        'status' => $s->status,
                        'statusLabel' => match($s->status) { 'done' => 'Selesai', 'scheduled' => $s->scheduled_at ? 'Terjadwal' : 'Menunggu ACC konsultan', 'cancelled' => 'Dibatalkan', default => 'Tidak hadir' },
                        'cancelUrl' => route('perusahaan.booking.cancel', $s),
                        'rescheduleUrl' => route('perusahaan.booking.reschedule', $s),
                        'chatUrl' => $s->consultant_id && in_array($s->status, ['scheduled', 'done']) ? route('perusahaan.chat', $s) : null,
                        'rating' => $s->rating,
                        'ratingStars' => $s->rating ? str_repeat('★', $s->rating) . str_repeat('☆', 5 - $s->rating) : null,
                    ]) }}">
                    <td class="p-3">{{ ($s->completed_at ?? $s->scheduled_at)?->format('d M Y') ?? '—' }}</td>
                    <td class="p-3">{{ $s->topic_category ?? '—' }}</td>
                    <td class="p-3">{{ $s->consultant?->user?->fullname ? $s->consultant->user->fullname . ' — ' : '' }}{{ $typeLabels[$s->konsultan_type] ?? ucfirst($s->konsultan_type) }}</td>
                    <td class="p-3">
                        <span class="text-xs px-2 py-0.5 rounded-lg {{ $s->status === 'done' ? 'bg-pine-100 text-pine-800' : ($s->status === 'scheduled' ? ($s->scheduled_at ? 'bg-terra-100 text-terra-800' : 'bg-amber-100 text-amber-800') : 'bg-gray-100 text-gray-500') }}">
                            {{ match($s->status) { 'done' => 'Selesai', 'scheduled' => $s->scheduled_at ? 'Terjadwal' : 'Menunggu ACC konsultan', 'cancelled' => 'Dibatalkan', default => 'Tidak hadir' } }}
                        </span>
                        @if($s->rating)
                        <span class="block text-[11px] text-amber-500 mt-0.5">{{ str_repeat('★', $s->rating) . str_repeat('☆', 5 - $s->rating) }}</span>
                        @endif
                    </td>
                    <td class="p-3 text-right">
                        @if($s->consultant_id && in_array($s->status, ['scheduled', 'done']))
                        <a href="{{ route('perusahaan.chat', $s) }}" @click.stop
                           class="inline-block whitespace-nowrap rounded-lg bg-pine-700 text-white text-xs font-semibold px-3 py-1.5 hover:bg-pine-800">{{ $s->status === 'done' && !$s->rating ? 'Beri rating' : 'Chat' }}</a>
                        @else
                        <span class="text-gray-300">›</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="p-4 text-gray-400">Belum ada sesi. <a href="{{ route('perusahaan.booking') }}" class="text-pine-700 font-semibold hover:underline">Yuk mulai dengan pilih jadwal.</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Resources self-help --}}
    <h2 class="font-semibold text-sm mb-2 text-pine-900">Bacaan singkat buat kamu</h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        @foreach($resources as $r)
        <div class="portal-card p-4 transition hover:shadow-md hover:border-pine-300">
            <div class="flex items-center justify-between">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-pine-50 text-pine-700">
                    @include('perusahaan.partials.icon', ['name' => $r['icon']])
                </span>
                <span class="text-[10px] font-semibold bg-pine-100 text-pine-800 rounded-full px-2 py-0.5">{{ $r['tag'] }}</span>
            </div>
            <p class="mt-3 text-sm font-semibold text-gray-900 leading-5">{{ $r['title'] }}</p>
            <p class="mt-1 text-xs text-gray-500 leading-5">{{ $r['desc'] }}</p>
        </div>
        @endforeach
    </div>

    <p class="text-xs text-gray-400">Program kesejahteraan karyawan {{ $perusahaan->brand }}. Data kamu dilindungi sesuai UU PDP.</p>

    {{-- Modal: detail sesi --}}
    <div x-show="detail" x-cloak x-transition.opacity class="fixed inset-0 z-50 grid place-items-center bg-pine-950/50 p-4">
        <div @click.outside="detail = null" x-show="detail" x-transition.scale class="portal-card w-full max-w-md p-6">
            <div class="flex items-start justify-between">
                <h3 class="font-semibold text-pine-900">Detail sesi</h3>
                <button @click="detail = null" class="text-gray-400 hover:text-gray-700">✕</button>
            </div>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Jadwal</dt><dd class="font-medium text-right" x-text="detail?.tanggal"></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Jenis konsultan</dt><dd class="font-medium" x-text="detail?.jenis"></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Topik</dt><dd class="font-medium" x-text="detail?.topik"></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Status</dt><dd class="font-medium" x-text="detail?.statusLabel"></dd></div>
                <template x-if="detail?.ratingStars">
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Rating kamu</dt><dd class="font-medium text-amber-500" x-text="detail?.ratingStars"></dd></div>
                </template>
            </dl>
            <template x-if="detail?.status === 'scheduled'">
                <div class="mt-6 grid grid-cols-2 gap-2">
                    <button @click="rescheduleId = detail.id; detail = null" class="rounded-lg border border-pine-700 text-pine-700 text-sm font-semibold px-3 py-2 hover:bg-pine-50">Ubah jadwal</button>
                    <form method="POST" :action="detail?.cancelUrl" onsubmit="return confirm('Batalkan sesi ini? Kuota kembali hanya sebelum batas 2 jam.')">
                        @csrf
                        <label class="block text-sm">Alasan pembatalan <input name="reason" required maxlength="1000" class="w-full border rounded"></label>
                        <button class="w-full rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm font-semibold px-3 py-2 hover:bg-red-100">Batalkan</button>
                    </form>
                </div>
            </template>
            <p class="mt-4 text-[11px] text-gray-400">Link sesi online dikirim ke email kamu mendekati jadwal.</p>
            <template x-if="detail?.chatUrl">
                <a :href="detail.chatUrl" class="mt-3 block text-center rounded-lg bg-pine-700 text-white text-sm font-semibold px-3 py-2 hover:bg-pine-800">Chat dengan konsultan</a>
            </template>
        </div>
    </div>

    {{-- Modal: reschedule --}}
    <div x-show="rescheduleId" x-cloak x-transition.opacity class="fixed inset-0 z-50 grid place-items-center bg-pine-950/50 p-4">
        <div @click.outside="rescheduleId = null" x-show="rescheduleId" x-transition.scale class="portal-card w-full max-w-md p-6">
            <div class="flex items-start justify-between">
                <h3 class="font-semibold text-pine-900">Pilih jadwal baru</h3>
                <button @click="rescheduleId = null" class="text-gray-400 hover:text-gray-700">✕</button>
            </div>
            <form method="POST" :action="'{{ url('/perusahaan/employee/sessions') }}/' + rescheduleId + '/reschedule'" class="mt-4">
                @csrf
                <label for="reschedule-slot" class="block text-sm">Slot baru sesuai jenis konsultan (WIB)</label>
                <select id="reschedule-slot" name="schedule_id" required class="w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="">— pilih slot —</option>
                    @foreach(\App\Services\BookingService::available('perusahaan', $perusahaan->id) as $slot)
                    <option value="{{ $slot->id }}">{{ $slot->started_at->translatedFormat('d M Y H:i') }} · {{ $slot->consultant->user->firstname }}</option>
                    @endforeach
                </select>
                @error('schedule_id')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                <button class="mt-4 w-full rounded-lg bg-pine-700 text-white text-sm font-semibold px-4 py-2.5 hover:bg-pine-800">Simpan jadwal baru</button>
            </form>
        </div>
    </div>

    {{-- Modal: wellbeing detail WHO-5 --}}
    @if($assessment)
    <div x-show="wellbeing" x-cloak x-transition.opacity class="fixed inset-0 z-50 grid place-items-center bg-pine-950/50 p-4">
        <div @click.outside="wellbeing = false" x-show="wellbeing" x-transition.scale class="portal-card w-full max-w-md p-6">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-semibold text-pine-900">Wellbeing score kamu</h3>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $assessment->taken_at->translatedFormat('d F Y') }} · WHO-5</p>
                </div>
                <button @click="wellbeing = false" class="text-gray-400 hover:text-gray-700">✕</button>
            </div>
            <div class="mt-4 flex items-center gap-4">
                <div class="text-4xl font-bold {{ $levelClass }}">{{ $assessment->score }}</div>
                <div>
                    <span class="text-xs font-semibold rounded-full px-2.5 py-1 {{ $assessment->band() === 'Sehat' ? 'bg-pine-100 text-pine-800' : ($assessment->band() === 'Perlu Perhatian' ? 'bg-terra-100 text-terra-800' : 'bg-red-100 text-red-800') }}">{{ $assessment->band() }}</span>
                    <p class="text-[11px] text-gray-400 mt-1">dari 100</p>
                </div>
            </div>
            <div class="mt-5 space-y-3">
                @foreach($who5Items as $i => $item)
                @php $ans = $assessment->answers[$i] ?? 0; @endphp
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-gray-600">{{ $item }}</span>
                        <span class="font-medium text-gray-800">{{ $answerLabels[$ans] }}</span>
                    </div>
                    <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-full rounded-full {{ $ans >= 3 ? 'bg-pine-600' : ($ans >= 2 ? 'bg-terra-400' : 'bg-red-400') }}" style="width: {{ $ans / 5 * 100 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
            <p class="mt-5 text-xs leading-5 text-gray-500">Skor di bawah 50 menandakan kamu mungkin butuh dukungan. <a href="{{ route('perusahaan.booking') }}" class="text-pine-700 font-semibold hover:underline">Booking sesi konsultasi</a> — gratis lewat program kantor.</p>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
<script>
    const trend = @json($assessmentTrend);
    if (trend.length > 1 && document.getElementById('chartTrend')) {
        new Chart(document.getElementById('chartTrend'), {
            type: 'line',
            data: {
                labels: trend.map((t) => t.label),
                datasets: [{
                    data: trend.map((t) => t.score),
                    borderColor: '#007648',
                    backgroundColor: 'rgba(0, 118, 72, 0.12)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                    pointBackgroundColor: '#007648',
                }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: '#64748b', font: { size: 11 } }, grid: { display: false } },
                    y: { min: 0, max: 100, ticks: { color: '#64748b', font: { size: 11 } }, grid: { color: 'rgba(148, 163, 184, 0.18)' } },
                },
            },
        });
    }
</script>
@endpush
