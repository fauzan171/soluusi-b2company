@extends('layouts.app')

@php($title = 'Pendaftaran Mitra Klinik')

@section('content')
<style>
    .partner-register-shell { box-shadow: 0 30px 80px rgba(6, 42, 32, .12); }
    .partner-register-aside.partner-register-grid {
        background-color: #062a20;
        background-image: linear-gradient(rgba(255,255,255,.035) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.035) 1px, transparent 1px), radial-gradient(circle at 88% 11%, rgba(241,90,35,.28), transparent 22rem), radial-gradient(circle at 4% 88%, rgba(74,163,124,.23), transparent 25rem), linear-gradient(150deg, #062a20 0%, #0c3b2e 56%, #0c5b3e 100%);
        background-size: 32px 32px, 32px 32px, auto, auto, auto;
        background-repeat: repeat, repeat, no-repeat, no-repeat, no-repeat;
    }
    .partner-field {
        width: 100%; border: 1px solid #dcd7cd; border-radius: .85rem; background: #fff; padding: .78rem .95rem; color: #1a2420; outline: none;
        transition: border-color .18s ease, box-shadow .18s ease;
    }
    .partner-field::placeholder { color: #9c9a94; }
    .partner-field:hover { border-color: #b0c9bc; }
    .partner-field:focus { border-color: #007648; box-shadow: 0 0 0 4px rgba(0,118,72,.1); }
    .partner-field.is-invalid { border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,.08); }
    .partner-terms-content { scrollbar-color: #7fc1a0 #edf1ed; scrollbar-width: thin; }
    .partner-terms-content h3 { color: #0c5b3e; border-top: 1px solid #e7e2d9; padding-top: 1.15rem; }
    .partner-terms-content table { background: white; }
    .partner-terms-content strong { color: #23352e; }
    .partner-check-card:has(input:checked) { border-color: #4aa37c; background: #eef7f2; box-shadow: 0 0 0 3px rgba(0,118,72,.07); }
    .partner-check-card:has(input:disabled) { opacity: .62; }
    .partner-submit:not(:disabled):hover { transform: translateY(-1px); box-shadow: 0 14px 30px rgba(0,118,72,.22); }
    @keyframes partner-rise { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    .partner-rise { animation: partner-rise .55s ease both; }
    @media (prefers-reduced-motion: reduce) { .partner-rise { animation: none; } .partner-field, .partner-submit { transition: none; } }
</style>

<div class="partner-register-shell mx-auto max-w-[1280px] overflow-hidden border border-[#dcd7cd] bg-white lg:grid lg:grid-cols-[.78fr_1.22fr]">
    <aside class="partner-register-aside partner-register-grid relative overflow-hidden px-7 py-9 text-white sm:px-10 lg:min-h-[calc(100vh-9rem)] lg:px-12 lg:py-12">
        <div aria-hidden="true" class="absolute -right-16 top-24 h-48 w-48 rounded-full border border-white/10"></div>
        <div aria-hidden="true" class="absolute -right-4 top-36 h-28 w-28 rounded-full border border-terra-400/40"></div>
        <div class="relative lg:sticky lg:top-24">
            <div class="inline-flex w-fit items-center rounded-lg bg-white px-3 py-2 shadow-sm">
                <img src="{{ asset('assets/brand/soluusi.svg') }}" alt="Soluusi" class="h-7 w-auto">
            </div>

            <div class="partner-rise mt-14 max-w-md">
                <div class="mb-5 flex items-center gap-3"><span class="h-px w-10 bg-terra-400"></span><p class="text-xs font-semibold uppercase tracking-[.22em] text-pine-200">Untuk Fasilitas Kesehatan</p></div>
                <h1 class="text-[2.6rem] font-semibold leading-[1.03] tracking-[-.045em] sm:text-5xl">Daftarkan mitra,<br><span class="text-pine-200">aktifkan layanan.</span></h1>
                <p class="mt-6 max-w-sm text-[15px] leading-7 text-white/70">Lengkapi data faskes agar tim partnership bisa menilai kebutuhan layanan, lokasi, dan estimasi volume sesi.</p>
            </div>

            <div class="relative mt-16 border-l border-white/15 pl-7">
                <p class="mb-5 text-[11px] font-semibold uppercase tracking-[.2em] text-white/45">Setelah mendaftar</p>
                <ol class="space-y-5">
                    <li class="relative flex gap-4"><span class="absolute -left-[2.05rem] top-1 grid h-5 w-5 place-items-center rounded-full bg-terra-500 text-[10px] font-bold">1</span><div><p class="text-sm font-semibold">Verifikasi data klinik</p><p class="mt-1 text-xs leading-5 text-white/55">Tim kami meninjau data dalam 1×24 jam kerja.</p></div></li>
                    <li class="relative flex gap-4"><span class="absolute -left-[2.05rem] top-1 grid h-5 w-5 place-items-center rounded-full border border-white/25 bg-pine-900 text-[10px] font-bold">2</span><div><p class="text-sm font-semibold">Diskusi bersama HPA</p><p class="mt-1 text-xs leading-5 text-white/55">Tentukan skema layanan yang paling sesuai.</p></div></li>
                    <li class="relative flex gap-4"><span class="absolute -left-[2.05rem] top-1 grid h-5 w-5 place-items-center rounded-full border border-white/25 bg-pine-900 text-[10px] font-bold">3</span><div><p class="text-sm font-semibold">Onboarding &amp; go-live</p><p class="mt-1 text-xs leading-5 text-white/55">Akun, konsultan, dan alur rujukan disiapkan bersama.</p></div></li>
                </ol>
            </div>
            <div class="mt-9 flex flex-wrap gap-x-6 gap-y-2 border-t border-white/10 pt-5 text-[11px] text-white/45">
                <span class="inline-flex items-center gap-2"><svg class="h-4 w-4 text-pine-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>Data terlindungi</span>
                <span>Tanpa biaya pendaftaran</span>
            </div>
        </div>
    </aside>

    <section class="bg-[#fcfbf8] px-6 py-9 sm:px-10 lg:px-14 lg:py-12">
        <div class="mx-auto max-w-2xl">
            <div class="partner-rise flex flex-col gap-4 border-b border-[#ded9cf] pb-8 sm:flex-row sm:items-end sm:justify-between">
                <div><p class="text-xs font-semibold uppercase tracking-[.18em] text-pine-700">Pendaftaran Mitra Klinik</p><h2 class="mt-2 text-3xl font-semibold tracking-[-.035em] text-pine-950">Data awal kemitraan.</h2><p class="mt-2 text-sm leading-6 text-[#66716c]">Isi data mitra, brand layanan, dan PIC yang berwenang mewakili fasilitas kesehatan.</p></div>
                <div class="shrink-0 text-xs text-[#7c827f]"><span class="text-terra-600">●</span> Wajib diisi</div>
            </div>

            <form method="POST" action="{{ route('partner.register.store') }}" class="mt-9 space-y-11">
                @csrf
                <fieldset>
                    <legend class="mb-6 flex items-center gap-4"><span class="grid h-9 w-9 place-items-center rounded-full bg-pine-900 text-xs font-bold text-white">01</span><span><span class="block text-base font-semibold text-pine-950">Identitas klinik</span><span class="mt-0.5 block text-xs text-[#7c827f]">Informasi yang akan kami verifikasi</span></span></legend>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="org_name" class="mb-2 block text-sm font-medium text-[#26342e]">Nama resmi mitra / badan usaha <span class="text-terra-600">*</span></label>
                            <input id="org_name" name="org_name" value="{{ old('org_name') }}" required autocomplete="organization" placeholder="Contoh: PT Klinik Sehat Sentosa" class="partner-field @error('org_name') is-invalid @enderror">
                            @error('org_name')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="brand" class="mb-2 block text-sm font-medium text-[#26342e]">Nama brand yang dilihat pasien <span class="font-normal text-[#8d918f]">(opsional)</span></label>
                            <input id="brand" name="brand" value="{{ old('brand') }}" autocomplete="organization" placeholder="Contoh: Klinik Sehat Sentosa" class="partner-field @error('brand') is-invalid @enderror">
                            <p class="mt-1.5 text-xs text-[#8d918f]">Dipakai untuk tampilan link booking customer. Jika kosong, nama resmi mitra digunakan.</p>
                            @error('brand')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="facility_type" class="mb-2 block text-sm font-medium text-[#26342e]">Tipe fasilitas <span class="text-terra-600">*</span></label>
                            <select id="facility_type" name="facility_type" required class="partner-field @error('facility_type') is-invalid @enderror">
                                <option value="">Pilih tipe faskes</option>
                                @foreach([
                                    'klinik_pratama' => 'Klinik Pratama',
                                    'klinik_utama' => 'Klinik Utama',
                                    'rumah_sakit' => 'Rumah Sakit',
                                    'laboratorium' => 'Laboratorium Klinik',
                                    'klinik_tumbuh_kembang' => 'Klinik Tumbuh Kembang',
                                    'klinik_kecantikan' => 'Klinik Kecantikan',
                                    'klinik_korporat' => 'Klinik Korporat / Occupational Health',
                                    'lainnya' => 'Lainnya',
                                ] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('facility_type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('facility_type')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="branch_name" class="mb-2 block text-sm font-medium text-[#26342e]">Nama cabang <span class="font-normal text-[#8d918f]">(opsional)</span></label>
                            <input id="branch_name" name="branch_name" value="{{ old('branch_name') }}" placeholder="Contoh: Cabang Jakarta Selatan" class="partner-field @error('branch_name') is-invalid @enderror">
                            @error('branch_name')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="regency_id" class="mb-2 block text-sm font-medium text-[#26342e]">Kota / kabupaten <span class="text-terra-600">*</span></label>
                            <div class="relative"><select id="regency_id" name="regency_id" required class="partner-field appearance-none pr-10 @error('regency_id') is-invalid @enderror"><option value="">Pilih lokasi klinik</option>@foreach($regencies as $r)<option value="{{ $r->id }}" @selected(old('regency_id') == $r->id)>{{ $r->name }}</option>@endforeach</select><svg class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#68736e]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></div>
                            @error('regency_id')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="estimated_monthly_sessions" class="mb-2 block text-sm font-medium text-[#26342e]">Estimasi sesi / bulan <span class="font-normal text-[#8d918f]">(opsional)</span></label>
                            <input id="estimated_monthly_sessions" type="number" min="0" max="10000" name="estimated_monthly_sessions" value="{{ old('estimated_monthly_sessions') }}" placeholder="Contoh: 20" class="partner-field @error('estimated_monthly_sessions') is-invalid @enderror">
                            @error('estimated_monthly_sessions')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="service_interest" class="mb-2 block text-sm font-medium text-[#26342e]">Layanan yang ingin diaktifkan <span class="text-terra-600">*</span></label>
                            <select id="service_interest" name="service_interest" required class="partner-field @error('service_interest') is-invalid @enderror">
                                <option value="">Pilih kebutuhan utama</option>
                                @foreach([
                                    'psikolog' => 'Psikolog / Konseling',
                                    'nutrisi' => 'Nutrisi / Gizi',
                                    'fisioterapi' => 'Fisioterapi',
                                    'mhcu' => 'Mental Health Check-Up',
                                    'edukasi' => 'Seminar / Edukasi',
                                    'paket_custom' => 'Paket custom sesuai kebutuhan',
                                ] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('service_interest') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('service_interest')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="address" class="mb-2 block text-sm font-medium text-[#26342e]">Alamat klinik / faskes <span class="font-normal text-[#8d918f]">(opsional)</span></label>
                            <textarea id="address" name="address" rows="2" placeholder="Alamat utama yang dipakai untuk koordinasi layanan offline" class="partner-field @error('address') is-invalid @enderror">{{ old('address') }}</textarea>
                            @error('address')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </fieldset>

                <fieldset class="border-t border-[#e5e0d6] pt-9">
                    <legend class="mb-6 flex items-center gap-4 pr-4"><span class="grid h-9 w-9 place-items-center rounded-full bg-pine-900 text-xs font-bold text-white">02</span><span><span class="block text-base font-semibold text-pine-950">PIC &amp; akses akun</span><span class="mt-0.5 block text-xs text-[#7c827f]">Kontak utama selama proses kemitraan</span></span></legend>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2"><label for="pic_name" class="mb-2 block text-sm font-medium text-[#26342e]">Nama lengkap PIC <span class="text-terra-600">*</span></label><input id="pic_name" name="pic_name" value="{{ old('pic_name') }}" required autocomplete="name" placeholder="Nama penanggung jawab kemitraan" class="partner-field @error('pic_name') is-invalid @enderror">@error('pic_name')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div><label for="email" class="mb-2 block text-sm font-medium text-[#26342e]">Email kerja <span class="text-terra-600">*</span></label><input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" inputmode="email" placeholder="pic@klinik.com" class="partner-field @error('email') is-invalid @enderror">@error('email')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div><label for="phone" class="mb-2 block text-sm font-medium text-[#26342e]">Nomor HP / WhatsApp <span class="text-terra-600">*</span></label><input id="phone" name="phone" value="{{ old('phone') }}" required autocomplete="tel" inputmode="tel" placeholder="08xxxxxxxxxx" class="partner-field @error('phone') is-invalid @enderror">@error('phone')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div>
                            <label for="password" class="mb-2 block text-sm font-medium text-[#26342e]">Buat password <span class="text-terra-600">*</span></label>
                            <div class="relative"><input id="password" type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="Minimal 8 karakter" class="partner-field pr-12 @error('password') is-invalid @enderror"><button type="button" data-password-toggle="password" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-md p-1 text-[#7c827f] hover:bg-pine-50 hover:text-pine-700" aria-label="Tampilkan password"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg></button></div>
                            @error('password')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="mb-2 block text-sm font-medium text-[#26342e]">Ulangi password <span class="text-terra-600">*</span></label>
                            <div class="relative"><input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" placeholder="Ketik ulang password" class="partner-field pr-12"><button type="button" data-password-toggle="password_confirmation" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-md p-1 text-[#7c827f] hover:bg-pine-50 hover:text-pine-700" aria-label="Tampilkan ulang password"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg></button></div>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="border-t border-[#e5e0d6] pt-9">
                    <legend class="mb-6 flex items-center gap-4 pr-4"><span class="grid h-9 w-9 place-items-center rounded-full bg-pine-900 text-xs font-bold text-white">03</span><span><span class="block text-base font-semibold text-pine-950">Persetujuan kemitraan</span><span class="mt-0.5 block text-xs text-[#7c827f]">Baca dokumen sampai selesai untuk melanjutkan</span></span></legend>
                    <div class="overflow-hidden border border-[#dcd7cd] bg-white shadow-[0_8px_25px_rgba(12,59,46,.05)]">
                        <div class="flex items-center justify-between gap-4 border-b border-[#e5e0d6] bg-[#f3f6f2] px-4 py-3 sm:px-5">
                            <div class="flex min-w-0 items-center gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-pine-900 text-white"><svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M8 13h8M8 17h6"/></svg></span><div class="min-w-0"><p class="truncate text-sm font-semibold text-pine-950">S&amp;K Mitra Klinik</p><p class="text-[11px] text-[#77807c]">19 bagian · dokumen kemitraan</p></div></div>
                            <span id="termsProgressText" class="shrink-0 rounded-full bg-white px-3 py-1 text-[11px] font-semibold text-pine-700 ring-1 ring-[#d8e5dc]" aria-live="polite">0% dibaca</span>
                        </div>
                        <div class="h-1 bg-[#e7ebe7]" aria-hidden="true"><div id="termsProgressBar" class="h-full w-0 bg-gradient-to-r from-pine-500 to-terra-500 transition-[width] duration-150"></div></div>
                        <div id="termsBox" tabindex="0" aria-label="Syarat dan Ketentuan Kemitraan Klinik" class="partner-terms-content h-[390px] overflow-y-auto scroll-smooth px-5 py-6 text-[13px] leading-6 text-[#59645f] outline-none focus:ring-4 focus:ring-inset focus:ring-pine-100 sm:h-[430px] sm:px-8">
                            @include('auth.partials.terms-klinik')
                            <div class="mt-6 border-t border-dashed border-pine-200 pt-5 text-center"><span class="inline-flex items-center gap-2 rounded-full bg-pine-50 px-4 py-2 text-xs font-semibold text-pine-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>Akhir dokumen</span></div>
                        </div>
                        <div class="border-t border-[#e5e0d6] bg-[#fbfaf7] px-4 py-4 sm:px-5"><div id="termsHint" class="flex items-center gap-3 text-xs font-medium text-amber-700" role="status"><span id="termsHintIcon" class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-amber-100"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></span><span id="termsHintText">Gulir dokumen sampai bagian paling bawah.</span></div></div>
                    </div>
                    <label for="termsCheck" class="partner-check-card mt-4 flex cursor-pointer items-start gap-3 rounded-xl border border-[#dcd7cd] bg-white p-4 transition sm:p-5">
                        <input type="checkbox" name="terms" value="1" id="termsCheck" disabled @checked(old('terms')) class="mt-0.5 h-5 w-5 shrink-0 cursor-pointer accent-[#007648] disabled:cursor-not-allowed">
                        <span><span class="block text-sm font-semibold leading-5 text-[#26342e]">Saya menyetujui Syarat &amp; Ketentuan</span><span class="mt-1 block text-xs leading-5 text-[#707874]">Saya telah membaca, memahami, dan menyetujuinya atas nama Klinik/Fasilitas Kesehatan yang saya daftarkan.</span></span>
                    </label>
                    @error('terms')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </fieldset>

                <div class="border-t border-[#e5e0d6] pt-7">
                    <button id="submitBtn" disabled class="partner-submit group flex w-full items-center justify-center gap-3 rounded-xl bg-pine-700 px-5 py-4 text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:bg-[#b8beb9] disabled:text-white/80"><span>Daftarkan Klinik</span><svg class="h-4 w-4 transition-transform group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
                    <p class="mt-4 text-center text-xs text-[#7c827f]">Sudah memiliki akun? <a href="{{ route('loginIndex') }}" class="font-semibold text-pine-700 underline decoration-pine-200 underline-offset-4 hover:text-pine-900">Masuk ke portal</a></p>
                </div>
            </form>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const box = document.getElementById('termsBox');
    const check = document.getElementById('termsCheck');
    const hint = document.getElementById('termsHint');
    const hintText = document.getElementById('termsHintText');
    const progressBar = document.getElementById('termsProgressBar');
    const progressText = document.getElementById('termsProgressText');
    const submitButton = document.getElementById('submitBtn');
    let unlocked = false;

    const syncSubmit = () => { submitButton.disabled = !check.checked; };
    const unlockAgreement = () => {
        if (unlocked) return;
        unlocked = true;
        check.disabled = false;
        hint.classList.remove('text-amber-700');
        hint.classList.add('text-pine-700');
        document.getElementById('termsHintIcon').classList.replace('bg-amber-100', 'bg-pine-100');
        hintText.textContent = 'Dokumen selesai dibaca. Centang persetujuan untuk melanjutkan.';
    };
    const updateReadingProgress = () => {
        const scrollable = Math.max(box.scrollHeight - box.clientHeight, 0);
        const progress = scrollable === 0 ? 100 : Math.min(100, Math.round((box.scrollTop / scrollable) * 100));
        progressBar.style.width = `${progress}%`;
        progressText.textContent = progress >= 100 ? 'Selesai dibaca' : `${progress}% dibaca`;
        if (scrollable === 0 || box.scrollTop + box.clientHeight >= box.scrollHeight - 20) unlockAgreement();
    };

    check.addEventListener('change', syncSubmit);
    box.addEventListener('scroll', updateReadingProgress, { passive: true });
    if (check.checked) { unlockAgreement(); syncSubmit(); }
    document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const input = document.getElementById(toggle.dataset.passwordToggle);
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            toggle.setAttribute('aria-label', showing ? 'Tampilkan password' : 'Sembunyikan password');
        });
    });
    updateReadingProgress();
});
</script>
@endsection
