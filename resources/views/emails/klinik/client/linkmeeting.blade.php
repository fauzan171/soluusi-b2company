@component('emails.klinik.client.partials.shell', ['title' => $brand . ' - Link Sesi Konsultasimu'])
    <h1 style="font-size:18px;margin:0 0 12px;">Link sesi konsultasimu sudah siap</h1>
    <p style="margin:0 0 16px;line-height:1.6;">
        Sesi dengan {{ trim(($consultationSession->consultant->user->firstname ?? '') . ' ' . ($consultationSession->consultant->user->lastname ?? '')) }}
        @if($consultationSession->schedule)
            pada {{ $consultationSession->schedule->started_at->translatedFormat('l, d M Y H:i') }} WIB
        @endif
    </p>
    @if($consultationSession->link_meeting)
    <a href="{{ $consultationSession->link_meeting }}" style="display:inline-block;background:#0d7a5f;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:600;">Gabung via Google Meet</a>
    @endif
@endcomponent
