@php
$assignments = $sekolah->morphMany(\App\Models\AssessmentAssignment::class, 'assignable')->orderByDesc('id')->get();
$instrument = null;
@endphp
<div class="bg-white border rounded-lg p-6 mt-8">
    <h2 class="font-semibold text-gray-700 mb-1">Assignment Instrumen Asesmen</h2>
    <p class="text-xs text-gray-400 mb-4">Siswa hanya boleh WHO-5, PSS-10, Big Five, DISC (minor-safety). Staf dewasa bisa instrumen gejala; MCMI-III selalu via grant per-person.</p>

    <form method="POST" action="{{ route('admin.tenants.sekolahs.assignments.store', $sekolah) }}" class="grid sm:grid-cols-4 gap-2 items-start" onsubmit="this.querySelector('button').disabled=true">
        @csrf
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Instrumen</label>
            <select name="instrument_code" required class="w-full border rounded px-2 py-1.5 text-sm">
                <option value="">— pilih —</option>
                @foreach(\App\Models\AssessmentInstrument::where('is_active', true)->orderBy('name')->get() as $i)
                    <option value="{{ $i->code }}" @selected(old('instrument_code') === $i->code)>{{ $i->name }}</option>
                @endforeach
            </select>
            @error('instrument_code')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Audiens</label>
            <select name="audience" required class="w-full border rounded px-2 py-1.5 text-sm">
                <option value="student" @selected(old('audience') === 'student')>Siswa</option>
                <option value="staff" @selected(old('audience') === 'staff')>Staf</option>
            </select>
            @error('audience')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Periode (buka s/d tutup)</label>
            <div class="flex gap-1">
                <input type="date" name="opens_at" value="{{ old('opens_at') }}" aria-label="Tanggal buka" class="w-full border rounded px-2 py-1.5 text-sm">
                <input type="date" name="closes_at" value="{{ old('closes_at') }}" aria-label="Tanggal tutup" class="w-full border rounded px-2 py-1.5 text-sm">
            </div>
            @error('opens_at')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            @error('closes_at')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="pt-6">
            <button class="bg-emerald-600 text-white text-sm font-semibold rounded px-4 py-1.5 hover:bg-emerald-700">Assign</button>
        </div>
    </form>

    @if($assignments->isNotEmpty())
    <div class="mt-4 divide-y text-sm">
        @foreach($assignments as $a)
        <div class="flex items-center justify-between py-2">
            <div>
                <span class="font-semibold">{{ $a->instrument_code }}</span>
                <span class="text-xs text-gray-400 ml-2">{{ $a->audience === 'student' ? 'Siswa' : 'Staf' }} · putaran {{ $a->round }} · {{ $a->opens_at->translatedFormat('d M Y') }} → {{ $a->closes_at?->translatedFormat('d M Y') ?? '∞' }}</span>
                <span class="ml-2 text-[10px] font-semibold px-2 py-0.5 rounded {{ $a->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">{{ $a->status === 'active' ? 'Aktif' : 'Ditutup' }}</span>
                <span class="ml-1 text-[10px] text-gray-400">{{ $a->source }}</span>
            </div>
            <form method="POST" action="{{ route($a->status === 'active' ? 'admin.tenants.sekolahs.assignments.close' : 'admin.tenants.sekolahs.assignments.reopen', [$sekolah, $a]) }}"
                onsubmit="return confirm('{{ $a->status === 'active' ? 'Tutup assignment ini? Siswa/staf tidak bisa mengisi lagi.' : 'Buka kembali assignment ini?' }}')">
                @csrf
                <button class="text-xs {{ $a->status === 'active' ? 'text-red-600' : 'text-emerald-700' }} font-semibold hover:underline">{{ $a->status === 'active' ? 'Tutup' : 'Buka' }}</button>
            </form>
            {{-- Audit F8: edit inline putaran & periode tanpa bikin assignment baru. --}}
            <details class="ml-2">
                <summary class="text-xs text-emerald-700 font-semibold hover:underline cursor-pointer">Edit</summary>
                <form method="POST" action="{{ route('admin.tenants.sekolahs.assignments.update', [$sekolah, $a]) }}"
                      class="mt-2 flex flex-wrap items-end gap-2 bg-gray-50 border rounded p-2">
                    @csrf
                    @method('PUT')
                    <label class="text-xs text-gray-600">Putaran
                        <input type="number" name="round" required min="1" max="99" value="{{ old('round', $a->round) }}" class="w-16 border rounded px-2 py-1">
                    </label>
                    <label class="text-xs text-gray-600">Buka
                        <input type="date" name="opens_at" required value="{{ old('opens_at', $a->opens_at->toDateString()) }}" class="border rounded px-2 py-1">
                    </label>
                    <label class="text-xs text-gray-600">Tutup
                        <input type="date" name="closes_at" value="{{ old('closes_at', $a->closes_at?->toDateString()) }}" class="border rounded px-2 py-1">
                    </label>
                    <button class="bg-emerald-600 text-white text-xs font-semibold rounded px-3 py-1 hover:bg-emerald-700">Simpan</button>
                </form>
            </details>
        </div>
        @endforeach
    </div>
    @else
    <p class="mt-4 text-sm text-gray-400">Belum ada assignment untuk sekolah ini.</p>
    @endif
</div>
