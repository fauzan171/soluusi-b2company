<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Soluusi Mitra — Partner Wellbeing untuk Klinik, Sekolah & Perusahaan</title>
<meta name="description" content="Platform wellbeing multi-profesional: psikolog, nutrisionis, fisioterapis untuk klinik, sekolah, dan perusahaan. Tanpa biaya tetap — bayar saat layanan digunakan.">
@vite(['resources/css/app.css', 'resources/js/app.js'])
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,400;1,9..144,500&display=swap" rel="stylesheet">
<style>
  :root {
    --forest: #0c3b2e;
    --pine: #0d7a5f;
    --paper: #f7f4ee;
    --ink: #1a2420;
    --terra: #c2540a;
    --navy: #1d2d50;
  }
  body { font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; background: var(--paper); color: var(--ink); }
  .font-display { font-family: 'Fraunces', serif; }
  .grain::before {
    content: ""; position: fixed; inset: 0; pointer-events: none; z-index: 50; opacity: .035;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
  }
  .reveal { opacity: 0; transform: translateY(28px); transition: opacity .8s cubic-bezier(.22,1,.36,1), transform .8s cubic-bezier(.22,1,.36,1); }
  .reveal.in { opacity: 1; transform: none; }
  @keyframes marquee { from { transform: translateX(0); } to { transform: translateX(-50%); } }
  .marquee-track { animation: marquee 28s linear infinite; }
  .num-outline { -webkit-text-stroke: 1.5px currentColor; color: transparent; }
  .card-hover { transition: transform .35s cubic-bezier(.22,1,.36,1), box-shadow .35s; }
  .card-hover:hover { transform: translateY(-4px); box-shadow: 0 18px 40px -18px rgba(12,59,46,.28); }
  .tick::before { content: "✓"; margin-right: .55rem; font-weight: 700; }
</style>
</head>
<body class="grain antialiased">

{{-- ============ NAV ============ --}}
<nav class="fixed top-0 inset-x-0 z-40 backdrop-blur-md bg-[#f7f4ee]/85 border-b border-[#0c3b2e]/10">
  <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
    <span class="flex items-center gap-2.5">
      <img src="{{ asset('assets/brand/soluusi.svg') }}" alt="Soluusi" class="h-7">
      <span class="text-[11px] font-bold tracking-widest uppercase bg-[#0c3b2e] text-[#f7f4ee] px-2 py-1 rounded">Mitra</span>
    </span>
    <div class="hidden md:flex items-center gap-8 text-sm font-medium text-[#0c3b2e]/80">
      <a href="{{ route('landing.klinik') }}" class="hover:text-[#0c3b2e]">Klinik</a>
      <a href="{{ route('landing.sekolah') }}" class="hover:text-[#0c3b2e]">Sekolah</a>
      <a href="{{ route('landing.perusahaan') }}" class="hover:text-[#0c3b2e]">Perusahaan</a>
      <a href="#cara" class="hover:text-[#0c3b2e]">Cara Kerja</a>
      <a href="{{ route('landing') }}#kontak" class="hover:text-[#0c3b2e]">Kontak</a>
    </div>
    <a href="{{ route('landing') }}#kontak" class="bg-[#0c3b2e] text-[#f7f4ee] text-sm font-semibold px-5 py-2.5 rounded-full hover:bg-[#0d7a5f] transition-colors">Jadi Mitra</a>
  </div>
</nav>

{{-- ============ HERO ============ --}}
<header class="pt-36 pb-20 px-6 relative overflow-hidden">
  <div class="absolute -top-24 -right-24 w-[480px] h-[480px] rounded-full bg-[#0d7a5f]/10 blur-3xl"></div>
  <div class="absolute top-40 -left-32 w-[380px] h-[380px] rounded-full bg-[#c2540a]/10 blur-3xl"></div>
  <div class="max-w-6xl mx-auto relative">
    <div class="grid lg:grid-cols-12 gap-12 items-center">
      <div class="lg:col-span-7">
        <p class="reveal text-sm font-semibold tracking-[.2em] uppercase text-[#0d7a5f] mb-6">Platform Wellbeing untuk Organisasi — Indonesia</p>
        <h1 class="reveal font-display text-5xl md:text-7xl leading-[1.04] tracking-tight font-medium" style="transition-delay:.08s">
          Wellbeing terintegrasi untuk <em class="text-[#0d7a5f]">organisasi modern.</em>
        </h1>
        <p class="reveal mt-8 text-lg md:text-xl text-[#1a2420]/70 max-w-2xl leading-relaxed" style="transition-delay:.16s">
          Satu platform untuk <strong>klinik</strong>, <strong>sekolah</strong>, dan <strong>perusahaan</strong>. Akses psikolog, nutrisionis, fisioterapis & konsultan karir — online & offline, tanpa biaya tetap.
        </p>
        <div class="reveal mt-10 flex flex-wrap gap-4" style="transition-delay:.24s">
          <a href="{{ route('landing') }}#kontak" class="bg-[#0c3b2e] text-[#f7f4ee] font-semibold px-8 py-4 rounded-full hover:bg-[#0d7a5f] transition-colors">Diskusi Kemitraan →</a>
          <a href="{{ route('landing') }}#vertikal" class="border border-[#0c3b2e]/25 text-[#0c3b2e] font-semibold px-8 py-4 rounded-full hover:border-[#0c3b2e] transition-colors">Lihat Solusi</a>
        </div>
      </div>
      <div class="lg:col-span-5 relative reveal" style="transition-delay:.2s">
        <div class="absolute -inset-4 bg-[#0d7a5f]/15 rounded-[2.5rem] rotate-3"></div>
        <img src="{{ asset('images/landing/hero-support.jpg') }}" alt="Dukungan wellbeing profesional" class="relative rounded-[2.5rem] w-full aspect-[4/5] object-cover shadow-2xl">
        <div class="absolute -bottom-6 -left-6 bg-[#f7f4ee] border border-[#0c3b2e]/10 rounded-2xl px-5 py-4 shadow-xl">
          <div class="font-display text-2xl font-semibold text-[#0c3b2e]">300+</div>
          <div class="text-xs text-[#1a2420]/60 uppercase tracking-wide">Konsultan profesional</div>
        </div>
      </div>
    </div>
    <div class="reveal mt-16 grid grid-cols-2 md:grid-cols-4 gap-px bg-[#0c3b2e]/10 border border-[#0c3b2e]/10 rounded-2xl overflow-hidden" style="transition-delay:.32s">
      @foreach([['3','Vertikal layanan'],['4+','Profesi konsultan'],['90 hari','Checkpoint aktivasi'],['0','Biaya tetap di depan']] as [$num,$label])
      <div class="bg-[#f7f4ee] p-6">
        <div class="font-display text-3xl font-semibold text-[#0c3b2e]">{{ $num }}</div>
        <div class="text-xs mt-1 text-[#1a2420]/60 uppercase tracking-wide">{{ $label }}</div>
      </div>
      @endforeach
    </div>
  </div>
</header>

{{-- ============ MARQUEE ============ --}}
<div class="border-y border-[#0c3b2e]/10 py-4 overflow-hidden bg-[#0c3b2e]">
  <div class="marquee-track flex whitespace-nowrap gap-10 text-[#f7f4ee]/85 font-display text-lg italic w-max">
    @foreach(array_fill(0,2,null) as $_)
      <span>Psikolog Klinis</span><span class="inline-block w-1.5 h-1.5 rotate-45 bg-[#c2540a] self-center"></span>
      <span>Psikolog Pendidikan</span><span class="inline-block w-1.5 h-1.5 rotate-45 bg-[#c2540a] self-center"></span>
      <span>Konselor</span><span class="inline-block w-1.5 h-1.5 rotate-45 bg-[#c2540a] self-center"></span>
      <span>Ahli Gizi</span><span class="inline-block w-1.5 h-1.5 rotate-45 bg-[#c2540a] self-center"></span>
      <span>Fisioterapis</span><span class="inline-block w-1.5 h-1.5 rotate-45 bg-[#c2540a] self-center"></span>
      <span>Terapis Okupasi</span><span class="inline-block w-1.5 h-1.5 rotate-45 bg-[#c2540a] self-center"></span>
      <span>Konsultan Karir</span><span class="inline-block w-1.5 h-1.5 rotate-45 bg-[#c2540a] self-center"></span>
      <span>Online & Offline</span><span class="inline-block w-1.5 h-1.5 rotate-45 bg-[#c2540a] self-center"></span>
    @endforeach
  </div>
</div>

{{-- ============ PROFESI ============ --}}
<section class="py-20 px-6">
  <div class="max-w-6xl mx-auto">
    <p class="reveal text-sm font-semibold tracking-[.2em] uppercase text-[#0d7a5f] mb-3">Tim Multi-Profesional</p>
    <h2 class="reveal font-display text-3xl md:text-4xl font-medium tracking-tight mb-10">Satu platform, semua kebutuhan wellbeing.</h2>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
      @foreach([
        ['mhcu-brain.jpg','Psikolog Klinis','Konseling & skrining mental (MMCI/MMPI/DISC) oleh psikolog berlisensi.'],
        ['gizi-bowl.jpg','Ahli Gizi','Konseling nutrisi personal & program gizi korporat.'],
        ['fisio-terapi.jpg','Fisioterapis','Terapi fisik, rehabilitasi & ergonomi pekerja kantoran.'],
        ['wellbeing-meditasi.jpg','Wellbeing Program','Workshop burnout, stress management & mindfulness.'],
      ] as [$img,$t,$d])
      <div class="reveal card-hover bg-white border border-[#0c3b2e]/10 rounded-2xl overflow-hidden">
        <img src="{{ asset('images/landing/'.$img) }}" alt="{{ $t }}" class="w-full h-44 object-cover">
        <div class="p-5">
          <h3 class="font-display text-lg font-semibold text-[#0c3b2e]">{{ $t }}</h3>
          <p class="mt-1.5 text-xs text-[#1a2420]/65 leading-relaxed">{{ $d }}</p>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ============ TRUSTED BY ============ --}}
<section class="py-16 px-6 bg-white border-y border-[#0c3b2e]/10">
  <div class="max-w-6xl mx-auto">
    <p class="reveal text-center text-sm font-semibold tracking-[.2em] uppercase text-[#0d7a5f] mb-10">Dipercaya oleh 30+ organisasi di Indonesia</p>
    <div class="reveal grid grid-cols-3 md:grid-cols-6 gap-8 items-center opacity-60">
      @foreach(range(1,12) as $i)
      <div class="flex items-center justify-center h-12">
        <div class="w-full h-8 bg-[#0c3b2e]/15 rounded-lg flex items-center justify-center text-[10px] font-bold text-[#0c3b2e]/40 uppercase tracking-wider">Logo {{ $i }}</div>
      </div>
      @endforeach
    </div>
    <p class="reveal mt-8 text-center text-xs text-[#1a2420]/40">+28 provinsi · 300+ konsultan profesional · 5.000+ kasus ditangani</p>
  </div>
</section>

{{-- ============ VERTIKAL CARDS ============ --}}
<section id="vertikal" class="py-24 px-6">
  <div class="max-w-6xl mx-auto">
    <p class="reveal text-sm font-semibold tracking-[.2em] uppercase text-[#0d7a5f] mb-3">Tiga Vertikal</p>
    <h2 class="reveal font-display text-3xl md:text-4xl font-medium tracking-tight mb-4">Solusi tailored untuk organisasimu.</h2>
    <p class="reveal text-[#1a2420]/70 max-w-2xl text-lg leading-relaxed mb-14">Setiap vertikal punya tantangan berbeda. Pilih yang relevan — atau combine untuk program wellbeing menyeluruh.</p>

    <div class="grid md:grid-cols-3 gap-6">
      {{-- Klinik --}}
      <a href="{{ route('landing.klinik') }}" class="reveal card-hover group bg-white border border-[#0c3b2e]/10 rounded-3xl overflow-hidden">
        <img src="{{ asset('images/landing/klinik-konsultasi.jpg') }}" alt="For Health Facilities" class="w-full h-48 object-cover">
        <div class="p-7">
          <div class="flex items-center gap-3 mb-3">
            <span class="num-outline font-display text-4xl font-semibold text-[#0d7a5f]">01</span>
            <span class="text-xs font-semibold tracking-[.15em] uppercase text-[#0d7a5f]">For Health Facilities</span>
          </div>
          <h3 class="font-display text-2xl font-semibold text-[#0c3b2e]">Klinik & Faskes</h3>
          <p class="mt-2 text-sm text-[#1a2420]/70 leading-relaxed">Tambah layanan psikolog, nutrisionis, fisioterapis tanpa rekrut SDM tetap. White-label checkout atas brand klinik.</p>
          <div class="mt-5 flex flex-wrap gap-2">
            <span class="text-[11px] bg-[#0d7a5f]/10 text-[#0d7a5f] px-2.5 py-1 rounded-full font-medium">Associate Consultant</span>
            <span class="text-[11px] bg-[#0d7a5f]/10 text-[#0d7a5f] px-2.5 py-1 rounded-full font-medium">MHCU</span>
            <span class="text-[11px] bg-[#0d7a5f]/10 text-[#0d7a5f] px-2.5 py-1 rounded-full font-medium">Bagi Hasil</span>
          </div>
          <span class="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-[#0d7a5f] group-hover:gap-2 transition-all">Lihat detail →</span>
        </div>
      </a>

      {{-- Sekolah --}}
      <a href="{{ route('landing.sekolah') }}" class="reveal card-hover group bg-white border border-[#c2540a]/10 rounded-3xl overflow-hidden">
        <img src="{{ asset('images/landing/sekolah-kelas.jpg') }}" alt="For School" class="w-full h-48 object-cover">
        <div class="p-7">
          <div class="flex items-center gap-3 mb-3">
            <span class="num-outline font-display text-4xl font-semibold text-[#c2540a]">02</span>
            <span class="text-xs font-semibold tracking-[.15em] uppercase text-[#c2540a]">For School</span>
          </div>
          <h3 class="font-display text-2xl font-semibold text-[#c2540a]">Sekolah & Kampus</h3>
          <p class="mt-2 text-sm text-[#1a2420]/70 leading-relaxed">Konseling siswa profesional, skrining massal, seminar parenting — tanpa rekrut psikolog full-time.</p>
          <div class="mt-5 flex flex-wrap gap-2">
            <span class="text-[11px] bg-[#c2540a]/10 text-[#c2540a] px-2.5 py-1 rounded-full font-medium">Konseling Siswa</span>
            <span class="text-[11px] bg-[#c2540a]/10 text-[#c2540a] px-2.5 py-1 rounded-full font-medium">MHCU Sekolah</span>
            <span class="text-[11px] bg-[#c2540a]/10 text-[#c2540a] px-2.5 py-1 rounded-full font-medium">Parenting</span>
          </div>
          <span class="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-[#c2540a] group-hover:gap-2 transition-all">Lihat detail →</span>
        </div>
      </a>

      {{-- Perusahaan --}}
      <a href="{{ route('landing.perusahaan') }}" class="reveal card-hover group bg-white border border-[#1d2d50]/10 rounded-3xl overflow-hidden">
        <img src="{{ asset('images/landing/perusahaan-kantor.jpg') }}" alt="For Company" class="w-full h-48 object-cover">
        <div class="p-7">
          <div class="flex items-center gap-3 mb-3">
            <span class="num-outline font-display text-4xl font-semibold text-[#1d2d50]">03</span>
            <span class="text-xs font-semibold tracking-[.15em] uppercase text-[#1d2d50]">For Company</span>
          </div>
          <h3 class="font-display text-2xl font-semibold text-[#1d2d50]">Perusahaan</h3>
          <p class="mt-2 text-sm text-[#1a2420]/70 leading-relaxed">EAP & wellbeing program tanpa rekrut tim internal. Kurangi turnover, absen, burnout dengan data agregat.</p>
          <div class="mt-5 flex flex-wrap gap-2">
            <span class="text-[11px] bg-[#1d2d50]/10 text-[#1d2d50] px-2.5 py-1 rounded-full font-medium">EAP</span>
            <span class="text-[11px] bg-[#1d2d50]/10 text-[#1d2d50] px-2.5 py-1 rounded-full font-medium">MHCU Korporat</span>
            <span class="text-[11px] bg-[#1d2d50]/10 text-[#1d2d50] px-2.5 py-1 rounded-full font-medium">Workshop</span>
          </div>
          <span class="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-[#1d2d50] group-hover:gap-2 transition-all">Lihat detail →</span>
        </div>
      </a>
    </div>
  </div>
</section>

{{-- ============ KENAPA BERBEDA ============ --}}
<section class="py-24 px-6">
  <div class="max-w-6xl mx-auto">
    <p class="reveal text-sm font-semibold tracking-[.2em] uppercase text-[#0d7a5f] mb-3">Kenapa Soluusi</p>
    <h2 class="reveal font-display text-3xl md:text-4xl font-medium tracking-tight mb-12">Bukan sekadar vendor konsultan.</h2>
    <div class="grid md:grid-cols-3 gap-6">
      @foreach([
        ['Multi-Vertikal','Satu platform untuk klinik, sekolah & perusahaan. Cross-sell natural setelah program stabil.'],
        ['Multi-Profesional','Psikolog, nutrisionis, fisioterapis, konsultan karir — bukan satu profesi saja.'],
        ['Online + Offline','Hybrid, 28 provinsi, white-label. Bukan app-only, bukan cuma telemedicine.'],
      ] as [$t,$d])
      <div class="reveal card-hover bg-white border border-[#0c3b2e]/10 rounded-2xl p-7">
        <h3 class="font-display text-xl font-semibold text-[#0c3b2e]">{{ $t }}</h3>
        <p class="mt-2 text-sm text-[#1a2420]/70 leading-relaxed">{{ $d }}</p>
      </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ============ CARA KERJA ============ --}}
<section id="cara" class="py-24 px-6 bg-[#0c3b2e] text-[#f7f4ee]">
  <div class="max-w-6xl mx-auto">
    <div class="grid lg:grid-cols-2 gap-12 items-center mb-14">
      <div>
        <p class="reveal text-sm font-semibold tracking-[.2em] uppercase text-[#7ad1b8] mb-4">Cara Kerja</p>
        <h2 class="reveal font-display text-4xl md:text-5xl font-medium tracking-tight">Dari MoU ke sesi pertama dalam 7 hari.</h2>
      </div>
      <img src="{{ asset('images/landing/partner-highfive.jpg') }}" alt="Kemitraan Soluusi" class="reveal rounded-3xl w-full h-64 lg:h-72 object-cover">
    </div>
    <div class="grid md:grid-cols-4 gap-8">
      @foreach([
        ['01','MoU & Onboarding','Tanda tangan, assign konsultan sesuai kebutuhan, setup portal & white-label.'],
        ['02','Aktivasi Hari 1–30','Jadwal konsultan live, materi promosi, sosialisasi staf — dipandu tim kami.'],
        ['03','Sesi Berjalan','Booking via checkout link, Google Calendar otomatis, dual-confirm anti-fraud.'],
        ['04','Checkpoint 90 Hari','Review performa, dashboard agregat, komisi & bagi hasil cair otomatis.'],
      ] as [$n,$t,$d])
      <div class="reveal border-t border-[#f7f4ee]/20 pt-6">
        <div class="font-display text-sm text-[#7ad1b8]">{{ $n }}</div>
        <h3 class="font-display text-xl font-semibold mt-2">{{ $t }}</h3>
        <p class="mt-2 text-sm text-[#f7f4ee]/70 leading-relaxed">{{ $d }}</p>
      </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ============ KONTAK / CTA ============ --}}
<section id="kontak" class="py-24 px-6 bg-gradient-to-br from-[#0c3b2e] to-[#0d7a5f] text-[#f7f4ee]">
  <div class="max-w-4xl mx-auto text-center">
    <p class="reveal text-sm font-semibold tracking-[.2em] uppercase text-[#7ad1b8] mb-4">Hubungi Kami</p>
    <h2 class="reveal font-display text-4xl md:text-6xl font-medium tracking-tight">Mulai dari 30 menit diskusi.</h2>
    <p class="reveal mt-6 text-lg text-[#f7f4ee]/80 leading-relaxed max-w-2xl mx-auto">Ceritakan kebutuhan organisasimu — tim partnership kami (HPA/SAP/CAP) akan siapkan proposal 1-pager yang relevan. Tanpa komitmen.</p>

    <div class="reveal mt-12 flex flex-col sm:flex-row gap-4 justify-center">
      <a href="https://wa.me/6285117674837?text=Halo%20Soluusi%2C%20saya%20tertarik%20diskusi%20kemitraan%20B2B" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-3 bg-[#25D366] text-white font-bold px-10 py-5 rounded-full hover:bg-[#20bd5a] transition-all hover:scale-105 shadow-2xl">
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
        Chat via WhatsApp
      </a>
      <a href="mailto:info@soluusi.com" class="inline-flex items-center justify-center gap-2 border-2 border-[#f7f4ee]/30 text-[#f7f4ee] font-semibold px-8 py-5 rounded-full hover:bg-[#f7f4ee]/10 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
        Email Kami
      </a>
    </div>

    <div class="reveal mt-12 grid grid-cols-1 md:grid-cols-3 gap-6 text-left">
      <div class="bg-[#f7f4ee]/10 backdrop-blur border border-[#f7f4ee]/20 rounded-2xl p-6">
        <div class="text-[#7ad1b8] text-2xl font-display font-semibold">24 jam</div>
        <div class="text-sm text-[#f7f4ee]/70 mt-1">Response time</div>
      </div>
      <div class="bg-[#f7f4ee]/10 backdrop-blur border border-[#f7f4ee]/20 rounded-2xl p-6">
        <div class="text-[#7ad1b8] text-2xl font-display font-semibold">Free pilot</div>
        <div class="text-sm text-[#f7f4ee]/70 mt-1">Untuk semua vertikal</div>
      </div>
      <div class="bg-[#f7f4ee]/10 backdrop-blur border border-[#f7f4ee]/20 rounded-2xl p-6">
        <div class="text-[#7ad1b8] text-2xl font-display font-semibold">No commitment</div>
        <div class="text-sm text-[#f7f4ee]/70 mt-1">Proposal sesuai skala</div>
      </div>
    </div>

    <div class="reveal mt-12 text-sm text-[#f7f4ee]/60">
      <p class="mb-2">Atau hubungi langsung:</p>
      <p>
        <a href="https://wa.me/6285117674837" class="hover:text-[#7ad1b8] font-semibold">+62 851-1767-4837</a> ·
        <a href="mailto:info@soluusi.com" class="hover:text-[#7ad1b8] font-semibold">info@soluusi.com</a>
      </p>
      <p class="mt-4 text-xs text-[#f7f4ee]/40">PT Saya Soluusi Bangsa Indonesia · AD Premier Office Park, Jakarta Selatan</p>
    </div>
  </div>
</section>

{{-- ============ FOOTER ============ --}}
<footer class="border-t border-[#0c3b2e]/10 py-10 px-6">
  <div class="max-w-6xl mx-auto flex flex-col md:flex-row justify-between gap-8 items-start">
    <div>
      <span class="flex items-center gap-2.5">
        <img src="{{ asset('assets/brand/soluusi.svg') }}" alt="Soluusi" class="h-6">
        <span class="text-[10px] font-bold tracking-widest uppercase bg-[#0c3b2e] text-[#f7f4ee] px-2 py-0.5 rounded">Mitra</span>
      </span>
      <p class="mt-3 text-xs text-[#1a2420]/50 max-w-xs leading-relaxed">Platform wellbeing multi-profesional untuk klinik, sekolah & perusahaan. Bagian dari Soluusi.com.</p>
    </div>
    <div class="text-xs text-[#1a2420]/50 leading-relaxed">
      <p class="font-semibold text-[#0c3b2e]">PT Saya Soluusi Bangsa Indonesia</p>
      <p>AD Premier Office Park, Jl. TB Simatupang No.5, RT.5/RW.7,<br>Ragunan, Ps. Minggu, Jakarta Selatan 12550</p>
      <p class="mt-2">
        <a href="https://wa.me/6285117674837" class="hover:text-[#0d7a5f]">+62 851-1767-4837</a> ·
        <a href="mailto:info@soluusi.com" class="hover:text-[#0d7a5f]">info@soluusi.com</a>
      </p>
    </div>
    <div class="text-xs text-[#1a2420]/50">
      <a href="{{ route('loginIndex') }}" class="hover:text-[#0c3b2e]">Login Mitra / Konsultan →</a><br>
      <a href="{{ route('partner.register') }}" class="hover:text-[#0c3b2e] font-semibold text-[#0c3b2e]/80">Daftar sebagai Mitra →</a>
      <p class="mt-2">© {{ date('Y') }} Soluusi. Confidentiality & UU PDP compliant.</p>
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