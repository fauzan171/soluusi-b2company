@extends('layouts.app')

@section('title', 'Roster Siswa — ' . $sekolah->brand)

@section('content')
<div x-data="{ tab: 'roster', refer: null }">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-xl font-bold text-pine-900">Roster Siswa</h1>
            <p class="text-sm text-gray-500">Kelola partisipasi program. Data di halaman ini untuk pengelolaan — konten sesi tetap rahasia.</p>
        </div>
        <button @click="tab = 'tambah'" class="bg-pine-700 text-white text-sm px-4 py-2 rounded-xl">+ Tambah Siswa</button>
    </div>

    @if(session('success'))<div class="mb-4 p-3 rounded-xl bg-pine-100 text-pine-800 text-sm">{{ session('success') }}</div>@endif

    {{-- Refer konseling (form tersembunyi, dibuka dari baris) --}}
    <div x-show="refer" x-cloak class="portal-card p-5 mb-4" x-data="{
        form: { student_id: '', konsultan_type: 'psikolog', mode: 'online', scheduled_at: '' },
        init() { this.$watch('refer', v => { if (v) this.form.student_id = v.id }) }
    }">
        <h3 class="dash-section-title">Refer konseling — <span x-text="refer ? refer.name : ''"></span></h3>
        <form method="POST" action="{{ route('sekolah.students.refer') }}" class="grid md:grid-cols-4 gap-3 mt-3">
            @csrf
            <input type="hidden" name="student_id" :value="form.student_id">
            <select name="konsultan_type" class="border border-[#e7e2d9] rounded-xl px-3 py-2 text-sm" x-model="form.konsultan_type">
                <option value="psikolog">Psikolog Anak & Remaja</option>
                <option value="karir">Konselor Karir</option>
            </select>
            <label class="text-sm">Slot konsultan (WIB)
                <select name="schedule_id" required class="w-full border rounded-xl px-3 py-2 text-sm">
                    <option value="">— Pilih slot sesuai jenis konsultan —</option>
                    @foreach($slots as $slot)
                    <option value="{{ $slot->id }}">{{ $slot->started_at->translatedFormat('d M Y H:i') }} · {{ $slot->consultant->user->firstname }} · {{ $slot->mode }}</option>
                    @endforeach
                </select>
                @error('schedule_id')<p class="text-red-600">{{ $message }}</p>@enderror
            </label>
            <button class="bg-pine-700 text-white text-sm px-4 py-2 rounded-xl">Jadwalkan</button>
        </form>
    </div>

    <section class="portal-card p-5">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-500 border-b border-[#e7e2d9]">
                        <th class="py-2">Nama</th><th>Kelas</th><th>Email</th>
                        <th>Consent ortu</th><th>Akun</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $s)
                    <tr class="border-b border-[#e7e2d9]/60">
                        <td class="py-2 font-medium">{{ $s->name }}</td>
                        <td>{{ $s->kelas }}</td>
                        <td class="text-gray-500">{{ $s->email ?? '—' }}</td>
                        <td>
                            @if($s->parent_consent_at)
                            <span class="text-pine-700 text-xs font-semibold">✓ disetujui</span>
                            @else
                            <span class="text-terra-700 text-xs font-semibold">belum</span>
                            @endif
                            <details><summary>Catat/koreksi izin wali eksternal</summary>
                                <form method="POST" action="{{ route('sekolah.students.consent', $s) }}">
                                    @csrf
                                    <label>Status <select name="granted"><option value="1">Setuju</option><option value="0">Cabut</option></select></label>
                                    <label>Referensi bukti <input name="evidence" required maxlength="2000"></label>
                                    <label>Waktu persetujuan/perubahan <input type="datetime-local" name="agreed_at" required></label>
                                    <button>Simpan jejak consent</button>
                                </form>
                            </details>
                        </td>
                        <td>
                            @if($s->user_id)
                            <span class="text-pine-700 text-xs">aktif</span>
                            @else
                            <form method="POST" action="{{ route('sekolah.students.invite') }}">@csrf
                                <input type="hidden" name="student_id" value="{{ $s->id }}">
                                <button class="text-xs text-terra-700 hover:underline" @unless($s->email) disabled @endunless>undang</button>
                            </form>
                            @endif
                        </td>
                        <td>
                            @if($s->parent_consent_at)
                            <button @click="refer = { id: @js($s->id), name: @js($s->name) }" class="text-xs bg-pine-50 text-pine-700 px-3 py-1 rounded-lg border border-pine-200">Refer</button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="py-6 text-center text-gray-500">Belum ada siswa. Tambah lewat form.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Form tambah siswa --}}
    <div x-show="tab === 'tambah'" x-cloak class="fixed inset-0 bg-black/40 z-50 grid place-items-center" @keydown.escape.window="tab = 'roster'">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md" @click.outside="tab = 'roster'">
            <h3 class="dash-section-title">Tambah siswa ke roster</h3>
            <form method="POST" action="{{ route('sekolah.students.store') }}" class="space-y-3 mt-4">
                @csrf
                <input type="text" name="name" placeholder="Nama lengkap siswa" class="w-full border border-[#e7e2d9] rounded-xl px-3 py-2 text-sm" required>
                <input type="email" name="email" placeholder="Email siswa (opsional, untuk undangan akun)" class="w-full border border-[#e7e2d9] rounded-xl px-3 py-2 text-sm">
                <input type="text" name="kelas" placeholder="Kelas, mis. X IPA 2" class="w-full border border-[#e7e2d9] rounded-xl px-3 py-2 text-sm" required>
                <input type="text" name="parent_name" placeholder="Nama orang tua/wali" class="w-full border border-[#e7e2d9] rounded-xl px-3 py-2 text-sm" required>
                <div class="flex gap-2 justify-end">
                    <button type="button" @click="tab = 'roster'" class="text-sm px-4 py-2 rounded-xl border border-[#e7e2d9]">Batal</button>
                    <button class="bg-pine-700 text-white text-sm px-4 py-2 rounded-xl">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
