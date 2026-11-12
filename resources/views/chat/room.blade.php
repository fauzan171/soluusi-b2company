@extends('layouts.app')

@php($title = 'Chat Konsultasi')

@section('content')
{{-- ponytail: polling 5s vanilla JS. Ganti ke SSE/broadcast kalau >50 sesi aktif bersamaan. --}}
<div id="chat" class="mx-auto max-w-2xl px-1"
     data-poll="{{ $pollUrl }}" data-post="{{ $postUrl }}" data-token="{{ csrf_token() }}"
     data-can-send="{{ $canSend ? 1 : 0 }}">

    <a href="{{ $backUrl }}" class="inline-flex items-center gap-1 text-sm text-pine-700 hover:text-pine-900 font-medium mb-3">← Kembali ke riwayat sesi</a>

    <div class="portal-card overflow-hidden">
        <header class="px-5 py-4 border-b border-[#e7e2d9] flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-pine-700 text-white font-semibold text-sm">
                    {{ strtoupper(mb_substr($peerLabel, 0, 1)) }}
                </span>
                <div class="min-w-0">
                    <h1 class="font-semibold text-pine-900 truncate">Chat {{ $peerLabel }}</h1>
                    <p class="text-xs text-gray-500">Sesi #{{ $session->id }} · {{ $session->topic_category ?? 'Konsultasi' }}</p>
                </div>
            </div>
            <span id="chat-status" class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-medium text-gray-500">
                <span class="h-1.5 w-1.5 rounded-full bg-gray-400" data-dot></span>menyambung…
            </span>
        </header>

        {{-- End session + rating --}}
        @if($isEmployee)
        <div class="px-5 py-3 border-b border-[#e7e2d9] bg-white">
            @if($session->status === 'scheduled')
            <form method="POST" action="{{ route('perusahaan.chat.end', $session) }}" class="flex items-center gap-3">
                @csrf
                <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-800">Akhiri sesi</button>
                <span class="text-xs text-gray-400">Chat akan ditutup setelah sesi diakhiri.</span>
            </form>
            @elseif($session->status === 'done' && !$session->rating)
            <form method="POST" action="{{ route('perusahaan.chat.rate', $session) }}" class="flex items-center gap-3">
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

        <div id="chat-log" class="px-4 py-4 sm:px-5 h-[55vh] sm:h-[60vh] overflow-y-auto space-y-2.5 text-sm bg-[#faf8f4]"></div>

        @if($canSend)
        <form id="chat-form" class="border-t border-[#e7e2d9] bg-white px-4 py-3 sm:px-5 flex items-end gap-2">
            <textarea id="chat-input" name="body" maxlength="2000" autocomplete="off" required rows="1"
                   class="flex-1 resize-none rounded-xl border border-[#e7e2d9] px-3.5 py-2.5 text-sm focus:outline-none focus:border-pine-400 focus:ring-2 focus:ring-pine-100"
                   placeholder="Tulis pesan…"></textarea>
            <button class="rounded-xl bg-pine-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-pine-800 transition-colors shrink-0">Kirim</button>
        </form>
        <p class="px-5 pb-3 text-[11px] text-gray-400 bg-white">Enter untuk kirim · Shift+Enter baris baru</p>
        @else
        <p class="border-t border-[#e7e2d9] px-5 py-3 text-xs text-gray-500 bg-white">Chat aktif setelah konsultan menyetujui jadwal sesi kamu.</p>
        @endif
    </div>
</div>

<script>
(() => {
    const root = document.getElementById('chat');
    const log = document.getElementById('chat-log');
    const form = document.getElementById('chat-form');
    const input = document.getElementById('chat-input');
    const status = document.getElementById('chat-status');
    const pollUrl = root.dataset.poll;
    const postUrl = root.dataset.post;
    const token = root.dataset.token;
    let lastId = 0, first = true;

    const esc = s => { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; };

    function add(m) {
        const wrap = document.createElement('div');
        wrap.className = 'flex ' + (m.mine ? 'justify-end' : 'justify-start');
        wrap.innerHTML = `<div class="max-w-[85%] sm:max-w-[75%] rounded-2xl px-3.5 py-2.5 shadow-sm ${m.mine
            ? 'bg-pine-700 text-white rounded-br-md'
            : 'bg-white border border-[#e7e2d9] text-gray-800 rounded-bl-md'}">
            ${m.mine ? '' : `<div class="text-[11px] font-semibold text-pine-700 mb-0.5">${esc(m.name)}</div>`}
            <div class="whitespace-pre-wrap break-words leading-relaxed">${esc(m.body)}</div>
            <div class="text-[10px] ${m.mine ? 'opacity-70' : 'text-gray-400'} mt-1 text-right">${esc(m.at)}</div>
        </div>`;
        log.appendChild(wrap);
        log.scrollTop = log.scrollHeight;
        if (m.id > lastId) lastId = m.id;
    }

    function setStatus(text, tone) {
        const dot = status.querySelector('[data-dot]');
        status.className = 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-medium ' +
            (tone === 'ok' ? 'bg-pine-100 text-pine-800' : tone === 'err' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500');
        dot.className = 'h-1.5 w-1.5 rounded-full ' + (tone === 'ok' ? 'bg-pine-600' : tone === 'err' ? 'bg-red-500' : 'bg-gray-400');
        status.childNodes[1].textContent = text;
    }

    async function poll() {
        try {
            const r = await fetch(pollUrl + (first ? '?after=0' : '?after=' + lastId), {
                headers: { 'Accept': 'application/json' }, credentials: 'same-origin'
            });
            if (!r.ok) throw new Error(r.status);
            const rows = await r.json();
            if (first) { log.innerHTML = ''; rows.length || (log.innerHTML = '<p class="text-center text-gray-400 py-6">Belum ada pesan. Sapa dulu.</p>'); }
            rows.forEach(add);
            first = false;
            setStatus('terhubung', 'ok');
        } catch (e) {
            setStatus('terputus, coba lagi…', 'err');
        }
    }

    async function send() {
        const body = input.value.trim();
        if (!body) return;
        input.value = '';
        input.style.height = 'auto';
        const r = await fetch(postUrl, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ body })
        });
        if (!r.ok) { setStatus('gagal kirim', 'err'); input.value = body; return; }
        await poll();
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

    poll();
    setInterval(poll, 5000);
})();
</script>
@endsection
