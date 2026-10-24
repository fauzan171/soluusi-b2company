<!DOCTYPE html>
<html>
    <head>
        <title>Terima Kasih! Mohon Isi Ulasan & CHK 💙</title>
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
                            🎉 Terima Kasih! Mohon Isi Ulasan & CHK 💙
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="font-size: 16px; color: #000; padding: 0 20px;">
                            Hai <strong>{{ $consultationSession->consultant->user->firstname . ' ' . $consultationSession->consultant->user->lastname }}</strong>,<br>

                            Terima kasih sudah menyelesaikan sesi konsultasi dengan <strong>{{ $consultationSession->customer_display_name }}</strong>! Kami harap sesi tadi berjalan lancar dan memberikan solusi terbaik. 😊

                        </td>
                    </tr>

                    <tr>
                        <td align="left" style="font-size: 20px; font-weight: 600; color: #222; padding: 30px 20px; border-top: 1px solid #dedede;">
                            📌 Penting!
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="font-size: 16px; color: #000; padding: 0 40px;">
                            ✅ <strong>Catatan Hasil Konsultasi (CHK) wajib diisi</strong> sesegera mungkin dan harus selesai dalam waktu maksimal <strong>2 x 24 jam</strong>. <br>
                            ✅ Jika CHK tidak diisi, <strong>konsultasi dianggap tidak ada</strong> dan tidak akan ada transaksi pembayaran. <br>
                            ✅ <strong>Pembayaran dilakukan setiap hari Jumat</strong>, terhitung 2 hari kerja setelah CHK selesai. Jika CHK selesai setelah batas waktu, maka pembayaran akan dilakukan pada Jumat berikutnya. <br>
                            ✅ Jika ada keterlambatan atau pertanyaan mengenai pembayaran dan sistem konsultasi, silakan hubungi Luusi di <a href="https://api.whatsapp.com/send?phone=6285117674837" style="color: #007648; text-decoration: none; font-weight: 600;" target="_blank">soluusi.com/wa</a> <br>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="font-size: 16px; color: #000; padding: 30px;">
                            Terima kasih sudah menjadi bagian dari Soluusi! 💙
                        </td>
                    </tr>
                    <x-email.footer/>
                </tbody>
            </table>
        </div>
    </body>
</html>
