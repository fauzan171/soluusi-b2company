<!DOCTYPE html>
<html>
    <title>Soluusi - Selamat! Data Kamu Telah Kami Terima 🎉</title>
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
                                  Selamat! Data kamu telah berhasil melewati proses review tim Soluusi. Kami senang bisa melangkah ke tahap selanjutnya bersama kamu.
                                  <br><br>
                                  Langkah berikutnya adalah sesi interview singkat (sekitar 15 menit) bersama tim Soluusi untuk mengenal lebih jauh tentang kamu dan memastikan pengalaman terbaik bagi klien di platform kami.
                                  <br><br>
                                  <strong>{{ \Carbon\Carbon::parse($consultant->interview_at)->locale('id')->translatedFormat('d F Y, H.i') }} WIB</strong>
                                  <br>
                                  <a href="{{ $consultant->interivew_link }}" style="color: #007648; text-decoration: none; font-weight: 600;">Link Meeting Disini</a>
                                  <br>
                                  🟢  Jika kamu tidak bisa, silakan kontak WA Luusi.
                                  <br><br>
                                  🟢  Jika waktu tidak sesuai, kamu bisa merekomendasikan jadwal alternatif pada Luusi melalui WhatsApp pada link <a href="https://api.whatsapp.com/send?phone=6285117674837" style="color: #007648; text-decoration: none; font-weight: 600;" target="_blank">soluusi.com/wa</a>, dan Luusi akan konfirmasi kepada tim.
                                  <br><br>
                                  Sekali lagi, selamat! Kami sangat antusias untuk segera berbincang denganmu. 😊
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
