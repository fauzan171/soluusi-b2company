<!DOCTYPE html>
<html>
    <head>
        <title>Soluusi - Terima Kasih! Catatan Hasil Konsultasi Kamu Sudah Selesai ✅</title>
    </head>
    <body>
        <div style="background-color: #ffffff; margin: 0; padding: 0; font-family: 'Poppins', Arial, Helvetica, sans-serif;">
            <table align="left" width="100%" cellspacing="0" cellpadding="0" style="background: #fff; min-width: 320px; max-width: 650px; margin: 0 auto;">
                <tbody>
                    <tr>
                        <td align="left" style="padding: 30px 20px;">
                            <img src="https://soluusi.com/assets/media/email/logo.png?v=32025" width="150" alt="Soluusi Logo" style="margin: 0;">
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="font-size: 24px; font-weight: bold; color: #000; padding: 30px 20px;">
                            ✅ Terima Kasih! Catatan Hasil Konsultasi Kamu Sudah Selesai 
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="font-size: 16px; color: #000; padding: 0 20px;">
                            Hai <strong>{{ $session->consultant->user->firstname . ' ' . $session->consultant->user->lastname }}</strong>,<br>

                            Terima kasih sudah menyelesaikan <strong>Catatan Hasil Konsultasi</strong> untuk sesi dengan <strong>{{ $session->customer_display_name }}</strong>. Kami sangat mengapresiasi dedikasimu dalam membantu klien mencapai solusinya! 🙌
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 50px 20px; border-top: 1px solid #dedede;">
                            <table width="100%" align="left" cellspacing="0" cellpadding="0">
                                <tbody>
                                    <tr>
                                        <td align="left" style="font-size: 20px; font-weight: 600; color: #222; padding-bottom: 20px;">
                                            💰 Informasi Pembayaran
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="left" style="font-size: 16px; color: #000; padding: 0 40px;">
                                            ✅ <strong>Pembayaran akan dilakukan setiap hari Jumat</strong>, terhitung 2 hari kerja setelah CHK selesai.<br><br>
                                            ✅ Jika CHK selesai setelah batas waktu, maka pembayaran akan dilakukan pada Jumat berikutnya.<br><br>
                                            ✅ Jika ada keterlambatan atau pertanyaan mengenai pembayaran dan sistem konsultasi, silakan hubungi Luusi melalui 
                                            <a href="https://api.whatsapp.com/send?phone=6285117674837" style="color: #007648; text-decoration: none; font-weight: 600;" target="_blank">soluusi.com/wa</a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="font-size: 16px; color: #000; padding: 30px; border-top: 1px solid #dedede;">
                            Terima kasih sudah menjadi bagian dari <strong>Soluusi</strong>! Kami berharap bisa terus bekerja sama denganmu untuk membantu lebih banyak orang. 💙
                        </td>
                    </tr>
                    <x-email.footer/>
                </tbody>
            </table>
        </div>
    </body>
</html>
