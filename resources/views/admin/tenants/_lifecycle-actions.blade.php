@php($routeBase = 'admin.tenants.' . $vertical)
@if(($tenant->status ?? 'active') === 'suspended')
    <form action="{{ route($routeBase . '.lifecycle-activate', $tenant) }}" method="POST" class="inline-block ml-2"
          onsubmit="return confirm('Aktifkan kembali tenant ini?')">
        @csrf
        <button class="bg-emerald-600 text-white font-semibold rounded px-4 py-2 text-sm hover:bg-emerald-700">Aktifkan kembali</button>
    </form>
@else
    <button type="button" onclick="document.getElementById('suspend-{{ $vertical }}').classList.toggle('hidden')"
            class="inline-block ml-2 bg-red-600 text-white font-semibold rounded px-4 py-2 text-sm hover:bg-red-700">Suspend</button>
    <form id="suspend-{{ $vertical }}" action="{{ route($routeBase . '.suspend', $tenant) }}" method="POST" class="hidden mt-2"
          onsubmit="return confirm('Suspend tenant ini? Admin tenant tidak bisa login.')">
        @csrf
        <input type="text" name="reason" placeholder="Alasan suspend (wajib)" required maxlength="500"
               class="border rounded px-2 py-1 text-sm w-64">
        <button class="bg-red-700 text-white font-semibold rounded px-3 py-1.5 text-sm hover:bg-red-800">Konfirmasi suspend</button>
    </form>
@endif
