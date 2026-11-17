@extends('layouts.app')

@section('title', 'Audit Log — Admin')

@section('content')
<div class="flex items-baseline justify-between mb-2">
    <h1 class="text-xl font-bold">Audit Log</h1>
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-emerald-700 font-semibold hover:underline">← Dashboard</a>
</div>
<p class="text-sm text-gray-500 mb-6">Jejak aksi superadmin. Nilai payload di-mask — data pribadi tidak ditampilkan mentah.</p>

<form method="GET" class="mb-6 flex flex-wrap gap-3 items-end max-w-4xl">
    <div>
        <label for="admin_id" class="block text-xs text-gray-500 mb-1">Admin</label>
        <select id="admin_id" name="admin_id" class="border rounded px-3 py-2 text-sm">
            <option value="">Semua</option>
            @foreach($admins as $a)
                <option value="{{ $a->id }}" {{ ($filters['admin_id'] ?? '') == $a->id ? 'selected' : '' }}>
                    {{ trim($a->firstname . ' ' . $a->lastname) }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="action" class="block text-xs text-gray-500 mb-1">Aksi</label>
        <input id="action" name="action" value="{{ $filters['action'] ?? '' }}" class="border rounded px-3 py-2 text-sm" placeholder="cth: klinik.update">
    </div>
    <div>
        <label for="target_type" class="block text-xs text-gray-500 mb-1">Target</label>
        <input id="target_type" name="target_type" value="{{ $filters['target_type'] ?? '' }}" class="border rounded px-3 py-2 text-sm" placeholder="cth: klinik">
    </div>
    <div>
        <label for="date_from" class="block text-xs text-gray-500 mb-1">Dari</label>
        <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="border rounded px-3 py-2 text-sm">
    </div>
    <div>
        <label for="date_to" class="block text-xs text-gray-500 mb-1">Sampai</label>
        <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="border rounded px-3 py-2 text-sm">
    </div>
    <button type="submit" class="bg-emerald-600 text-white text-sm font-semibold rounded px-4 py-2 hover:bg-emerald-700">Filter</button>
</form>

<div class="overflow-x-auto bg-white border rounded-lg"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Waktu</th>
        <th class="p-3">Admin</th>
        <th class="p-3">Aksi</th>
        <th class="p-3">Target</th>
        <th class="p-3">Payload</th>
    </tr></thead>
    <tbody>
        @forelse($rows as $row)
        <tr class="border-b align-top">
            <td class="p-3 whitespace-nowrap text-gray-500">{{ $row['created_at'] }}</td>
            <td class="p-3">{{ $row['admin'] }}</td>
            <td class="p-3 font-semibold">{{ $row['action'] }}</td>
            <td class="p-3 whitespace-nowrap">{{ $row['target_type'] }} #{{ $row['target_id'] }}</td>
            <td class="p-3 text-xs text-gray-500">
                @forelse($row['payload'] as $key => $value)
                    <div><span class="font-semibold">{{ $key }}</span>: {{ is_scalar($value) ? (string) $value : json_encode($value) }}</div>
                @empty
                    <span class="text-gray-300">—</span>
                @endforelse
            </td>
        </tr>
        @empty
        <tr><td class="p-3 text-gray-400" colspan="5">Tidak ada aksi yang cocok dengan filter.</td></tr>
        @endforelse
    </tbody>
</table></div>
{{ $actions->links() }}
@endsection
