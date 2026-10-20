@component('emails.klinik.client.partials.shell', ['title' => $brand . ' - Terima Kasih! Catatan Hasil Konsultasi Kamu Sudah Selesai ✅'])
    <h1 style="font-size:18px;margin:0 0 12px;">Catatan hasil konsultasimu sudah selesai ✅</h1>
    <p style="margin:0 0 16px;line-height:1.6;">
        Konsultanmu telah menyelesaikan catatan dari sesi konsultasimu.
    </p>
    @if(!empty($session->solution))
    <div style="background:#f4f6f5;border-radius:8px;padding:16px;font-size:14px;line-height:1.6;">
        {!! nl2br(e($session->solution)) !!}
    </div>
    @endif
    <p style="margin:16px 0 0;font-size:13px;color:#8a948f;">
        Catatan ini bersifat rahasia dan hanya dikirim ke kamu.
    </p>
@endcomponent
