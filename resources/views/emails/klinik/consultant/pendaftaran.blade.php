<!DOCTYPE html>
<html>
    <title>Soluusi - Terima Kasih Telah Mendaftar di Soluusi, {{ $consultant->user->firstname }}! 🎉</title>
    <body>
      <div bgcolor="#FFFFFF" style="background-color: #ffffff; margin: 0; padding: 0">
        <table align="left" width="100%" style="background: #fff; min-width: 320px" cellspacing="0" cellpadding="0">
          <tbody>
            <tr>
              <td>
                <table width="650" align="left" style="max-width: 650px; margin: 0 auto" cellpadding="0" cellspacing="0">
                  <tbody>
                    <tr>
                      <td align="left" style="padding: 30px 20px 30px; ">
                        <img src="https://soluusi.com/assets/media/email/logo.png?v=32025" width="150" style="margin: 0; padding-bottom: 0px;">
                        </img>
                      </td>
                    </tr>
                    <tr>
                      <td style="padding: 0 10px 60px">
                        <table align="left" width="550" style="max-width: 550px; margin: 0 auto" cellpadding="0" cellspacing="0">
                          <tbody>
                            <tr>
                              <td align="left" style="font-size: 24px; line-height: 32px; font-weight: normal; font-family: 'Poppins', Arial, Helvetica, sans-serif; color: #000; padding: 30px 20px 30px; ">
                                <p style="font-size: 24px; line-height: 32px; font-weight: normal; font-family: 'Poppins', Arial, Helvetica, sans-serif; color: #000; font-weight: normal; margin: 0; padding-bottom: 0px;">
                                  <strong>Halo {{ $consultant->user->firstname }},</strong>
                                </p>
                              </td>
                            </tr>
                            <tr>
                              <td align="left" style="font-size: 18px; line-height: 24px; font-weight: normal; font-family: 'Poppins', Arial, Helvetica, sans-serif; color: #000; padding: 0px 20px 30px; ">
                                <p style="font-size: 14px; line-height: 24px; font-weight: normal; font-family: 'Poppins', Arial, Helvetica, sans-serif; color: #000; font-weight: normal; margin: 0; padding-bottom: 0px; ">
                                  Selamat datang di Soluusi! Terima kasih telah mendaftar sebagai konsultan di platform kami.
                                  <br><br>
                                  Kenalin aku Luusi. Tim kami saat ini sedang meninjau profil kamu. Proses ini akan memakan waktu maksimal 2x24 jam pada hari kerja. Setelah proses review selesai, kamu akan menerima email konfirmasi terkait langkah selanjutnya:
                                  <br><br>
                                  🟢  Jika data kamu telah kami terima, kami akan mengundang untuk melakukan sesi interview bersama tim Soluusi.
                                  <br>
                                  🟢  Jika data kamu belum dapat kami terima, kami akan menginformasikan alasannya melalui email.
                                  <br><br>
                                  Untuk pertanyaan lebih lanjut, kamu bisa menghubungi aku, Luusi, melalui WhatsApp di link berikut: <a href="https://api.whatsapp.com/send?phone=6285117674837" style="color: #007648; text-decoration: none; font-weight: 600;" target="_blank">soluusi.com/wa</a>
                                  <br><br>
                                  Terima kasih sudah menjadi bagian dari Soluusi. Kami berharap dapat bekerja sama untuk membantu lebih banyak orang menemukan Soluusi terbaik bagi mereka!
                                  <br><br>
                                  Salam hangat,
                                  <br>
                                  <strong>Luusi</strong>
                                  <br>
                                  Soluusi Team
                                </p>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </td>
                    </tr>
                    <x-email.footer/>
                  </tbody>
                </table>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </body>
</html>