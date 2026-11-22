@extends('layouts.app')

@php
    $title = 'Chat Konsultasi';
    // Vertical-agnostic action URLs — default perusahaan (CAP), controller SAP override.
    $endUrl = $endUrl ?? route('perusahaan.chat.end', $session);
    $rateUrl = $rateUrl ?? route('perusahaan.chat.rate', $session);
    $startUrl = $startUrl ?? route('consultant.chat.start', $session);
@endphp

@section('content')
{{-- ponytail: polling adaptif (3s fokus/10s blur) vanilla JS. Ganti ke SSE/broadcast kalau >50 sesi aktif bersamaan. --}}
<div id="chat" class="mx-auto max-w-2xl px-1"
     data-poll="{{ $pollUrl }}" data-post="{{ $postUrl }}" data-token="{{ csrf_token() }}"
     data-can-send="{{ $canSend ? 1 : 0 }}" data-status="{{ $session->status }}"
     data-purged="{{ !empty($transcriptPurged) ? 1 : 0 }}">

    <a href="{{ $backUrl }}" class="inline-flex items-center gap-1 text-sm text-pine-700 hover:text-pine-900 font-medium mb-3">← Kembali ke riwayat sesi</a>

    <div class="portal-card overflow-hidden flex flex-col h-[75vh]">
        @if(!empty($sessionSummary))
        <div class="px-5 py-4 border-b border-[#e7e2d9] bg-pine-50/50 shrink-0">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-pine-700 mb-1">
                {{ $isEmployee ? 'Saran dari sesi kamu' : 'Ringkasan sesi (AI)' }}
            </p>
            <p class="text-sm text-gray-800 whitespace-pre-wrap leading-relaxed">{{ $sessionSummary }}</p>
        </div>
        @endif
        <header class="px-5 py-4 border-b border-[#e7e2d9] flex items-center justify-between gap-3 shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-pine-700 text-white font-semibold text-sm">
                    {{ strtoupper(mb_substr($peerLabel, 0, 1)) }}
                </span>
                <div class="min-w-0">
                    <h1 class="font-semibold text-pine-900 truncate">Chat {{ $peerLabel }}</h1>
                    <p class="text-xs text-gray-500">Sesi #{{ $session->id }} · {{ $session->topic_category ?? 'Konsultasi' }}</p>
                </div>
            </div>
            <span id="chat-status" class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-medium text-gray-500 shrink-0">
                <span class="h-1.5 w-1.5 rounded-full bg-gray-400" data-dot></span><span data-label>menyambung…</span>
            </span>
        </header>

        {{-- Keluhan awal: hanya konsultan yang membaca (HR/BK tidak pernah).
             Ini konteks yang diminta karyawan saat mengajukan konsultasi. --}}
        @if(!$isEmployee && !empty($session->request_notes))
        <details class="px-5 py-3 border-b border-[#e7e2d9] bg-amber-50/50 shrink-0" open>
            <summary class="text-xs font-semibold text-pine-900 cursor-pointer">Cerita awal dari {{ $peerLabel }} (rahasia)</summary>
            <p class="mt-2 text-sm text-gray-800 whitespace-pre-wrap leading-relaxed">{{ $session->request_notes }}</p>
        </details>
        @endif

        {{-- End session + rating --}}
        @if($isEmployee)
        <div class="px-5 py-3 border-b border-[#e7e2d9] bg-white shrink-0">
            @if($session->status === 'scheduled' && $session->scheduled_at)
            <form method="POST" action="{{ $endUrl }}" class="flex items-center gap-3" onsubmit="return confirm('Akhiri sesi ini? Chat akan ditutup.');">
                @csrf
                <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-800">Akhiri sesi</button>
                <span class="text-xs text-gray-400">Chat akan ditutup setelah sesi diakhiri.</span>
            </form>
            @elseif($session->status === 'done' && !$session->rating)
            <form method="POST" action="{{ $rateUrl }}" class="flex items-center gap-3">
                @csrf
                <label class="text-sm text-gray-600">Rating:</label>
                <select name="rating" class="rounded-lg border border-[#e7e2d9] px-2 py-1 text-sm">
                    <option value="5">★★★★★</option>
                    <option value="4">★★★★☆</option>
                    <option value="3">★★★☆☆</option>
                    <option value="2">★★☆☆☆</option>
                    <option value="1">★☆☆☆☆</option>
                </select>
                <button type="submit" class="rounded-lg bg-pine-700 px-3 py-1 text-sm font-semibold text-white hover:bg-pine-800">Kirim</button>
            </form>
            @elseif($session->status === 'done' && $session->rating)
            <p class="text-sm text-gray-500">Sesi selesai. Rating: {{ str_repeat('★', $session->rating) . str_repeat('☆', 5 - $session->rating) }}</p>
            @endif
        </div>
        @endif
        @if(!$isEmployee)
        <div class="px-5 py-3 border-b border-[#e7e2d9] bg-white flex flex-wrap items-center gap-3 shrink-0">
            @if($session->status === 'scheduled' && $session->scheduled_at)
                @if(!$session->checked_in_at)
                <form method="POST" action="{{ $startUrl }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-pine-700 px-3 py-1.5 text-sm font-semibold text-white hover:bg-pine-800">Mulai sesi</button>
                </form>
                @endif
                <form method="POST" action="{{ $endUrl }}" class="flex items-center gap-3" onsubmit="return confirm('Akhiri sesi ini? Chat akan ditutup.');">
                    @csrf
                    <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-800">Akhiri sesi</button>
                </form>
            @else
                <span class="text-sm text-gray-500">Sesi selesai.</span>
            @endif
        </div>
        @endif

        {{-- W5 (K10): tombol join meeting — hanya saat sesi scheduled dan dalam
             jendela waktu (15 menit sebelum jadwal sampai selesai sesi). --}}
        @php($inWindow = $session->status === 'scheduled' && $session->scheduled_at
            && now()->gte($session->scheduled_at->copy()->subMinutes(15))
            && now()->lte($session->ended_at ?? $session->scheduled_at->copy()->addHour()))
        @if($inWindow && $session->meeting_url)
        <div class="px-5 py-3 border-b border-[#e7e2d9] bg-pine-50 flex flex-wrap items-center gap-3">
            <a href="{{ $session->meeting_url }}" target="_blank" rel="noopener noreferrer"
               class="rounded-lg bg-pine-700 px-4 py-2 text-sm font-semibold text-white hover:bg-pine-800 transition-colors">
                Join meeting
            </a>
            <span class="text-xs text-pine-800">Link video call sesi ini sudah aktif.</span>
        </div>
        @endif

        @if(!empty($transcriptPurged))
        <p class="border-t border-[#e7e2d9] px-5 py-3 text-xs text-gray-500 bg-white">
            Transkrip chat sesi ini sudah dihapus otomatis 24 jam setelah sesi selesai (kebijakan retensi privasi). Ringkasan sesi tetap tersimpan.
        </p>
        @endif

        {{-- W7 (K14): catatan klinis konsultan — konsultan-only, encrypted, tiap
             akses halaman (bukan poll) ter-audit (note.view). --}}
        @if(!$isEmployee)
        @php($note = $session->note)
        <div class="px-5 py-4 border-b border-[#e7e2d9] bg-pine-50/50" x-data="{ open: {{ $note ? 'false' : 'true' }} }">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-pine-900 flex items-center gap-2">
                    Catatan sesi <span class="text-[10px] font-semibold uppercase tracking-wide rounded-full bg-pine-100 text-pine-800 px-2 py-0.5">Rahasia</span>
                </h2>
                <button type="button" @click="open = !open" class="text-xs font-medium text-pine-700 hover:underline">
                    <span x-text="open ? 'Tutup' : 'Buka'"></span>
                </button>
            </div>
            <div x-show="open" x-cloak class="mt-3 space-y-2">
                <form method="POST" action="{{ route('consultant.notes.store', $session) }}" class="space-y-2">
                    @csrf
                    <textarea name="body" rows="3" required maxlength="10000"
                              placeholder="Catatan klinis sesi ini (tersimpan terenkripsi, hanya kamu yang bisa baca)…"
                              class="w-full rounded-xl border border-[#e7e2d9] px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:border-pine-400 focus:ring-2 focus:ring-pine-100">{{ old('body', $note?->body) }}</textarea>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-[11px] text-gray-400">Enkripsi at-rest · akses tercatat di audit log</span>
                        <button type="submit" class="rounded-xl bg-pine-700 px-4 py-2 text-sm font-semibold text-white hover:bg-pine-800 transition-colors">
                            {{ $note ? 'Perbarui catatan' : 'Simpan catatan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- W7 (K14): hasil sesi — ringkasan + rekomendasi (karyawan lihat setelah sesi selesai). --}}
        @php($result = $session->result)
        <div class="px-5 py-4 border-b border-[#e7e2d9]" x-data="{ open: {{ $result ? 'false' : 'true' }} }">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-pine-900">Hasil sesi</h2>
                <button type="button" @click="open = !open" class="text-xs font-medium text-pine-700 hover:underline">
                    <span x-text="open ? 'Tutup' : 'Buka'"></span>
                </button>
            </div>
            <div x-show="open" x-cloak class="mt-3 space-y-2">
                <form method="POST" action="{{ route('consultant.results.store', $session) }}" class="space-y-2">
                    @csrf
                    <textarea name="summary" rows="2" required maxlength="10000"
                              placeholder="Ringkasan sesi untuk karyawan…"
                              class="w-full rounded-xl border border-[#e7e2d9] px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:border-pine-400 focus:ring-2 focus:ring-pine-100">{{ old('summary', $result?->summary) }}</textarea>
                    <textarea name="recommendation" rows="2" maxlength="10000"
                              placeholder="Rekomendasi / langkah lanjutan…"
                              class="w-full rounded-xl border border-[#e7e2d9] px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:border-pine-400 focus:ring-2 focus:ring-pine-100">{{ old('recommendation', $result?->recommendation) }}</textarea>
                    <div class="flex items-center justify-end gap-3">
                        <button type="submit" class="rounded-xl bg-pine-700 px-4 py-2 text-sm font-semibold text-white hover:bg-pine-800 transition-colors">
                            {{ $result ? 'Perbarui hasil' : 'Simpan hasil' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- W7: rating agregat karyawan untuk sesi-sesi kamu di perusahaan ini — komentar individu tidak pernah dilihat konsultan. --}}
        @if($session->status === 'done')
        <div class="px-5 py-3 border-b border-[#e7e2d9] bg-white flex flex-wrap items-center gap-2 text-sm">
            <span class="text-gray-500">Ulasan karyawan:</span>
            @php($agg = \App\Models\Perusahaan\PerusahaanSession::ratingAggregate($session->consultant_id, $session->perusahaan_id))
            @if($agg['count'])
            <span class="font-semibold text-amber-500">{{ str_repeat('★', (int) round($agg['avg'])) . str_repeat('☆', 5 - (int) round($agg['avg'])) }}</span>
            <span class="text-gray-600">{{ $agg['avg'] }} / 5 · {{ $agg['count'] }} ulasan di {{ $session->perusahaan?->brand }}</span>
            @else
            <span class="text-gray-400">Belum ada ulasan.</span>
            @endif
        </div>
        @endif
        @endif

        {{-- W7 (K14): hasil sesi — sisi karyawan, hanya setelah sesi selesai. --}}
        @if($isEmployee && $session->status === 'done')
        @php($result = $session->result)
        @php($review = $session->review)
        @php($agg = \App\Models\Perusahaan\PerusahaanSession::ratingAggregate($session->consultant_id, $session->perusahaan_id))
        <div class="px-5 py-4 border-b border-[#e7e2d9] bg-pine-50/50 space-y-3">
            <h2 class="text-sm font-semibold text-pine-900">Hasil sesi kamu</h2>
            @if($result)
            <div class="text-sm text-gray-700 space-y-2">
                <p><span class="font-medium text-ink">Ringkasan:</span> {{ $result->summary }}</p>
                @if($result->recommendation)
                <p><span class="font-medium text-ink">Rekomendasi:</span> {{ $result->recommendation }}</p>
                @endif
            </div>
            @else
            <p class="text-sm text-gray-400">Konsultan belum menuliskan ringkasan sesi.</p>
            @endif

            {{-- Ulasan: 1x per sesi, hanya setelah done. --}}
            @if(!$review)
            <form method="POST" action="{{ route('perusahaan.reviews.store', $session) }}" class="pt-2 space-y-2">
                @csrf
                <label class="block text-xs text-gray-600">Beri ulasan untuk konsultan:</label>
                <div class="flex items-center gap-2 flex-wrap">
                    <select name="rating" required class="rounded-lg border border-[#e7e2d9] px-2 py-1.5 text-sm bg-white">
                        <option value="5">★★★★★ Sangat membantu</option>
                        <option value="4">★★★★☆ Membantu</option>
                        <option value="3">★★★☆☆ Cukup</option>
                        <option value="2">★★☆☆☆ Kurang</option>
                        <option value="1">★☆☆☆☆ Tidak membantu</option>
                    </select>
                    <input type="text" name="comment" maxlength="2000" placeholder="Komentar (opsional, privat)"
                           class="flex-1 min-w-[200px] rounded-lg border border-[#e7e2d9] px-3 py-1.5 text-sm bg-white focus:outline-none focus:border-pine-400">
                    <button type="submit" class="rounded-lg bg-pine-700 px-3 py-1.5 text-sm font-semibold text-white hover:bg-pine-800">Kirim ulasan</button>
                </div>
                <p class="text-[11px] text-gray-400">Ulasan anonim bagi konsultan — mereka hanya melihat rata-rata, bukan komentar kamu.</p>
            </form>
            @else
            <p class="text-xs text-gray-500 pt-1">Ulasan kamu: {{ str_repeat('★', $review->rating) . str_repeat('☆', 5 - $review->rating) }} · terima kasih sudah memberi feedback.</p>
            @endif
        </div>
        @endif

        {{-- F7: load-more riwayat >200 pesan; server set visibilitas via has_more. --}}
        <div class="shrink-0 border-b border-[#e7e2d9] bg-white text-center py-1">
            <button id="chat-older" type="button" class="hidden text-xs font-medium text-pine-700 hover:underline">Muat pesan lama</button>
        </div>
        <div id="chat-log" class="flex-1 min-h-0 px-4 py-4 sm:px-5 overflow-y-auto space-y-2.5 text-sm bg-[#faf8f4]"></div>

        <div id="chat-composer" class="shrink-0">
            @if($canSend)
            <form id="chat-form" class="border-t border-[#e7e2d9] bg-white px-4 py-3 sm:px-5 flex items-end gap-2">
                <textarea id="chat-input" name="body" maxlength="2000" autocomplete="off" required rows="1"
                       class="flex-1 resize-none rounded-xl border border-[#e7e2d9] px-3.5 py-2.5 text-sm focus:outline-none focus:border-pine-400 focus:ring-2 focus:ring-pine-100"
                       placeholder="Tulis pesan…"></textarea>
                <button id="chat-send" class="rounded-xl bg-pine-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-pine-800 transition-colors shrink-0 disabled:opacity-50 disabled:cursor-not-allowed">Kirim</button>
            </form>
            <p class="px-5 pb-3 text-[11px] text-gray-400 bg-white">Enter untuk kirim · Shift+Enter baris baru</p>
            @else
            <p id="chat-closed-note" class="border-t border-[#e7e2d9] px-5 py-3 text-xs text-gray-500 bg-white">Chat aktif setelah konsultan menyetujui jadwal sesi kamu.</p>
            @endif
        </div>
    </div>
</div>

<script>
(() => {
    const root = document.getElementById('chat');
    const log = document.getElementById('chat-log');
    const form = document.getElementById('chat-form');
    const input = document.getElementById('chat-input');
    const sendBtn = document.getElementById('chat-send');
    const status = document.getElementById('chat-status');
    const composer = document.getElementById('chat-composer');
    const pollUrl = root.dataset.poll;
    const postUrl = root.dataset.post;
    const token = root.dataset.token;
    let lastId = 0, first = true, sending = false, timer = null;
    let hasMore = false, loadingOlder = false;
    let peerReadUpto = 0;
    let delay = 5000; // backoff: 5s → 10s → 30s cap, reset on success
    let terminal = ['done', 'cancelled', 'no_show'].includes(root.dataset.status);
    const terminalAtLoad = terminal;

    const esc = s => { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; };

    function add(m) {
        const wrap = document.createElement('div');
        wrap.className = 'flex ' + (m.mine ? 'justify-end' : 'justify-start');
        wrap.innerHTML = `<div class="max-w-[85%] sm:max-w-[75%] rounded-2xl px-3.5 py-2.5 shadow-sm ${m.mine
            ? 'bg-pine-700 text-white rounded-br-md'
            : 'bg-white border border-[#e7e2d9] text-gray-800 rounded-bl-md'}">
            ${m.mine ? '' : `<div class="text-[11px] font-semibold text-pine-700 mb-0.5">${esc(m.name)}</div>`}
            <div class="whitespace-pre-wrap break-words leading-relaxed">${esc(m.body)}</div>
            <div class="text-[10px] ${m.mine ? 'opacity-70' : 'text-gray-400'} mt-1 text-right">${esc(m.at)}<span data-tick="${m.id}" class="ml-1">${m.mine && m.read ? '✓✓' : ''}</span></div>
        </div>`;
        log.appendChild(wrap);
        log.scrollTop = log.scrollHeight;
        if (m.id > lastId) lastId = m.id;
    }

    // F7: prepend untuk load-more — sisip sebelum anak pertama, tanpa auto-scroll.
    function prepend(m) {
        const wrap = document.createElement('div');
        wrap.className = 'flex ' + (m.mine ? 'justify-end' : 'justify-start');
        wrap.innerHTML = `<div class="max-w-[85%] sm:max-w-[75%] rounded-2xl px-3.5 py-2.5 shadow-sm ${m.mine
            ? 'bg-pine-700 text-white rounded-br-md'
            : 'bg-white border border-[#e7e2d9] text-gray-800 rounded-bl-md'}">
            ${m.mine ? '' : `<div class="text-[11px] font-semibold text-pine-700 mb-0.5">${esc(m.name)}</div>`}
            <div class="whitespace-pre-wrap break-words leading-relaxed">${esc(m.body)}</div>
            <div class="text-[10px] ${m.mine ? 'opacity-70' : 'text-gray-400'} mt-1 text-right">${esc(m.at)}<span data-tick="${m.id}" class="ml-1">${m.mine && m.read ? '✓✓' : ''}</span></div>
        </div>`;
        log.insertBefore(wrap, log.firstChild);
    }

    // D.5: tick "dibaca" untuk pesan sendiri. Server kirim id tertinggi yang
    // sudah dibaca lawan; semua pesan sendiri <= id itu bertanda ✓✓.
    function applyReadUpto(upto) {
        if (!upto || upto <= peerReadUpto) return;
        peerReadUpto = upto;
        log.querySelectorAll('[data-tick]').forEach(el => {
            if (Number(el.dataset.tick) <= upto) el.textContent = '✓✓';
        });
    }

    function setStatus(text, tone) {
        const dot = status.querySelector('[data-dot]');
        const label = status.querySelector('[data-label]');
        status.className = 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-medium shrink-0 ' +
            (tone === 'ok' ? 'bg-pine-100 text-pine-800' : tone === 'err' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500');
        dot.className = 'h-1.5 w-1.5 rounded-full ' + (tone === 'ok' ? 'bg-pine-600' : tone === 'err' ? 'bg-red-500' : 'bg-gray-400');
        label.textContent = text;
    }

    function closeSession() {
        terminal = true;
        if (timer) { clearTimeout(timer); timer = null; }
        setStatus('sesi selesai', '');
        if (composer) {
            composer.innerHTML = '<p class="border-t border-[#e7e2d9] px-5 py-3 text-xs text-gray-500 bg-white">Sesi selesai. Chat ditutup.</p>';
        }
        // D.6: sesi berakhir saat room terbuka → muat ulang sekali supaya form
        // rating (render server) muncul. Tidak reload bila sudah terminal saat load.
        if (!terminalAtLoad) location.reload();
    }

    async function poll() {
        if (terminal) return;
        try {
            const r = await fetch(pollUrl + (first ? '?after=0' : '?after=' + lastId), {
                headers: { 'Accept': 'application/json' }, credentials: 'same-origin'
            });
            if (!r.ok) throw new Error(r.status);
            const data = await r.json();
            const rows = data.messages || [];
            if (first) { log.innerHTML = ''; rows.length || (log.innerHTML = root.dataset.purged === '1'
                ? '<p class="text-center text-gray-400 py-6">Transkrip sudah dihapus sesuai kebijakan retensi.</p>'
                : '<p class="text-center text-gray-400 py-6">Belum ada pesan. Sapa dulu.</p>'); }
            rows.forEach(add);
            applyReadUpto(data.peer_read_upto);
            if (first) log.scrollTop = log.scrollHeight;
            first = false;
            delay = 5000;
            // F7: tampilkan tombol load-more. Poll tanpa pesan baru mengirim
            // has_more=false → jangan sampai menutup tombol yang sudah tampil;
            // penyembunyian final hanya oleh loadOlder (habis riwayat).
            hasMore = !!data.has_more;
            const olderBtn = document.getElementById('chat-older');
            if (olderBtn && hasMore && !loadingOlder) olderBtn.classList.remove('hidden');
            if (data.status && ['done', 'cancelled', 'no_show'].includes(data.status)) {
                closeSession();
                return;
            }
            setStatus(data.can_send ? 'sesi berlangsung' : 'terhubung', 'ok');
        } catch (e) {
            delay = Math.min(delay * 2, 30000);
            setStatus('terputus, coba lagi…', 'err');
        }
        if (!terminal) timer = setTimeout(poll, delay);
    }

    // F7: load-more riwayat lama — ambil 200 pesan sebelum id terkecil di log,
    // prepend, jaga scroll tidak lompat.
    async function loadOlder() {
        const ids = [...log.querySelectorAll('[data-tick]')].map(el => Number(el.dataset.tick));
        const oldest = ids.length ? Math.min(...ids) : null;
        if (!oldest || loadingOlder) return;
        loadingOlder = true;
        const btn = document.getElementById('chat-older');
        if (btn) btn.disabled = true;
        const keep = log.scrollHeight - log.scrollTop;
        try {
            const r = await fetch(pollUrl + '?before_id=' + oldest, {
                headers: { 'Accept': 'application/json' }, credentials: 'same-origin'
            });
            if (!r.ok) throw new Error(r.status);
            const data = await r.json();
            const rows = (data.messages || []).slice().reverse();
            for (const m of rows) prepend(m);
            log.scrollTop = log.scrollHeight - keep;
            hasMore = !!data.has_more;
        } catch (e) {
            setStatus('gagal muat riwayat', 'err');
        } finally {
            loadingOlder = false;
            if (btn) {
                btn.disabled = false;
                btn.classList.toggle('hidden', !hasMore);
            }
        }
    }

    async function send() {
        const body = input.value.trim();
        if (!body || sending || terminal) return;
        sending = true;
        sendBtn.disabled = true;
        input.value = '';
        input.style.height = 'auto';
        try {
            const r = await fetch(postUrl, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ body })
            });
            if (!r.ok) { setStatus('gagal kirim', 'err'); input.value = body; return; }
            await poll();
        } finally {
            sending = false;
            if (sendBtn) sendBtn.disabled = terminal;
        }
    }

    if (form) {
        form.addEventListener('submit', e => { e.preventDefault(); send(); });
        input.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
        });
        input.addEventListener('input', () => {
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 120) + 'px';
        });
    }

    // F7: tombol muat riwayat lama.
    const olderBtn = document.getElementById('chat-older');
    if (olderBtn) olderBtn.addEventListener('click', loadOlder);

    if (terminal) {
        setStatus('sesi selesai', '');
    }
    poll();
    // W5 (K11): polling adaptif — 3s saat tab terlihat, 10s saat blur.
    let timer = null;
    const arm = () => {
        if (timer) clearInterval(timer);
        timer = setInterval(poll, document.visibilityState === 'visible' ? 3000 : 10000);
    };
    arm();
    document.addEventListener('visibilitychange', arm);
})();
</script>
@endsection

