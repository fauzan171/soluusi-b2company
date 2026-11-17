{{-- C4 — Form assign associate ke tenant. Butuh: $associates (collection User role associate), $assignRoute, $tenant. --}}
@if(!empty($associates))
<div class="bg-white border rounded-lg p-6 mt-8">
    <h2 class="font-semibold text-gray-700 mb-2">Assign associate</h2>
    <p class="text-xs text-gray-500 mb-3">Associate saat ini: <span class="font-semibold">{{ $tenant->associate?->fullname ?? '—' }}</span>. Kosongkan untuk melepas. Tercatat di audit log.</p>
    <form method="POST" action="{{ $assignRoute }}" class="flex flex-wrap items-end gap-2">
        @csrf
        <div>
            <label class="block text-xs text-gray-500 mb-1" for="associate_id">Associate</label>
            <select id="associate_id" name="associate_id" class="text-sm border border-[#e7e2d9] rounded-lg px-2 py-1 bg-white min-w-[16rem]">
                <option value="">— tidak ada (unassign) —</option>
                @foreach($associates as $a)
                    <option value="{{ $a->id }}" @selected((int) $tenant->associate_id === (int) $a->id)>{{ $a->fullname ?: $a->email }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="bg-emerald-600 text-white font-semibold rounded px-4 py-1.5 text-sm hover:bg-emerald-700">Simpan associate</button>
    </form>
</div>
@endif
