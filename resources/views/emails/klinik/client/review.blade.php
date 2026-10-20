@component('emails.klinik.client.partials.shell', ['title' => $brand . ' - Terima Kasih! Bagikan Ulasanmu 💚'])
    <h1 style="font-size:18px;margin:0 0 12px;">Terima kasih! Bagaimana sesimu? 💚</h1>
    <p style="margin:0 0 16px;line-height:1.6;">
        Sesi konsultasimu dengan {{ trim(($consultationSession->consultant->user->firstname ?? '') . ' ' . ($consultationSession->consultant->user->lastname ?? '')) }} telah selesai.
        Ulasanmu membantu kami meningkatkan layanan.
    </p>
    @if($consultationSession->checkoutLink)
    <a href="{{ route('checkout.status', $consultationSession->checkoutLink->token) }}" style="display:inline-block;background:#0d7a5f;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:600;">Beri Ulasan</a>
    @endif
@endcomponent
