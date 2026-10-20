<!DOCTYPE html>
<html>
    <head>
        <title>Soluusi - Jadwal Konsultasi Baru! Bersiaplah untuk Sesi dengan Klien 🎯</title>
    </head>
    <body>
        <div style="background-color: #ffffff; margin: 0; padding: 0; font-family: 'Poppins', Arial, Helvetica, sans-serif;">
            <table align="left" width="100%" cellspacing="0" cellpadding="0" style="background: #fff; min-width: 320px; max-width: 650px; margin: 0 auto">
                <tbody>
                    <tr>
                        <td align="left" style="padding: 30px 20px;">
                            <img src="https://soluusi.com/assets/media/email/logo.png?v=32025" width="150" alt="Soluusi Logo" style="margin: 0;">
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="font-size: 24px; font-weight: bold; color: #000; padding: 30px 20px;">
                            Jadwal Konsultasi Baru! Bersiaplah untuk Sesi dengan Klien 🎯
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="font-size: 16px; color: #000; padding: 30px 20px;">
                            Hai <strong>{{ $session->consultant->user->firstname . ' ' . $session->consultant->user->lastname }}</strong>, kabar baik! 🎉 Ada klien yang telah berhasil melakukan pembayaran dan menjadwalkan sesi konsultasi denganmu. Yuk, persiapkan dirimu untuk sesi ini!

                            <br><br>

                            <strong>Nama Klien:</strong> {{ $session->user->firstname . ' ' . $session->user->lastname }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 50px 20px; border-top: 1px solid #dedede;">
                            <table width="100%" align="left" cellspacing="0" cellpadding="0">
                                <tbody>
                                    <tr>
                                        <td align="left" style="font-size: 24px; font-weight: 600; color: #222; padding-bottom: 20px;">
                                            📌 Detail Konsultasi
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 16px; padding: 5px 20px;">
                                            <strong>Nama Klien:</strong> {{ $session->user->firstname . ' ' . $session->user->lastname }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 16px; padding: 5px 20px;">
                                            <strong>Layanan:</strong> {{ $session->consultantPackage->name }}

                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 16px; padding: 5px 20px;">
                                            <strong>Durasi:</strong> {{ $session->consultantPackage->duration }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 16px; padding: 5px 20px;">
                                            <strong>Jadwal:</strong> 
                                            @if($session->schedule && $session->schedule->started_at && $session->schedule->ended_at)
                                                {{ \Carbon\Carbon::parse($session->schedule->started_at)->format('d F Y H:i') }} - {{ \Carbon\Carbon::parse($session->schedule->ended_at)->format('H:i') }} WIB
                                            @else
                                                Belum ditentukan
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="left" style="font-size: 24px; font-weight: 600; color: #222; padding: 30px 20px;">
                                            💳 Konfirmasi Pembayaran
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 16px; padding: 5px 20px;">
                                            <strong>Nomor Invoice:</strong> {{ $session->order_number }}

                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 16px; padding: 5px 20px;">
                                            <strong>Tanggal Pembayaran:</strong> 
                                            @if($session->payment_at)
                                                {{ \Carbon\Carbon::parse($session->payment_at)->format('d F Y H:i') }}
                                            @else
                                                Belum dibayar
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 16px; font-weight: 600; padding: 10px 20px; border-top: 1px solid #dedede;">
                                            <strong>Total:</strong> {{ $session->payment }}

                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px; border-top: 1px solid #dedede;">
                            <p style="font-size: 16px; color: #000; ">
                                📅 Konsultasi ini telah dijadwalkan melalui Google Calendar, dan kamu akan menerima pemberitahuan melalui email. Pastikan untuk mempersiapkan diri dengan baik! 💚
                            </p>
                            <ul style="font-size: 16px; color: #000;  padding-left: 0; list-style: none;">
                                <li>🟢 Jika sesi <strong>online</strong>, pastikan koneksi stabil. Link Google Meet tersedia di Google Calendar.</li>
                                <li>🟢 Jika sesi <strong>offline</strong>, harap konfirmasi lokasi sebelum pertemuan. Link lokasi juga tersedia di Google Calendar.</li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="font-size: 16px; color: #000; padding: 20px;">
                            Jika ada kendala atau informasi tambahan yang diperlukan, silakan hubungi Luusi melalui 
                            <a href="https://api.whatsapp.com/send?phone=6285117674837" style="color: #007648; text-decoration: none; font-weight: 600;" target="_blank">soluusi.com/wa</a>
                            atau lewat
                            <a href="mailto:support@soluusi.com" style="color: #007648; text-decoration: none; font-weight: 600;">support@soluusi.com</a>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="font-size: 16px; color: #000; padding: 20px;">
                            Terima kasih sudah menjadi bagian dari Soluusi! Semoga sesi ini berjalan lancar dan bermanfaat. 😊
                        </td>
                    </tr>
                    <x-email.footer/>
                </tbody>
            </table>
        </div>
    </body>
</html>
