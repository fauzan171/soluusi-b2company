<!DOCTYPE html>
<html>
    <head>
        <title>Soluusi - Jadwal Konsultasi bersama {{ $consultationSession->customer_display_name }}</title>
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
                            Jadwal Konsultasi dengan <strong>{{ $consultationSession->customer_display_name }}</strong> Sudah Siap! 📅
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="font-size: 16px; color: #000; padding: 0 20px;">
                            Hai <strong>{{ $consultationSession->consultant->user->firstname . ' ' . $consultationSession->consultant->user->lastname }}</strong>, jadwal konsultasimu bersama <strong>{{ $consultationSession->customer_display_name }}</strong> telah terkonfirmasi! Berikut detail sesi konsultasimu:
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
                                            <strong>Paket Konsultasi:</strong> {{ $consultationSession->consultantPackage->name }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 16px; padding: 5px 20px;">
                                            <strong>Jadwal:</strong> 
                                            @if($consultationSession->schedule && $consultationSession->schedule->started_at && $consultationSession->schedule->ended_at)
                                                {{ \Carbon\Carbon::parse($consultationSession->schedule->started_at)->format('d F Y, H:i') }}
                                                - {{ \Carbon\Carbon::parse($consultationSession->schedule->ended_at)->format('d F Y, H:i') }} (WIB)
                                            @else
                                                Belum ditentukan
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 16px; padding: 5px 20px;">
                                            <strong>Google Calendar:</strong> <a href="https://calendar.google.com/" style="color: #007648; text-decoration: none;">Lihat di Google Calendar</a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 16px; padding: 5px 20px;">
                                            <strong>Google Meet:</strong> <a href="{{ $consultationSession->schedule->link_meeting }}" style="color: #007648; text-decoration: none;">Gabung ke Meeting</a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="padding: 20px;">
                            <a href="{{ $consultationSession->schedule->link_meeting }}" 
                                style="
                                    display: inline-block;
                                    background-color: #007648;
                                    color: #fff;
                                    font-size: 16px;
                                    font-weight: 600;
                                    text-decoration: none;
                                    padding: 12px 24px;
                                    border-radius: 8px;
                                    transition: background-color 0.3s ease-in-out;
                                "
                                onmouseover="this.style.backgroundColor='#003B24'"
                                onmouseout="this.style.backgroundColor='#007648'">
                                Gabung ke Meeting
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px; border-top: 1px solid #dedede;">
                            <p style="font-size: 16px; color: #000; ">
                                Pastikan kamu hadir tepat waktu dan mempersiapkan sesi dengan baik. Jika sesi dilakukan secara online, pastikan koneksi internetmu stabil untuk kelancaran konsultasi. 😊
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="font-size: 20px; font-weight: 600; color: #222; padding: 30px 20px; border-top: 1px solid #dedede;">
                            📢 Hal Penting untuk Konsultan:
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="font-size: 16px; color: #000; padding: 0 40px;">
                            ✅ Berikan pelayanan terbaik dan buat klien nyaman agar mereka percaya untuk konsultasi lanjutan. <br>
                            ✅ Pakailah pakaian yang sopan dan profesional serta pastikan suasana kondusif. <br>
                            ✅ Gunakan background netral atau download background konsultasi di sini: <a href="https://soluusi.com/backgroundkonsultasi" style="color: #007648; text-decoration: none;">soluusi.com/backgroundkonsultasi</a>. <br>
                            ✅ Berikan layanan sesuai keilmuan dan keahlianmu agar klien mendapatkan solusi yang tepat. <br>
                            ✅ Jika ada klien yang membuatmu tidak nyaman atau pembahasan di luar bidangmu, segera informasikan ke Luusi (kenyamanan dan keamanan konsultan sangat berarti bagi kami). <br>
                            ✅ Pastikan kamu mengingatkan klien tentang durasi waktu di awal sesi agar mereka lebih sadar terhadap batasan waktu. <br>
                            ✅ Jika diperlukan, rekomendasikan klien untuk konsultasi lanjutan atau rujukan ke ahli lain.
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="font-size: 16px; color: #000; padding: 30px;">
                            Jika ada pertanyaan atau kendala, kamu bisa menghubungi Luusi melalui 
                            <a href="https://api.whatsapp.com/send?phone=6285117674837" style="color: #007648; text-decoration: none; font-weight: 600;" target="_blank">soluusi.com/wa</a> atau lewat <a href="mailto:support@soluusi.com" style="color: #007648; text-decoration: none; font-weight: 600;">support@soluusi.com</a>.
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="font-size: 16px; color: #000; padding: 20px;">
                            Terima kasih telah menjadi bagian dari Soluusi! Semoga sesi ini berjalan lancar dan bermanfaat. 💚
                        </td>
                    </tr>
                    <x-email.footer/>
                </tbody>
            </table>
        </div>
    </body>
</html>
