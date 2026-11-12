@extends('layouts.app')

@section('title', 'Roster Siswa — ' . $sekolah->brand)

@section('content')
<div x-data="{ tab: 'roster', refer: null, search: '', filterKelas: '', filterConsent: '' }">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <h1 class="text-xl font-bold text-pine-900">Roster Siswa</h1>
            <p class="text-sm text-gray-500">Kelola partisipasi program. Data di halaman ini untuk pengelolaan — konten sesi tetap rahasia.</p>
        </div>
        <button @click="tab = 'tambah'" class="inline-flex items-center gap-1.5 rounded-xl bg-pine-700 px-4 py-2 text-sm font-semibold text-white hover:bg-pine-800 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Tambah Siswa
        </button>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-pine-200 bg-pine-50 px-4 py-3 text-sm text-pine-800">{{ session('success') }}</div>
    @endif

    {{-- Refer konseling (form tersembunyi, dibuka dari baris) --}}
    <div x-show="refer" x-cloak class="portal-card p-5 mb-4" x-data="{
        form: { student_id: '', konsultan_type: 'psikolog', mode: 'online', scheduled_at: '' },
        init() { this.$watch('refer', v => { if (v) this.form.student_id = v.id }) }
    }">
        <h3 class="font-semibold text-pine-900">Refer konseling — <span x-text="refer ? refer.name : ''"></span></h3>
        <form method="POST" action="{{ route('sekolah.students.refer') }}" class="grid md:grid-cols-4 gap-3 mt-3">
            @csrf
            <input type="hidden" name="student_id" :value="form.student_id">
            <select name="konsultan_type" class="w-full rounded-xl border border-[#e7e2d9] bg-white px-3 py-2 text-sm focus:border-pine-400 focus:ring-1 focus:ring-pine-200" x-model="form.konsultan_type">
                <option value="psikolog">Psikolog Anak & Remaja</option>
                <option value="karir">Konselor Karir</option>
            </select>
            <label class="text-sm md:col-span-2">Slot konsultan (WIB)
                <select name="schedule_id" required class="mt-1 w-full rounded-xl border border-[#e7e2d9] bg-white px-3 py-2 text-sm focus:border-pine-400 focus:ring-1 focus:ring-pine-200">
                    <option value="">— Pilih slot sesuai jenis konsultan —</option>
                    @foreach($slots as $slot)
                    <option value="{{ $slot->id }}">{{ $slot->started_at->translatedFormat('d M Y H.i') }} · {{ $slot->consultant->user->firstname }} · {{ $slot->mode }}</option>
                    @endforeach
                </select>
                @error('schedule_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </label>
            <button class="self-end rounded-xl bg-pine-700 px-4 py-2 text-sm font-semibold text-white hover:bg-pine-800 transition">Jadwalkan</button>
        </form>
    </div>

    {{-- Filter & pencarian --}}
    <section class="portal-card p-4 mb-4">
        <div class="grid gap-3 md:grid-cols-4">
            <div class="md:col-span-2">
                <input type="search" x-model="search" placeholder="Cari nama atau email…" class="w-full rounded-xl border border-[#e7e2d9] bg-white px-3 py-2 text-sm focus:border-pine-400 focus:ring-1 focus:ring-pine-200">
            </div>
            <select x-model="filterKelas" class="w-full rounded-xl border border-[#e7e2d9] bg-white px-3 py-2 text-sm focus:border-pine-400 focus:ring-1 focus:ring-pine-200">
                <option value="">Semua kelas</option>
                @foreach($students->pluck('kelas')->unique()->sort() as $kelas)
                    <option value="{{ $kelas }}">{{ $kelas }}</option>
                @endforeach
            </select>
            <select x-model="filterConsent" class="w-full rounded-xl border border-[#e7e2d9] bg-white px-3 py-2 text-sm focus:border-pine-400 focus:ring-1 focus:ring-pine-200">
                <option value="">Semua status consent</option>
                <option value="disetujui">Consent disetujui</option>
                <option value="belum">Consent belum</option>
            </select>
        </div>
    </section>

    <section class="portal-card p-5">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-500 border-b border-[#e7e2d9]">
                        <th class="py-2 pr-4">Nama</th>
                        <th class="py-2 pr-4">Kelas</th>
                        <th class="py-2 pr-4">Email</th>
                        <th class="py-2 pr-4">Consent ortu</th>
                        <th class="py-2 pr-4">Akun</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $s)
                    <tr class="border-b border-[#e7e2d9]/60 hover:bg-pine-50/40 transition"
                        x-show="
                            (!search || '{{ Str::lower($s->name) }}'.includes(search.toLowerCase()) || '{{ Str::lower($s->email ?? '') }}'.includes(search.toLowerCase())) &&
                            (!filterKelas || '{{ $s->kelas }}' === filterKelas) &&
                            (!filterConsent || (filterConsent === 'disetujui' && {{ $s->parent_consent_at ? 'true' : 'false' }}) || (filterConsent === 'belum' && !{{ $s->parent_consent_at ? 'true' : 'false' }}))
                        ">
                        <td class="py-3 pr-4 font-medium text-gray-900">{{ $s->name }}</td>
                        <td class="py-3 pr-4 text-gray-600">{{ $s->kelas }}</td>
                        <td class="py-3 pr-4 text-gray-500">{{ $s->email ?? '—' }}</td>
                        <td class="py-3 pr-4">
                            @if($s->parent_consent_at)
                                <span class="inline-flex items-center gap-1 rounded-full bg-pine-100 px-2.5 py-1 text-xs font-semibold text-pine-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    Disetujui
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-terra-100 px-2.5 py-1 text-xs font-semibold text-terra-800">Belum</span>
                            @endif
                            <details class="mt-1.5 text-xs text-gray-500">
                                <summary class="cursor-pointer hover:text-pine-700">Catat/koreksi izin wali eksternal</summary>
                                <form method="POST" action="{{ route('sekolah.students.consent', $s) }}" class="mt-2 space-y-2 rounded-xl border border-[#e7e2d9] bg-gray-50 p-3">
                                    @csrf
                                    <label class="block text-xs">Status
                                        <select name="granted" class="mt-0.5 w-full rounded-lg border border-[#e7e2d9] bg-white px-2 py-1.5 text-xs">
                                            <option value="1">Setuju</option>
                                            <option value="0">Cabut</option>
                                        </select>
                                    </label>
                                    <label class="block text-xs">Referensi bukti
                                        <input name="evidence" required maxlength="2000" class="mt-0.5 w-full rounded-lg border border-[#e7e2d9] bg-white px-2 py-1.5 text-xs">
                                    </label>
                                    <label class="block text-xs">Waktu persetujuan/perubahan
                                        <input type="datetime-local" name="agreed_at" required class="mt-0.5 w-full rounded-lg border border-[#e7e2d9] bg-white px-2 py-1.5 text-xs">
                                    </label>
                                    <button class="rounded-lg bg-pine-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-pine-800">Simpan jejak consent</button>
                                </form>
                            </details>
                        </td>
                        <td class="py-3 pr-4">
                            @if($s->user_id)
                                <span class="inline-flex items-center rounded-full bg-pine-100 px-2.5 py-1 text-xs font-semibold text-pine-800">Aktif</span>
                            @else
                                <form method="POST" action="{{ route('sekolah.students.invite') }}" class="inline">@csrf
                                    <input type="hidden" name="student_id" value="{{ $s->id }}">
                                    <button class="text-xs font-medium text-terra-700 hover:text-terra-800 hover:underline disabled:text-gray-400 disabled:no-underline" @unless($s->email) disabled @endunless>Undang</button>
                                </form>
                            @endif
                        </td>
                        <td class="py-3">
                            @if($s->parent_consent_at)
                            <button @click="refer = { id: @js($s->id), name: @js($s->name) }" class="rounded-lg border border-pine-200 bg-pine-50 px-3 py-1 text-xs font-semibold text-pine-700 hover:bg-pine-100 transition">Refer</button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center">
                            <div class="mx-auto max-w-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                <p class="mt-2 text-sm text-gray-500">Belum ada siswa. Tambah lewat form.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Form tambah siswa --}}
    <div x-show="tab === 'tambah'" x-cloak class="fixed inset-0 bg-pine-950/50 z-50 grid place-items-center p-4" @keydown.escape.window="tab = 'roster'">
        <div class="portal-card w-full max-w-md p-6" @click.outside="tab = 'roster'">
            <h3 class="font-semibold text-pine-900">Tambah siswa ke roster</h3>
            <form method="POST" action="{{ route('sekolah.students.store') }}" class="space-y-3 mt-4">
                @csrf
                <input type="text" name="name" placeholder="Nama lengkap siswa" class="w-full rounded-xl border border-[#e7e2d9] bg-white px-3 py-2 text-sm focus:border-pine-400 focus:ring-1 focus:ring-pine-200" required>
                <input type="email" name="email" placeholder="Email siswa (opsional, untuk undangan akun)" class="w-full rounded-xl border border-[#e7e2d9] bg-white px-3 py-2 text-sm focus:border-pine-400 focus:ring-1 focus:ring-pine-200">
                <input type="text" name="kelas" placeholder="Kelas, mis. X IPA 2" class="w-full rounded-xl border border-[#e7e2d9] bg-white px-3 py-2 text-sm focus:border-pine-400 focus:ring-1 focus:ring-pine-200" required>
                <input type="text" name="parent_name" placeholder="Nama orang tua/wali" class="w-full rounded-xl border border-[#e7e2d9] bg-white px-3 py-2 text-sm focus:border-pine-400 focus:ring-1 focus:ring-pine-200" required>
                <div class="flex gap-2 justify-end pt-1">
                    <button type="button" @click="tab = 'roster'" class="rounded-xl border border-[#e7e2d9] px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</button>
                    <button class="rounded-xl bg-pine-700 px-4 py-2 text-sm font-semibold text-white hover:bg-pine-800 transition">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
