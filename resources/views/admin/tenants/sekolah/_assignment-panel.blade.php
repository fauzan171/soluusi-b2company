@php
$assignments = $sekolah->morphMany(\App\Models\AssessmentAssignment::class, 'assignable')->orderByDesc('id')->get();
$instrument = null;
@endphp
<div class="bg-white border rounded-lg p-6 mt-8">
    <h2 class="font-semibold text-gray-700 mb-1">Assignment Instrumen Asesmen</h2>
    <p class="text-xs text-gray-400 mb-4">Siswa hanya boleh WHO-5, PSS-10, Big Five, DISC (minor-safety). Staf dewasa bisa instrumen gejala; MCMI-III selalu via grant per-person.</p>

    <form method="POST" action="{{ route('admin.tenants.sekolahs.assignments.store', $sekolah) }}" class="grid sm:grid-cols-4 gap-2 items-start">
        @csrf
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Instrumen</label>
            <select name="instrument_code" required class="w-full border rounded px-2 py-1.5 text-sm">
                <option value="">— pilih —</option>
                @foreach(\App\Models\AssessmentInstrument::where('is_active', true)->orderBy('name')->get() as $i)
                    <option value="{{ $i->code }}">{{ $i->name }}</option>
                @endforeach
            </select>
            @error('instrument_code')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Audiens</label>
            <select name="audience" required class="w-full border rounded px-2 py-1.5 text-sm">
                <option value="student">Siswa</option>
                <option value="staff">Staf</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Buka s/d</label>
            <div class="flex gap-1">
                <input type="date" name="opens_at" class="w-full border rounded px-2 py-1.5 text-sm">
                <input type="date" name="closes_at" class="w-full border rounded px-2 py-1.5 text-sm">
            </div>
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
                <span class="text-xs text-gray-400 ml-2">{{ $a->audience }} · round {{ $a->round }} · {{ $a->opens_at->format('d M Y') }} → {{ $a->closes_at?->format('d M Y') ?? '∞' }}</span>
                <span class="ml-2 text-[10px] font-semibold px-2 py-0.5 rounded {{ $a->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">{{ $a->status }}</span>
                <span class="ml-1 text-[10px] text-gray-400">{{ $a->source }}</span>
            </div>
            <form method="POST" action="{{ route($a->status === 'active' ? 'admin.tenants.sekolahs.assignments.close' : 'admin.tenants.sekolahs.assignments.reopen', [$sekolah, $a]) }}"
                onsubmit="return confirm('{{ $a->status === 'active' ? 'Tutup assignment ini?' : 'Buka kembali assignment ini?' }}')">
                @csrf
                <button class="text-xs {{ $a->status === 'active' ? 'text-red-600' : 'text-emerald-700' }} font-semibold hover:underline">{{ $a->status === 'active' ? 'Tutup' : 'Buka' }}</button>
            </form>
        </div>
        @endforeach
    </div>
    @endif
</div>
