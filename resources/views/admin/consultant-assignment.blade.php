@extends('layouts.app')

@section('content')
<h1 class="text-xl font-bold mb-1">Consultant Assignment — <a href="{{ route('admin.dashboard') }}" class="text-sm text-emerald-700 font-normal hover:underline">← Dashboard</a></h1>
<p class="text-sm text-gray-500 mb-6">Assign/remove konsultan ke pool tenant (perusahaan / sekolah). Konsultan yang ter-assign muncul di picker booking tenant.</p>

@if(session('success'))
<div class="mb-4 rounded border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-2 text-sm text-red-800">
    @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
</div>
@endif

<form method="GET" class="mb-6 flex gap-3 items-end">
    <div>
        <label class="block text-xs text-gray-500 mb-1">Vertikal</label>
        <select name="tenant" class="border rounded px-3 py-2 text-sm" onchange="this.form.submit()">
            <option value="perusahaan" @selected($tenant === 'perusahaan')>Perusahaan</option>
            <option value="sekolah" @selected($tenant === 'sekolah')>Sekolah</option>
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Tenant</label>
        <select name="tenant_id" class="border rounded px-3 py-2 text-sm" onchange="this.form.submit()">
            <option value="">— pilih —</option>
            @foreach($tenants as $t)
            <option value="{{ $t->id }}" @selected($tenantId === $t->id)>{{ $t->brand ?? $t->name }}</option>
            @endforeach
        </select>
    </div>
</form>

@if($tenantId)
<table class="w-full bg-white border rounded-lg text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Konsultan</th>
        <th class="p-3">Status</th>
        <th class="p-3 text-right">Aksi</th>
    </tr></thead>
    <tbody>
        @forelse($consultants as $c)
        @php $assigned = in_array($c->id, $assignedIds, true); @endphp
        <tr class="border-b">
            <td class="p-3">{{ $c->user?->fullname ?? 'Konsultan #'.$c->id }}</td>
            <td class="p-3">
                @if($assigned)
                <span class="bg-emerald-100 text-emerald-800 text-xs font-semibold rounded px-2 py-1">Assigned</span>
                @else
                <span class="text-gray-400 text-xs">—</span>
                @endif
            </td>
            <td class="p-3 text-right">
                <form method="POST" action="{{ $assigned ? route('admin.consultant-assignment.remove') : route('admin.consultant-assignment.assign') }}" class="inline">
                    @csrf
                    <input type="hidden" name="tenant" value="{{ $tenant }}">
                    <input type="hidden" name="tenant_id" value="{{ $tenantId }}">
                    <input type="hidden" name="consultant_id" value="{{ $c->id }}">
                    <button class="text-xs font-semibold rounded px-3 py-1 {{ $assigned ? 'bg-red-600 text-white' : 'bg-emerald-700 text-white' }}">
                        {{ $assigned ? 'Remove' : 'Assign' }}
                    </button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="3" class="p-3 text-gray-400">Belum ada konsultan dengan status accept.</td></tr>
        @endforelse
    </tbody>
</table>
@else
<p class="text-sm text-gray-400">Pilih tenant untuk melihat & mengatur assignment.</p>
@endif
@endsection
