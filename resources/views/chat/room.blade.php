@extends('layouts.app')

@section('content')
{{-- ponytail: polling 5s vanilla JS. Ganti ke SSE/broadcast kalau >50 sesi aktif bersamaan. --}}
<div id="chat" class="mx-auto max-w-2xl"
     data-poll="{{ $pollUrl }}" data-post="{{ $postUrl }}" data-token="{{ csrf_token() }}"
     data-can-send="{{ $canSend ? 1 : 0 }}">
    <div class="flex items-center justify-between mb-3">
        <div>
            <h1 class="font-semibold text-pine-900">Chat {{ $peerLabel }}</h1>
            <p class="text-xs text-gray-500">Sesi #{{ $session->id }} · {{ $session->topic_category ?? 'Konsultasi' }}</p>
        </div>
        <span id="chat-status" class="text-[11px] text-gray-400">menyambung…</span>
    </div>

    <div id="chat-log" class="portal-card p-4 h-[60vh] overflow-y-auto space-y-2 text-sm"></div>

    @if($canSend)
    <form id="chat-form" class="mt-3 flex gap-2">
        <input id="chat-input" name="body" maxlength="2000" autocomplete="off" required
               class="flex-1 rounded-xl border border-[#e7e2d9] px-3 py-2 text-sm focus:outline-none focus:border-pine-400"
               placeholder="Tulis pesan…">
        <button class="rounded-xl bg-pine-700 px-4 py-2 text-sm font-semibold text-white hover:bg-pine-800">Kirim</button>
    </form>
    @else
    <p class="mt-3 text-xs text-gray-500">Chat aktif saat sesi berstatus terjadwal.</p>
    @endif
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
        wrap.className = m.mine ? 'flex justify-end' : 'flex justify-start';
        wrap.innerHTML = `<div class="max-w-[80%] rounded-2xl px-3 py-2 ${m.mine ? 'bg-pine-700 text-white' : 'bg-pine-50 text-gray-800'}">
            ${m.mine ? '' : `<div class="text-[11px] font-semibold opacity-70 mb-0.5">${esc(m.name)}</div>`}
            <div class="whitespace-pre-wrap break-words">${esc(m.body)}</div>
            <div class="text-[10px] opacity-60 mt-1 text-right">${esc(m.at)}</div>
        </div>`;
        log.appendChild(wrap);
        log.scrollTop = log.scrollHeight;
        if (m.id > lastId) lastId = m.id;
    }

    async function poll() {
        try {
            const r = await fetch(pollUrl + (first ? '?after=0' : '?after=' + lastId), {
                headers: { 'Accept': 'application/json' }, credentials: 'same-origin'
            });
            if (!r.ok) throw new Error(r.status);
            const rows = await r.json();
            if (first) { log.innerHTML = ''; rows.length || (log.innerHTML = '<p class="text-gray-400">Belum ada pesan. Sapa dulu.</p>'); }
            rows.forEach(add);
            first = false;
            status.textContent = 'terhubung';
            status.className = 'text-[11px] text-pine-600';
        } catch (e) {
            status.textContent = 'terputus, coba lagi…';
            status.className = 'text-[11px] text-red-600';
        }
    }

    if (form) form.addEventListener('submit', async e => {
        e.preventDefault();
        const body = input.value.trim();
        if (!body) return;
        input.value = '';
        const r = await fetch(postUrl, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ body })
        });
        if (!r.ok) { status.textContent = 'gagal kirim'; input.value = body; return; }
        await poll();
    });

    poll();
    setInterval(poll, 5000);
})();
</script>
@endsection