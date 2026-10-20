<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Soluusi Mitra — For Health Facilities: Klinik & Faskes</title>
<meta name="description" content="Tambah layanan psikolog, nutrisionis, fisioterapis ke klinik tanpa rekrut SDM tetap. White-label checkout, split payment otomatis.">
@vite(['resources/css/app.css', 'resources/js/app.js'])
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,400;1,9..144,500&display=swap" rel="stylesheet">
<style>
  :root { --forest: #0c3b2e; --pine: #0d7a5f; --paper: #f7f4ee; --ink: #1a2420; --terra: #c2540a; }
  body { font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; background: var(--paper); color: var(--ink); }
  .font-display { font-family: 'Fraunces', serif; }
  .grain::before { content: ""; position: fixed; inset: 0; pointer-events: none; z-index: 50; opacity: .035; background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E"); }
  .reveal { opacity: 0; transform: translateY(28px); transition: opacity .8s cubic-bezier(.22,1,.36,1), transform .8s cubic-bezier(.22,1,.36,1); }
  .reveal.in { opacity: 1; transform: none; }
  .card-hover { transition: transform .35s cubic-bezier(.22,1,.36,1), box-shadow .35s; }
  .card-hover:hover { transform: translateY(-4px); box-shadow: 0 18px 40px -18px rgba(12,59,46,.28); }
  .tick::before { content: "✓"; margin-right: .55rem; font-weight: 700; color: var(--pine); }
  .cross::before { content: "✕"; margin-right: .55rem; font-weight: 700; color: var(--terra); }
  @keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
  .testi-float { animation: float 5s ease-in-out infinite; }
  .testi-float:nth-child(2) { animation-delay: 1.5s; }
</style>
</head>
<body class="grain antialiased">

<nav class="fixed top-0 inset-x-0 z-40 backdrop-blur-md bg-[#f7f4ee]/85 border-b border-[#0c3b2e]/10">
  <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
    <a href="{{ route('landing') }}" class="flex items-center gap-2.5">
      <img src="{{ asset('assets/brand/soluusi.svg') }}" alt="Soluusi" class="h-7">
      <span class="text-[11px] font-bold tracking-widest uppercase bg-[#0c3b2e] text-[#f7f4ee] px-2 py-1 rounded">Mitra</span>
    </a>
    <div class="hidden md:flex items-center gap-8 text-sm font-medium text-[#0c3b2e]/80">
      <a href="{{ route('landing.klinik') }}" class="text-[#0c3b2e]">Klinik</a>
      <a href="{{ route('landing.sekolah') }}" class="hover:text-[#0c3b2e]">Sekolah</a>
      <a href="{{ route('landing.perusahaan') }}" class="hover:text-[#0c3b2e]">Perusahaan</a>
    </div>
    <a href="{{ route('landing') }}#kontak" class="bg-[#0c3b2e] text-[#f7f4ee] text-sm font-semibold px-5 py-2.5 rounded-full hover:bg-[#0d7a5f] transition-colors">Jadi Mitra</a>
  </div>
</nav>

<!-- HERO -->
<header class="pt-36 pb-20 px-6 relative overflow-hidden">
  <div class="absolute -top-24 -right-24 w-[480px] h-[480px] rounded-full bg-[#0d7a5f]/10 blur-3xl"></div>
  <div class="max-w-6xl mx-auto relative">
    <div class="grid lg:grid-cols-12 gap-12 items-center">
      <div class="lg:col-span-7">
        <p class="reveal text-sm font-semibold tracking-[.2em] uppercase text-[#0d7a5f] mb-6">For Health Facilities</p>
        <h1 class="reveal font-display text-5xl md:text-7xl leading-[1.04] tracking-tight font-medium">
          Tambah layanan profesional, <em class="text-[#0d7a5f]">tanpa rekrut</em> SDM tetap.
        </h1>
        <p class="reveal mt-8 text-lg md:text-xl text-[#1a2420]/70 max-w-2xl leading-relaxed">
          Psikolog, nutrisionis, fisioterapis on-demand untuk klinik & faskes. White-label checkout atas brand klinik, split payment otomatis.
        </p>
        <div class="reveal mt-6 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm font-semibold text-[#0c3b2e]/70">
          <span>30+ klinik & faskes</span><span class="text-[#0d7a5f]">·</span>
          <span>28 provinsi</span><span class="text-[#0d7a5f]">·</span>
          <span>3 profesi kesehatan</span>
        </div>
        <div class="reveal mt-10 flex flex-wrap gap-4">
          <a href="{{ route('landing') }}#kontak" class="bg-[#0c3b2e] text-[#f7f4ee] font-semibold px-8 py-4 rounded-full hover:bg-[#0d7a5f] transition-colors">Diskusi Kemitraan →</a>
          <a href="#skema" class="border border-[#0c3b2e]/25 text-[#0c3b2e] font-semibold px-8 py-4 rounded-full hover:border-[#0c3b2e] transition-colors">Lihat Skema</a>
        </div>
      </div>
      <div class="lg:col-span-5 relative reveal">
        <div class="absolute -inset-4 bg-[#0d7a5f]/15 rounded-[2.5rem] rotate-3"></div>
        <img src="{{ asset('images/landing/klinik-konsultasi.jpg') }}" alt="Konsultasi di klinik" class="relative rounded-[2.5rem] w-full aspect-[4/5] object-cover shadow-2xl">
      </div>
    </div>
  </div>
</header>

<!-- PAIN -->
<section class="py-24 px-6 bg-white border-y border-[#0c3b2e]/10">
  <div class="max-w-6xl mx-auto">
    <p class="reveal text-sm font-semibold tracking-[.2em] uppercase text-[#0d7a5f] mb-3">Masalahnya</p>
    <h2 class="reveal font-display text-3xl md:text-4xl font-medium tracking-tight max-w-3xl">Mau tambah layanan psikolog, tapi cara lama terlalu mahal.</h2>
    <div class="grid md:grid-cols-3 gap-5 mt-12">
      @foreach([
        ['Rekrut = biaya tetap','Gaji, tunjangan, BPJS — terlepas dari ada pasien atau tidak. Untuk layanan baru, ini risiko besar.'],
        ['Trial & error mahal','Salah rekrut satu orang = 6 bulan biaya hangus sebelum sadar layanannya tidak jalan.'],
        ['Admin yang menumpuk','Jadwal, invoicing, laporan sesi — semua nambah beban staf yang sudah penuh.'],
      ] as [$t,$d])
      <div class="reveal border-t-2 border-[#c2540a]/40 pt-6">
        <h3 class="font-display text-xl font-semibold text-[#0c3b2e]">{{ $t }}</h3>
        <p class="mt-2 text-sm text-[#1a2420]/70 leading-relaxed">{{ $d }}</p>
      </div>
      @endforeach
    </div>
    <p class="reveal mt-12 font-display text-2xl text-[#0d7a5f]">Solusinya: layanan on-demand, biaya hanya saat ada sesi.</p>
  </div>
</section>

<!-- SKEMA -->
<section id="skema" class="py-24 px-6">
  <div class="max-w-6xl mx-auto">
    <p class="reveal text-sm font-semibold tracking-[.2em] uppercase text-[#0d7a5f] mb-3">Empat Skema Kolaborasi</p>
    <h2 class="reveal font-display text-3xl md:text-4xl font-medium tracking-tight mb-10">Pilih sesuai kebutuhan klinik — bisa kombinasi.</h2>

    <div class="grid md:grid-cols-3 gap-5">
      @foreach([
        ['Associate Consultant','Akses psikolog, nutrisionis, fisioterapis on-demand (online & offline).','Tambah layanan tanpa biaya tetap SDM'],
        ['Edukasi & Aktivasi','Seminar & webinar mental health, parenting, burnout.','Reputasi klinik sebagai holistic care'],
        ['Paket Custom Sesuai Kebutuhan','Paket 1-on-1 dan check-up (skrining mental health & tes lain) disusun bareng mitra, sesuai kebutuhan dan budget klinik.','Layanan tambahan tanpa komitmen tetap'],
      ] as [$t,$d,$v])
      <div class="reveal card-hover bg-white border border-[#0c3b2e]/10 rounded-2xl p-7">
        <h3 class="font-display text-xl font-semibold text-[#0c3b2e]">{{ $t }}</h3>
        <p class="mt-2 text-sm text-[#1a2420]/70 leading-relaxed">{{ $d }}</p>
        <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-[#0d7a5f]">{{ $v }}</p>
      </div>
      @endforeach
    </div>
  </div>
</section>

<!-- SATU SISTEM SIAP PAKAI — angka dibahas saat meeting -->
<section class="py-24 px-6 bg-[#0c3b2e] text-[#f7f4ee]">
  <div class="max-w-6xl mx-auto">
    <p class="reveal text-sm font-semibold tracking-[.2em] uppercase text-[#7ad1b8] mb-3">Yang Klinik Terima</p>
    <h2 class="reveal font-display text-3xl md:text-4xl font-medium tracking-tight">Bukan cuma konsultan — satu sistem operasional siap pakai.</h2>
    <p class="reveal mt-4 text-[#f7f4ee]/70 leading-relaxed max-w-2xl">Kami bangun dan kelola seluruh platformnya: dari pasien booking sampai margin cair. Klinik tinggal pakai, tanpa nambah satu pun pekerjaan admin.</p>

    <div class="grid md:grid-cols-3 gap-5 mt-12">
      {{-- Icon: heroicons outline (MIT) — konsisten stroke, bukan emoji --}}
      @foreach([
        ['M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21','Portal Mitra','Admin klinik pilih konsultan dari pool ter-QC, atur jadwal, buat link booking, dan pantau semuanya dalam satu dashboard.'],
        ['M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244','Checkout Link White-Label','Link booking atas brand klinik — pasien isi data & bayar langsung tanpa akun. Nama kami tidak muncul di halaman pasien.'],
        ['M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z','Pembayaran Terintegrasi','Pasien bayar via payment gateway (VA, e-wallet, QRIS). Begitu lunas, bagian klinik langsung tercatat otomatis.'],
        ['M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z','Dual-Confirm Anti-Fraud','Sesi baru dianggap selesai setelah konfirmasi konsultan & pasien. Tidak ada sesi fiktif yang terhitung.'],
        ['M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z','Dashboard Agregat','Statistik sesi, status, dan margin real-time. Detail pasien tetap rahasia — klinik hanya lihat angka agregat.'],
        ['M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z','Google Calendar Otomatis','Setiap sesi langsung terjadwal di kalender klinik, lengkap dengan link meeting untuk sesi online.'],
      ] as [$path,$t,$d])
      <div class="reveal bg-[#f7f4ee]/5 border border-[#f7f4ee]/15 rounded-2xl p-6">
        <div class="w-11 h-11 rounded-xl bg-[#7ad1b8]/15 flex items-center justify-center">
          <svg class="w-6 h-6 text-[#7ad1b8]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/></svg>
        </div>
        <h3 class="font-display font-semibold text-[#7ad1b8] mt-4">{{ $t }}</h3>
        <p class="mt-2 text-sm text-[#f7f4ee]/70 leading-relaxed">{{ $d }}</p>
      </div>
      @endforeach
    </div>

    <div class="reveal mt-12 text-center">
      <a href="{{ route('landing') }}#kontak" class="inline-block bg-[#7ad1b8] text-[#0c3b2e] font-semibold px-8 py-4 rounded-full hover:bg-[#f7f4ee] transition-colors">Lihat Sistemnya di Demo →</a>
    </div>
  </div>
</section>

<!-- CARA KERJA -->
<section class="py-24 px-6">
  <div class="max-w-6xl mx-auto">
    <p class="reveal text-sm font-semibold tracking-[.2em] uppercase text-[#0d7a5f] mb-3">Cara Kerja</p>
    <h2 class="reveal font-display text-3xl md:text-4xl font-medium tracking-tight mb-12">Dari MoU ke sesi pertama dalam 7 hari.</h2>
    <div class="grid md:grid-cols-4 gap-8">
      @foreach([
        ['01','MoU & Onboarding','Tanda tangan, assign konsultan sesuai kebutuhan, setup portal & white-label.'],
        ['02','Aktivasi Hari 1–30','Jadwal konsultan live, materi promosi, sosialisasi staf — dipandu tim kami.'],
        ['03','Sesi Berjalan','Booking via checkout link, Google Calendar otomatis, dual-confirm anti-fraud.'],
        ['04','Checkpoint 90 Hari','Review performa, dashboard agregat, komisi & bagi hasil cair otomatis.'],
      ] as [$n,$t,$d])
      <div class="reveal border-t border-[#0c3b2e]/20 pt-6">
        <div class="font-display text-sm text-[#0d7a5f]">{{ $n }}</div>
        <h3 class="font-display text-xl font-semibold mt-2 text-[#0c3b2e]">{{ $t }}</h3>
        <p class="mt-2 text-sm text-[#1a2420]/70 leading-relaxed">{{ $d }}</p>
      </div>
      @endforeach
    </div>
  </div>
</section>

<!-- TESTIMONI -->
<section class="py-24 px-6 bg-white border-t border-[#0c3b2e]/10">
  <div class="max-w-6xl mx-auto">
    <p class="reveal text-sm font-semibold tracking-[.2em] uppercase text-[#0d7a5f] mb-3">Testimoni Mitra</p>
    <h2 class="reveal font-display text-3xl md:text-4xl font-medium tracking-tight mb-12">Kata mereka yang sudah bergabung.</h2>

    <div class="grid md:grid-cols-2 gap-8">
      @foreach([
        ['dr. Sarah Wijaya','Owner','Klinik Sehat Mandiri, Jakarta','"Sejak partner dengan Soluusi, kami bisa tawarkan layanan psikolog tanpa rekrut full-time. Pasien puas, revenue naik 30% dalam 3 bulan pertama."'],
        ['dr. Andi Pratama','Direktur Operasional','Grup Klinik Sejahtera (5 cabang)','"Split payment otomatis bikin administrasi gampang. Margin klinik langsung masuk, gak perlu ribet invoicing. Recommended!"'],
      ] as [$name,$role,$org,$quote])
      <div class="reveal card-hover testi-float bg-[#f7f4ee] border border-[#0c3b2e]/10 rounded-3xl p-8 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-32 h-32 bg-[#0d7a5f]/5 rounded-full -mr-16 -mt-16"></div>
        <div class="relative">
          <svg class="w-10 h-10 text-[#0d7a5f]/30 mb-4" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/></svg>
          <p class="text-[#1a2420]/80 leading-relaxed italic mb-6">{{ $quote }}</p>
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-[#0d7a5f]/20 flex items-center justify-center text-[#0d7a5f] font-display font-semibold text-lg">
              {{ substr($name, 0, 1) }}
            </div>
            <div>
              <div class="font-display font-semibold text-[#0c3b2e]">{{ $name }}</div>
              <div class="text-xs text-[#1a2420]/60">{{ $role }} · {{ $org }}</div>
            </div>
          </div>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>

<!-- FIT OR NOT -->
<section class="py-24 px-6">
  <div class="max-w-6xl mx-auto">
    <p class="reveal text-sm font-semibold tracking-[.2em] uppercase text-[#0d7a5f] mb-3">Cek Kecocokan</p>
    <h2 class="reveal font-display text-3xl md:text-4xl font-medium tracking-tight mb-12">Kami pilih-pilih mitra. Ini fair untuk kedua pihak.</h2>
    <div class="grid md:grid-cols-2 gap-5">
      <div class="reveal bg-white border border-[#0d7a5f]/25 rounded-2xl p-8">
        <h3 class="font-display text-xl font-semibold text-[#0d7a5f] mb-5">Cocok kalau klinik kamu…</h3>
        <ul class="space-y-3 text-sm text-[#1a2420]/80 leading-relaxed">
          <li class="tick">Mau tambah layanan wellbeing tapi hindari biaya tetap SDM</li>
          <li class="tick">Punya traffic pasien yang bisa ditawari layanan baru</li>
          <li class="tick">Serius menjaga kerahasiaan data pasien (UU PDP)</li>
          <li class="tick">Siap kolaborasi minimal 1 kuartal untuk lihat hasil</li>
        </ul>
      </div>
      <div class="reveal bg-white border border-[#c2540a]/20 rounded-2xl p-8">
        <h3 class="font-display text-xl font-semibold text-[#c2540a] mb-5">Belum cocok kalau…</h3>
        <ul class="space-y-3 text-sm text-[#1a2420]/80 leading-relaxed">
          <li class="cross">Cari karyawan tetap full-time — kami model kemitraan</li>
          <li class="cross">Butuh layanan kuratif darurat/IGD — di luar scope</li>
          <li class="cross">Mau kelola konsultan sendiri — pool & QC dari kami</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="py-24 px-6 bg-white border-y border-[#0c3b2e]/10">
  <div class="max-w-4xl mx-auto">
    <p class="reveal text-sm font-semibold tracking-[.2em] uppercase text-[#0d7a5f] mb-3">FAQ</p>
    <h2 class="reveal font-display text-3xl md:text-4xl font-medium tracking-tight mb-12">Yang paling sering ditanyakan.</h2>
    <div class="space-y-4">
      @foreach([
        ['Berapa lama kontrak / MoU-nya?','Tidak ada lock-in panjang. Kami review bersama setiap checkpoint 90 hari — kalau tidak cocok, bisa evaluasi ulang tanpa penalti.'],
        ['Siapa yang bertanggung jawab secara klinis?','Konsultan kami credentialed & di-QC oleh tim Soluusi. Klinik tetap pegang hubungan dengan pasien; kami tangani matching, jadwal, dan verifikasi sesi.'],
        ['Bagaimana uang mengalir?','Pasien bayar lewat checkout white-label atas brand klinik. Sistem split otomatis: bagian konsultan & Soluusi ke kami, margin klinik tercatat dan cair otomatis.'],
        ['Apakah staf klinik bisa lihat data pasien?','Tidak. Dashboard klinik hanya data agregat (jumlah sesi, status, margin). Detail sesi & identitas pasien rahasia — ini komitmen UU PDP kami.'],
        ['Berapa margin untuk klinik?','Disesuaikan skala klinik dan layanan yang dipilih — dibahas di diskusi 30 menit, lalu kami kirim proposal 1-pager.'],
      ] as [$q,$a])
      <details class="reveal group bg-[#f7f4ee] border border-[#0c3b2e]/10 rounded-2xl px-6 py-5">
        <summary class="cursor-pointer font-display text-lg font-semibold text-[#0c3b2e] list-none flex justify-between items-center gap-4">
          {{ $q }}
          <span class="text-[#0d7a5f] text-xl transition-transform group-open:rotate-45 shrink-0">+</span>
        </summary>
        <p class="mt-3 text-sm text-[#1a2420]/70 leading-relaxed">{{ $a }}</p>
      </details>
      @endforeach
    </div>
  </div>
</section>

<!-- CTA -->
<section class="py-24 px-6">
  <div class="max-w-4xl mx-auto text-center">
    <h2 class="reveal font-display text-4xl md:text-5xl font-medium tracking-tight">Siap menambah layanan klinik?</h2>
    <p class="reveal mt-6 text-[#1a2420]/70 text-lg leading-relaxed">Diskusi 30 menit tanpa komitmen. Tim kami akan siapkan proposal 1-pager sesuai skala klinik — termasuk skema komisi yang angkanya tidak kami taruh di sini.</p>
    <div class="reveal mt-10 flex flex-wrap justify-center gap-4">
      <a href="{{ route('landing') }}#kontak" class="bg-[#0c3b2e] text-[#f7f4ee] font-semibold px-8 py-4 rounded-full hover:bg-[#0d7a5f] transition-colors">Diskusi Kemitraan →</a>
      <a href="{{ route('landing.sekolah') }}" class="border border-[#0c3b2e]/25 text-[#0c3b2e] font-semibold px-8 py-4 rounded-full hover:border-[#0c3b2e] transition-colors">Lihat vertikal lain</a>
    </div>
  </div>
</section>

<footer class="border-t border-[#0c3b2e]/10 py-10 px-6">
  <div class="max-w-6xl mx-auto flex flex-col md:flex-row justify-between gap-8 items-start">
    <div>
      <span class="flex items-center gap-2.5">
        <img src="{{ asset('assets/brand/soluusi.svg') }}" alt="Soluusi" class="h-6">
        <span class="text-[10px] font-bold tracking-widest uppercase bg-[#0c3b2e] text-[#f7f4ee] px-2 py-0.5 rounded">Mitra</span>
      </span>
      <p class="mt-3 text-xs text-[#1a2420]/50 max-w-xs leading-relaxed">Platform wellbeing multi-profesional untuk klinik, sekolah & perusahaan.</p>
    </div>
    <div class="text-xs text-[#1a2420]/50">
      <a href="{{ route('landing') }}" class="hover:text-[#0c3b2e]">← Kembali ke Beranda</a><br>
      <a href="{{ route('loginIndex') }}" class="hover:text-[#0c3b2e]">Login Mitra / Konsultan →</a>
    </div>
  </div>
</footer>

<script>
  const io = new IntersectionObserver((entries) => {
    entries.forEach((e) => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
  }, { threshold: 0.12 });
  document.querySelectorAll('.reveal').forEach((el) => io.observe(el));
</script>
</body>
</html>
