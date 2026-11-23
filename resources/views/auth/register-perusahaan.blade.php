@extends('layouts.app')

@php($title = 'Pendaftaran Perusahaan')

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
    .partner-package-card:has(input:checked) { border-color: #007648; background: #eef7f2; }
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
                <div class="mb-5 flex items-center gap-3"><span class="h-px w-10 bg-terra-400"></span><p class="text-xs font-semibold uppercase tracking-[.22em] text-pine-200">Untuk Perusahaan</p></div>
                <h1 class="text-[2.6rem] font-semibold leading-[1.03] tracking-[-.045em] sm:text-5xl">Daftarkan perusahaan,<br><span class="text-pine-200">aktifkan wellbeing tim.</span></h1>
                <p class="mt-6 max-w-sm text-[15px] leading-7 text-white/70">Lengkapi data perusahaan agar tim kami bisa menyiapkan program wellbeing yang paling sesuai untuk karyawan Anda.</p>
            </div>

            <div class="relative mt-16 border-l border-white/15 pl-7">
                <p class="mb-5 text-[11px] font-semibold uppercase tracking-[.2em] text-white/45">Setelah mendaftar</p>
                <ol class="space-y-5">
                    <li class="relative flex gap-4"><span class="absolute -left-[2.05rem] top-1 grid h-5 w-5 place-items-center rounded-full bg-terra-500 text-[10px] font-bold">1</span><div><p class="text-sm font-semibold">Verifikasi data perusahaan</p><p class="mt-1 text-xs leading-5 text-white/55">Tim kami menghubungi Anda untuk verifikasi data.</p></div></li>
                    <li class="relative flex gap-4"><span class="absolute -left-[2.05rem] top-1 grid h-5 w-5 place-items-center rounded-full border border-white/25 bg-pine-900 text-[10px] font-bold">2</span><div><p class="text-sm font-semibold">Penetapan paket &amp; band harga</p><p class="mt-1 text-xs leading-5 text-white/55">Pilih paket dan band jumlah karyawan yang sesuai.</p></div></li>
                    <li class="relative flex gap-4"><span class="absolute -left-[2.05rem] top-1 grid h-5 w-5 place-items-center rounded-full border border-white/25 bg-pine-900 text-[10px] font-bold">3</span><div><p class="text-sm font-semibold">Aktivasi program</p><p class="mt-1 text-xs leading-5 text-white/55">Akun HR dan akses platform disiapkan bersama.</p></div></li>
                </ol>
            </div>
            <div class="mt-9 flex flex-wrap gap-x-6 gap-y-2 border-t border-white/10 pt-5 text-[11px] text-white/45">
                <span class="inline-flex items-center gap-2"><svg class="h-4 w-4 text-pine-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>Data karyawan terlindungi</span>
                <span>Tanpa biaya pendaftaran</span>
            </div>
        </div>
    </aside>

    <section class="bg-[#fcfbf8] px-6 py-9 sm:px-10 lg:px-14 lg:py-12">
        <div class="mx-auto max-w-2xl">
            <div class="partner-rise flex flex-col gap-4 border-b border-[#ded9cf] pb-8 sm:flex-row sm:items-end sm:justify-between">
                <div><p class="text-xs font-semibold uppercase tracking-[.18em] text-pine-700">Pendaftaran Perusahaan</p><h2 class="mt-2 text-3xl font-semibold tracking-[-.035em] text-pine-950">Daftar Sekarang.</h2><p class="mt-2 text-sm leading-6 text-[#66716c]">Isi data perusahaan, kontak HR, dan paket yang diminati. Tim kami akan menghubungi Anda untuk aktivasi.</p></div>
                <div class="shrink-0 text-xs text-[#7c827f]"><span class="text-terra-600">●</span> Wajib diisi</div>
            </div>

            <form method="POST" action="{{ route('perusahaan.register.store') }}" class="mt-9 space-y-11">
                @csrf
                <fieldset>
                    <legend class="mb-6 flex items-center gap-4"><span class="grid h-9 w-9 place-items-center rounded-full bg-pine-900 text-xs font-bold text-white">01</span><span><span class="block text-base font-semibold text-pine-950">Identitas perusahaan</span><span class="mt-0.5 block text-xs text-[#7c827f]">Informasi yang akan kami verifikasi</span></span></legend>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="org_name" class="mb-2 block text-sm font-medium text-[#26342e]">Nama perusahaan <span class="text-terra-600">*</span></label>
                            <input id="org_name" name="org_name" value="{{ old('org_name') }}" required autocomplete="organization" placeholder="Contoh: PT Maju Bersama Indonesia" class="partner-field @error('org_name') is-invalid @enderror">
                            @error('org_name')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="employee_band" class="mb-2 block text-sm font-medium text-[#26342e]">Jumlah karyawan <span class="text-terra-600">*</span></label>
                            <select id="employee_band" name="employee_band" required class="partner-field @error('employee_band') is-invalid @enderror">
                                <option value="">Pilih rentang jumlah karyawan</option>
                                @foreach($bands as $value => $label)
                                    <option value="{{ $value }}" @selected(old('employee_band') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('employee_band')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="phone" class="mb-2 block text-sm font-medium text-[#26342e]">Nomor HP / WhatsApp <span class="text-terra-600">*</span></label>
                            <input id="phone" name="phone" value="{{ old('phone') }}" required autocomplete="tel" inputmode="tel" placeholder="08xxxxxxxxxx" class="partner-field @error('phone') is-invalid @enderror">
                            @error('phone')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </fieldset>

                <fieldset class="border-t border-[#e5e0d6] pt-9">
                    <legend class="mb-6 flex items-center gap-4 pr-4"><span class="grid h-9 w-9 place-items-center rounded-full bg-pine-900 text-xs font-bold text-white">02</span><span><span class="block text-base font-semibold text-pine-950">Kontak HR</span><span class="mt-0.5 block text-xs text-[#7c827f]">Kontak utama selama proses aktivasi</span></span></legend>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div><label for="hr_name" class="mb-2 block text-sm font-medium text-[#26342e]">Nama HR <span class="text-terra-600">*</span></label><input id="hr_name" name="hr_name" value="{{ old('hr_name') }}" required autocomplete="name" placeholder="Nama HR / penanggung jawab" class="partner-field @error('hr_name') is-invalid @enderror">@error('hr_name')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div><label for="hr_email" class="mb-2 block text-sm font-medium text-[#26342e]">Email HR <span class="text-terra-600">*</span></label><input id="hr_email" type="email" name="hr_email" value="{{ old('hr_email') }}" required autocomplete="email" inputmode="email" placeholder="hr@perusahaan.com" class="partner-field @error('hr_email') is-invalid @enderror">@error('hr_email')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    </div>
                </fieldset>

                <fieldset class="border-t border-[#e5e0d6] pt-9">
                    <legend class="mb-6 flex items-center gap-4 pr-4"><span class="grid h-9 w-9 place-items-center rounded-full bg-pine-900 text-xs font-bold text-white">03</span><span><span class="block text-base font-semibold text-pine-950">Pilih paket</span><span class="mt-0.5 block text-xs text-[#7c827f]">Paket dapat disesuaikan kembali saat aktivasi</span></span></span></legend>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach($packages as $value => $label)
                            <label class="partner-package-card flex cursor-pointer items-start gap-3 rounded-xl border border-[#dcd7cd] bg-white p-4 transition">
                                <input type="radio" name="package_code" value="{{ $value }}" @checked(old('package_code') === $value || (!$errors->any() && $value === 'discovery')) class="mt-0.5 h-5 w-5 shrink-0 cursor-pointer accent-[#007648]">
                                <span><span class="block text-sm font-semibold leading-5 text-[#26342e]">{{ explode(' (', $label)[0] }}</span><span class="mt-1 block text-xs leading-5 text-[#707874]">{{ str_replace(['(',')'], ['', ''], '(' . explode(' (', $label)[1]) }}</span></span>
                            </label>
                        @endforeach
                    </div>
                    @error('package_code')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </fieldset>

                <fieldset class="border-t border-[#e5e0d6] pt-9">
                    <legend class="mb-6 flex items-center gap-4 pr-4"><span class="grid h-9 w-9 place-items-center rounded-full bg-pine-900 text-xs font-bold text-white">04</span><span><span class="block text-base font-semibold text-pine-950">Persetujuan</span><span class="mt-0.5 block text-xs text-[#7c827f]">Baca dokumen sampai selesai untuk melanjutkan</span></span></legend>
                    <div class="overflow-hidden border border-[#dcd7cd] bg-white shadow-[0_8px_25px_rgba(12,59,46,.05)]">
                        <div class="flex items-center justify-between gap-4 border-b border-[#e5e0d6] bg-[#f3f6f2] px-4 py-3 sm:px-5">
                            <div class="flex min-w-0 items-center gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-pine-900 text-white"><svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M8 13h8M8 17h6"/></svg></span><div class="min-w-0"><p class="truncate text-sm font-semibold text-pine-950">S&amp;K Perusahaan</p><p class="text-[11px] text-[#77807c]">19 bagian · dokumen layanan</p></div></div>
                            <span id="termsProgressText" class="shrink-0 rounded-full bg-white px-3 py-1 text-[11px] font-semibold text-pine-700 ring-1 ring-[#d8e5dc]" aria-live="polite">0% dibaca</span>
                        </div>
                        <div class="h-1 bg-[#e7ebe7]" aria-hidden="true"><div id="termsProgressBar" class="h-full w-0 bg-gradient-to-r from-pine-500 to-terra-500 transition-[width] duration-150"></div></div>
                        <div id="termsBox" tabindex="0" aria-label="Syarat dan Ketentuan Layanan Wellbeing Perusahaan" class="partner-terms-content h-[390px] overflow-y-auto scroll-smooth px-5 py-6 text-[13px] leading-6 text-[#59645f] outline-none focus:ring-4 focus:ring-inset focus:ring-pine-100 sm:h-[430px] sm:px-8">
                            @include('auth.partials.terms-perusahaan')
                            <div class="mt-6 border-t border-dashed border-pine-200 pt-5 text-center"><span class="inline-flex items-center gap-2 rounded-full bg-pine-50 px-4 py-2 text-xs font-semibold text-pine-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>Akhir dokumen</span></div>
                        </div>
                        <div class="border-t border-[#e5e0d6] bg-[#fbfaf7] px-4 py-4 sm:px-5"><div id="termsHint" class="flex items-center gap-3 text-xs font-medium text-amber-700" role="status"><span id="termsHintIcon" class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-amber-100"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></span><span id="termsHintText">Gulir dokumen sampai bagian paling bawah.</span></div></div>
                    </div>
                    <label for="termsCheck" class="partner-check-card mt-4 flex cursor-pointer items-start gap-3 rounded-xl border border-[#dcd7cd] bg-white p-4 transition sm:p-5">
                        <input type="checkbox" name="terms" value="1" id="termsCheck" disabled @checked(old('terms')) class="mt-0.5 h-5 w-5 shrink-0 cursor-pointer accent-[#007648] disabled:cursor-not-allowed">
                        <span><span class="block text-sm font-semibold leading-5 text-[#26342e]">Saya menyetujui Syarat &amp; Ketentuan</span><span class="mt-1 block text-xs leading-5 text-[#707874]">Saya telah membaca, memahami, dan menyetujuinya atas nama Perusahaan yang saya daftarkan.</span></span>
                    </label>
                    @error('terms')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </fieldset>

                <div class="border-t border-[#e5e0d6] pt-7">
                    <button id="submitBtn" disabled class="partner-submit group flex w-full items-center justify-center gap-3 rounded-xl bg-pine-700 px-5 py-4 text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:bg-[#b8beb9] disabled:text-white/80"><span>Daftar Sekarang</span><svg class="h-4 w-4 transition-transform group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
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
    updateReadingProgress();
});
</script>
@endsection
