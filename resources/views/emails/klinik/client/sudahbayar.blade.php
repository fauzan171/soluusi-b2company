@component('emails.klinik.client.partials.shell', ['title' => $brand . ' - Pembayaran Berhasil! 🎉'])
    <h1 style="font-size:18px;margin:0 0 12px;">Pembayaran berhasil! 🎉</h1>
    <p style="margin:0 0 16px;line-height:1.6;">
        Sesi konsultasimu sudah terkonfirmasi. Detail sesi:
    </p>
    <table style="width:100%;font-size:14px;margin-bottom:20px;">
        <tr><td style="padding:6px 0;color:#8a948f;">Konsultan</td><td style="padding:6px 0;font-weight:600;">{{ trim(($session->consultant->user->firstname ?? '') . ' ' . ($session->consultant->user->lastname ?? '')) }}</td></tr>
        <tr><td style="padding:6px 0;color:#8a948f;">Paket</td><td style="padding:6px 0;font-weight:600;">{{ $session->consultantPackage->name ?? '-' }}</td></tr>
        @if($session->schedule)
        <tr><td style="padding:6px 0;color:#8a948f;">Jadwal</td><td style="padding:6px 0;font-weight:600;">{{ $session->schedule->started_at->translatedFormat('l, d M Y H.i') }} WIB</td></tr>
        @endif
        <tr><td style="padding:6px 0;color:#8a948f;">No. Order</td><td style="padding:6px 0;font-weight:600;">{{ $session->order_number }}</td></tr>
    </table>
    @if($session->link_meeting)
    <a href="{{ $session->link_meeting }}" style="display:inline-block;background:#0d7a5f;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:600;">Gabung Sesi (Google Meet)</a>
    @endif
    @if($session->checkoutLink)
    <p style="margin:16px 0 0;font-size:13px;">
        Lihat status sesi kamu: <a href="{{ route('checkout.status', $session->checkoutLink->token) }}" style="color:#0d7a5f;">halaman status</a>
    </p>
    @endif
@endcomponent
