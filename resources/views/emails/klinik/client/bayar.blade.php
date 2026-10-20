@component('emails.klinik.client.partials.shell', ['title' => $brand . ' - Satu Langkah Lagi! Selesaikan Pembayaranmu'])
    <h1 style="font-size:18px;margin:0 0 12px;">Satu langkah lagi! 🎉</h1>
    <p style="margin:0 0 16px;line-height:1.6;">
        Kamu baru saja memesan sesi konsultasi. Selesaikan pembayaran untuk mengonfirmasi jadwalmu:
    </p>
    <table style="width:100%;font-size:14px;margin-bottom:20px;">
        <tr><td style="padding:6px 0;color:#8a948f;">Konsultan</td><td style="padding:6px 0;font-weight:600;">{{ trim(($session->consultant->user->firstname ?? '') . ' ' . ($session->consultant->user->lastname ?? '')) }}</td></tr>
        <tr><td style="padding:6px 0;color:#8a948f;">Paket</td><td style="padding:6px 0;font-weight:600;">{{ $session->consultantPackage->name ?? '-' }}</td></tr>
        @if($session->schedule)
        <tr><td style="padding:6px 0;color:#8a948f;">Jadwal</td><td style="padding:6px 0;font-weight:600;">{{ $session->schedule->started_at->translatedFormat('l, d M Y H:i') }} WIB</td></tr>
        @endif
        <tr><td style="padding:6px 0;color:#8a948f;">Total</td><td style="padding:6px 0;font-weight:600;">Rp{{ number_format((float) $session->amount, 0, ',', '.') }}</td></tr>
    </table>
    @if($session->url_snap)
    <a href="{{ $session->url_snap }}" style="display:inline-block;background:#0d7a5f;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:600;">Bayar Sekarang</a>
    <p style="margin:16px 0 0;font-size:12px;color:#8a948f;">Link pembayaran berlaku hingga {{ optional($session->expired_at)->translatedFormat('d M Y H:i') ?? '1 jam ke depan' }}.</p>
    @endif
@endcomponent
