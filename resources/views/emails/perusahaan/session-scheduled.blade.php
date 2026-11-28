@if($start)
Jadwal sesi konsultasi kamu sudah diatur.

Waktu : {{ $start->translatedFormat('l, d M Y H.i') }} – {{ $end->translatedFormat('H.i') }} WIB

@if($ics)
Undangan kalender terlampir (invite.ics) — buka untuk menambah ke Google Calendar, Outlook, atau Apple Calendar.
@endif
@else
Ada pembaruan sesi konsultasi kamu yang menunggu tindakan.
@endif

Detail sesi (tipe, mode, link gabung): {{ $viewUrl }}
