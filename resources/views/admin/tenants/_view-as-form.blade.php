{{-- B6 — Form start view-as dari detail tenant. Superadmin only (route role:admin). --}}
@if(!empty($viewAsUsers) && count($viewAsUsers) > 0)
<div class="bg-white border rounded-lg p-6 mt-8">
    <h2 class="font-semibold text-gray-700 mb-2">Masuk sebagai (view-as read-only)</h2>
    <p class="text-xs text-gray-500 mb-3">Mode view-as 30 menit, read-only, tercatat di audit log.</p>
    <form method="POST" action="{{ route('admin.view-as.start') }}" class="flex flex-wrap items-end gap-2">
        @csrf
        <div>
            <label class="block text-xs text-gray-500 mb-1" for="view_as_user_id">User</label>
            <select id="view_as_user_id" name="user_id" required class="text-sm border border-[#e7e2d9] rounded-lg px-2 py-1 bg-white min-w-[16rem]">
                <option value="">— pilih user —</option>
                @foreach($viewAsUsers as $u)
                    <option value="{{ $u['user_id'] }}">{{ $u['label'] }} ({{ $u['role'] }})</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-[12rem]">
            <label class="block text-xs text-gray-500 mb-1" for="view_as_reason">Alasan (opsional)</label>
            <input id="view_as_reason" type="text" name="reason" maxlength="255" class="w-full text-sm border border-[#e7e2d9] rounded-lg px-2 py-1" placeholder="mis. tiket support #123">
        </div>
        <button type="submit" class="bg-amber-600 text-white font-semibold rounded px-4 py-1.5 text-sm hover:bg-amber-700">Masuk sebagai</button>
    </form>
</div>
@endif
