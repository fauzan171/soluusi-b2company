@component('emails.klinik.client.partials.shell', ['title' => $brand . ' - Reminder! Konsultasimu 1 jam lagi ⏳'])
    <h1 style="font-size:18px;margin:0 0 12px;">Konsultasimu mulai 1 jam lagi ⏳</h1>
    <p style="margin:0 0 16px;line-height:1.6;">
        Bersiap ya! Sesi dengan {{ trim(($session->consultant->user->firstname ?? '') . ' ' . ($session->consultant->user->lastname ?? '')) }}
        @if($session->schedule)
            dimulai {{ $session->schedule->started_at->translatedFormat('H:i') }} WIB.
        @endif
    </p>
    @if($session->link_meeting)
    <a href="{{ $session->link_meeting }}" style="display:inline-block;background:#0d7a5f;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:600;">Gabung Sesi</a>
    @endif
@endcomponent
