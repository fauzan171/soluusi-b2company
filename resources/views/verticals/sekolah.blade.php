<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Soluusi Mitra — For School: Sekolah & Kampus</title>
<meta name="description" content="Layanan konseling siswa profesional tanpa rekrut psikolog full-time. MHCU sekolah, parenting, wellbeing guru.">
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
  .card-hover:hover { transform: translateY(-4px); box-shadow: 0 18px 40px -18px rgba(194,84,10,.28); }
  .tick::before { content: "✓"; margin-right: .55rem; font-weight: 700; }
  @keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
  .testi-float { animation: float 5s ease-in-out infinite; }
  .testi-float:nth-child(2) { animation-delay: 1.5s; }
</style>
</head>
<body class="grain antialiased">

<nav class="fixed top-0 inset-x-0 z-40 backdrop-blur-md bg-[#f7f4ee]/85 border-b border-[#c2540a]/10">
  <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
    <a href="{{ route('landing') }}" class="flex items-center gap-2.5">
      <img src="{{ asset('assets/brand/soluusi.svg') }}" alt="Soluusi" class="h-7">
      <span class="text-[11px] font-bold tracking-widest uppercase bg-[#c2540a] text-[#f7f4ee] px-2 py-1 rounded">Mitra</span>
    </a>
    <div class="hidden md:flex items-center gap-8 text-sm font-medium text-[#1a2420]/80">
      <a href="{{ route('landing.klinik') }}" class="hover:text-[#c2540a]">Klinik</a>
      <a href="{{ route('landing.sekolah') }}" class="text-[#c2540a]">Sekolah</a>
      <a href="{{ route('landing.perusahaan') }}" class="hover:text-[#c2540a]">Perusahaan</a>
    </div>
    <a href="{{ route('landing') }}#kontak" class="bg-[#c2540a] text-[#f7f4ee] text-sm font-semibold px-5 py-2.5 rounded-full hover:bg-[#a04508] transition-colors">Jadi Mitra</a>
  </div>
</nav>

<header class="pt-36 pb-20 px-6 relative overflow-hidden bg-[#fdf3ec]">
  <div class="absolute -top-24 -right-24 w-[480px] h-[480px] rounded-full bg-[#c2540a]/10 blur-3xl"></div>
  <div class="max-w-6xl mx-auto relative">
    <div class="grid lg:grid-cols-12 gap-12 items-center">
      <div class="lg:col-span-7">
        <p class="reveal text-sm font-semibold tracking-[.2em] uppercase text-[#c2540a] mb-6">For School</p>
        <h1 class="reveal font-display text-5xl md:text-7xl leading-[1.04] tracking-tight font-medium">
          Wellbeing terstruktur untuk <em class="text-[#c2540a]">siswa, guru & orang tua.</em>
        </h1>
        <p class="reveal mt-8 text-lg md:text-xl text-[#1a2420]/70 max-w-2xl leading-relaxed">
          Konseling siswa profesional, skrining mental massal, seminar parenting — tanpa rekrut psikolog full-time.
        </p>
        <div class="reveal mt-10 flex flex-wrap gap-4">
          <a href="{{ route('landing') }}#kontak" class="bg-[#c2540a] text-[#f7f4ee] font-semibold px-8 py-4 rounded-full hover:bg-[#a04508] transition-colors">Diskusi Kemitraan →</a>
          <a href="#skema" class="border border-[#c2540a]/25 text-[#c2540a] font-semibold px-8 py-4 rounded-full hover:border-[#c2540a] transition-colors">Lihat Skema</a>
        </div>
      </div>
      <div class="lg:col-span-5 relative reveal">
        <div class="absolute -inset-4 bg-[#c2540a]/15 rounded-[2.5rem] rotate-3"></div>
        <img src="{{ asset('images/landing/sekolah-kelas.jpg') }}" alt="Lingkungan sekolah" class="relative rounded-[2.5rem] w-full aspect-[4/5] object-cover shadow-2xl">
      </div>
    </div>
  </div>
</header>

<section id="skema" class="py-24 px-6 bg-[#fdf3ec]">
  <div class="max-w-6xl mx-auto">
    <p class="reveal text-sm font-semibold tracking-[.2em] uppercase text-[#c2540a] mb-3">Enam Skema Kolaborasi</p>
    <h2 class="reveal font-display text-3xl md:text-4xl font-medium tracking-tight mb-10">Dukungan wellbeing menyeluruh untuk komunitas sekolah.</h2>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
      @foreach([
        ['Konseling Siswa','Psikolog anak/remaja on-demand per rujukan BK, online & offline.'],
        ['MHCU Sekolah','Skrining massal + laporan agregat per kelas + flagging siswa berisiko.'],
        ['Edukasi & Parenting','Seminar untuk orang tua, guru & kelas — mental health, digital wellbeing.'],
        ['Wellbeing Guru & Staf','Konseling + skrining burnout untuk guru & staf sekolah.'],
        ['Konsultan Karir','Tes minat-bakat & konseling karir siswa kelas akhir (SMA/SMK/univ).'],
        ['Benefit Keluarga','Diskon konsultasi untuk orang tua & keluarga siswa.'],
      ] as [$t,$d])
      <div class="reveal card-hover bg-white border border-[#c2540a]/15 rounded-2xl p-6">
        <h3 class="font-display text-lg font-semibold text-[#c2540a]">{{ $t }}</h3>
        <p class="mt-2 text-sm text-[#1a2420]/70 leading-relaxed">{{ $d }}</p>
      </div>
      @endforeach
    </div>
  </div>
</section>

<section class="py-24 px-6">
  <div class="max-w-6xl mx-auto">
    <p class="reveal text-sm font-semibold tracking-[.2em] uppercase text-[#c2540a] mb-3">Model Komersial</p>
    <h2 class="reveal font-display text-3xl md:text-4xl font-medium tracking-tight mb-12">Fleksibel sesuai kebutuhan sekolah.</h2>

    <div class="grid md:grid-cols-3 gap-5">
      @foreach([
        ['Subscription Siswa','Rp15–35rb','per siswa / tahun','1× skrining massal + kuota sesi konseling siswa. Cocok untuk sekolah yang mau program wellbeing menyeluruh.', true],
        ['Pay-per-Sesi','Diskon 20%','harga khusus mitra','Siswa/ortu bayar per sesi dengan harga khusus sekolah. Tanpa commitment tahunan.', false],
        ['MHCU Flat','Rp1,5–3jt','per 100 siswa','Skrining massal satu gelombang + laporan agregat + rekomendasi intervensi.', false],
      ] as [$t,$p,$unit,$d,$hot])
      <div class="reveal card-hover rounded-2xl p-7 {{ $hot ? 'bg-[#c2540a] text-[#fdf3ec]' : 'bg-white border border-[#c2540a]/15' }}">
        <h3 class="font-display text-xl font-semibold">{{ $t }}</h3>
        <div class="mt-4 font-display text-4xl font-semibold">{{ $p }}</div>
        <div class="text-xs uppercase tracking-widest mt-1 {{ $hot ? 'text-[#fdf3ec]/70' : 'text-[#1a2420]/50' }}">{{ $unit }}</div>
        <p class="mt-4 text-sm leading-relaxed {{ $hot ? 'text-[#fdf3ec]/85' : 'text-[#1a2420]/70' }}">{{ $d }}</p>
      </div>
      @endforeach
    </div>
    <p class="reveal mt-6 text-xs text-[#1a2420]/50">Semua harga indikatif — final mengikuti cakupan, volume & negosiasi.</p>
  </div>
</section>

<section class="py-24 px-6 bg-white border-t border-[#c2540a]/10">
  <div class="max-w-6xl mx-auto">
    <p class="reveal text-sm font-semibold tracking-[.2em] uppercase text-[#c2540a] mb-3">Confidentiality</p>
    <h2 class="reveal font-display text-3xl md:text-4xl font-medium tracking-tight mb-10">Data siswa dilindungi, hanya aggregate ke sekolah.</h2>
    <div class="grid md:grid-cols-2 gap-8">
      <div class="reveal">
        <p class="text-[#1a2420]/70 leading-relaxed">Laporan skrining ke sekolah selalu <strong>agregat per kelas/sekolah</strong>. Identitas siswa berisiko hanya diakses BK + psikolog Soluusi (confidential).</p>
        <ul class="mt-6 space-y-2 text-sm text-[#1a2420]/80">
          <li class="tick">Informed consent + parent consent untuk siswa di bawah umur</li>
          <li class="tick">UU PDP compliant</li>
          <li class="tick">HIMPSI compliant untuk instrumen category-A</li>
        </ul>
      </div>
      <div class="reveal bg-[#fdf3ec] border border-[#c2540a]/15 rounded-2xl p-6">
        <h3 class="font-display text-lg font-semibold text-[#c2540a] mb-3">Dukungan Aktivasi</h3>
        <ul class="space-y-2 text-sm text-[#1a2420]/80">
          <li class="tick">Template surat edaran skrining ke orang tua</li>
          <li class="tick">Template informed consent & data privacy</li>
          <li class="tick">Dashboard laporan MHCU per kelas</li>
          <li class="tick">Materi parenting standar untuk seminar</li>
          <li class="tick">QR code reservasi konseling di ruang BK</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<section class="py-24 px-6 bg-[#fdf3ec]">
  <div class="max-w-6xl mx-auto">
    <p class="reveal text-sm font-semibold tracking-[.2em] uppercase text-[#c2540a] mb-3">Cara Kerja</p>
    <h2 class="reveal font-display text-3xl md:text-4xl font-medium tracking-tight mb-12">Dari MoU ke sesi pertama dalam 7 hari.</h2>
    <div class="grid md:grid-cols-4 gap-8">
      @foreach([
        ['01','MoU & Onboarding','Tanda tangan, assign psikolog sesuai kebutuhan, setup portal & white-label.'],
        ['02','Aktivasi Hari 1–30','Jadwal konsultan live, materi promosi, sosialisasi BK & staf.'],
        ['03','Sesi Berjalan','Rujukan BK via checkout link, Google Calendar otomatis, laporan agregat.'],
        ['04','Checkpoint 90 Hari','Review performa, dashboard agregat, komisi & bagi hasil cair otomatis.'],
      ] as [$n,$t,$d])
      <div class="reveal border-t border-[#c2540a]/20 pt-6">
        <div class="font-display text-sm text-[#c2540a]">{{ $n }}</div>
        <h3 class="font-display text-xl font-semibold mt-2 text-[#1a2420]">{{ $t }}</h3>
        <p class="mt-2 text-sm text-[#1a2420]/70 leading-relaxed">{{ $d }}</p>
      </div>
      @endforeach
    </div>
  </div>
</section>

<section class="py-24 px-6 bg-white border-t border-[#c2540a]/10">
  <div class="max-w-6xl mx-auto">
    <p class="reveal text-sm font-semibold tracking-[.2em] uppercase text-[#c2540a] mb-3">Testimoni Mitra</p>
    <h2 class="reveal font-display text-3xl md:text-4xl font-medium tracking-tight mb-12">Kata mereka yang sudah bergabung.</h2>

    <div class="grid md:grid-cols-2 gap-8">
      @foreach([
        ['Ibu Ratna, M.Pd','Kepala Sekolah','SMA Harapan Bangsa, Bandung','"Program konseling siswa dari Soluusi sangat membantu. BK kami kewalahan, sekarang ada psikolog on-demand yang bisa handle kasus berat. Orang tua juga puas."','sekolah-testi-1.jpg'],
        ['Fr. Maria Goretti','Wakil Kepala Sekolah Kesiswaan','SMP Santa Maria, Surabaya','"MHCU sekolah kasih kami data real kondisi wellbeing siswa. Intervensi jadi lebih tepat sasaran. Laporan agregat ke yayasan juga gampang."','sekolah-testi-2.jpg'],
      ] as [$name,$role,$org,$quote,$img])
      <div class="reveal card-hover testi-float bg-[#fdf3ec] border border-[#c2540a]/10 rounded-3xl p-8 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-32 h-32 bg-[#c2540a]/5 rounded-full -mr-16 -mt-16"></div>
        <div class="relative">
          <svg class="w-10 h-10 text-[#c2540a]/30 mb-4" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/></svg>
          <p class="text-[#1a2420]/80 leading-relaxed italic mb-6">{{ $quote }}</p>
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-[#c2540a]/20 flex items-center justify-center text-[#c2540a] font-display font-semibold text-lg">
              {{ substr($name, 0, 1) }}
            </div>
            <div>
              <div class="font-display font-semibold text-[#c2540a]">{{ $name }}</div>
              <div class="text-xs text-[#1a2420]/60">{{ $role }} · {{ $org }}</div>
            </div>
          </div>
        </div>
      </div>
      @endforeach
    </div>

    <div class="reveal mt-12 text-center">
      <p class="text-sm text-[#1a2420]/60">+15 sekolah & kampus sudah bergabung di 12 kota</p>
    </div>
  </div>
</section>

<section class="py-24 px-6 bg-white border-t border-[#c2540a]/10">
  <div class="max-w-4xl mx-auto text-center">
    <h2 class="reveal font-display text-4xl md:text-5xl font-medium tracking-tight">Siap mendukung wellbeing siswa?</h2>
    <p class="reveal mt-6 text-[#1a2420]/70 text-lg leading-relaxed">Diskusi 30 menit tanpa komitmen. Tim SAP kami akan siapkan proposal 1-pager sesuai skala sekolah.</p>
    <div class="reveal mt-10 flex flex-wrap justify-center gap-4">
      <a href="{{ route('landing') }}#kontak" class="bg-[#c2540a] text-[#f7f4ee] font-semibold px-8 py-4 rounded-full hover:bg-[#a04508] transition-colors">Diskusi Kemitraan →</a>
      <a href="{{ route('landing.perusahaan') }}" class="border border-[#c2540a]/25 text-[#c2540a] font-semibold px-8 py-4 rounded-full hover:border-[#c2540a] transition-colors">Lihat vertikal lain</a>
    </div>
  </div>
</section>

<footer class="border-t border-[#c2540a]/10 py-10 px-6">
  <div class="max-w-6xl mx-auto flex flex-col md:flex-row justify-between gap-8 items-start">
    <div>
      <span class="flex items-center gap-2.5">
        <img src="{{ asset('assets/brand/soluusi.svg') }}" alt="Soluusi" class="h-6">
        <span class="text-[10px] font-bold tracking-widest uppercase bg-[#c2540a] text-[#f7f4ee] px-2 py-0.5 rounded">Mitra</span>
      </span>
      <p class="mt-3 text-xs text-[#1a2420]/50 max-w-xs leading-relaxed">Platform wellbeing multi-profesional untuk klinik, sekolah & perusahaan.</p>
    </div>
    <div class="text-xs text-[#1a2420]/50">
      <a href="{{ route('landing') }}" class="hover:text-[#c2540a]">← Kembali ke Beranda</a><br>
      <a href="{{ route('loginIndex') }}" class="hover:text-[#c2540a]">Login Mitra / Konsultan →</a>
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