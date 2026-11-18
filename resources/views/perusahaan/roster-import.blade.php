@extends('layouts.app')

@section('content')
<div class="mb-4 flex items-center justify-between flex-wrap gap-2">
    <div>
        <h1 class="text-xl font-bold text-pine-900">Import Karyawan dari CSV</h1>
        <p class="text-sm text-gray-500">
            Unggah satu file CSV dan akun login karyawan dibuat otomatis — tanpa email.
            Karyawan wajib mengganti password dan mengonfirmasi profil saat login pertama.
        </p>
    </div>
    <a href="{{ route('perusahaan.employees') }}" class="text-sm text-pine-700 hover:underline">← Kembali ke roster</a>
</div>

<details class="portal-card p-4 mb-4" @if(!$preview) open @endif>
    <summary class="cursor-pointer font-semibold text-pine-900">1. Unggah file CSV</summary>
    <form method="POST" action="{{ route('perusahaan.employees.import.preview') }}" enctype="multipart/form-data" class="space-y-3 mt-3">
        @csrf
        <input type="file" name="file" accept=".csv,text/csv" required
            class="block w-full text-sm text-gray-600 file:mr-3 file:rounded file:border-0 file:bg-pine-700 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white">
        @error('file')<p class="text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
        <p class="text-xs text-gray-500">
            Header wajib: <code class="bg-gray-100 px-1 rounded">name,email,password,divisi</code>.
            Kolom <code class="bg-gray-100 px-1 rounded">password</code> boleh kosong (sistem membuat acak).
            Maksimal 5 MB / 2000 baris.
        </p>
        <button class="btn-pine text-sm">Pratinjau</button>
    </form>
</details>

@if($preview)
    <div class="portal-card p-4 mb-4">
        <h2 class="font-semibold text-pine-900 mb-3">2. Periksa hasil pratinjau</h2>

        <div class="grid grid-cols-3 gap-3 mb-4">
            <div class="rounded-lg bg-green-50 p-3">
                <p class="text-xs text-green-700">Siap dibuat</p>
                <p class="text-2xl font-bold text-green-800">{{ $preview['summary']['valid'] }}</p>
            </div>
            <div class="rounded-lg bg-yellow-50 p-3">
                <p class="text-xs text-yellow-700">Duplikat (dilewati)</p>
                <p class="text-2xl font-bold text-yellow-800">{{ $preview['summary']['duplicate'] }}</p>
            </div>
            <div class="rounded-lg bg-red-50 p-3">
                <p class="text-xs text-red-700">Error (dilewati)</p>
                <p class="text-2xl font-bold text-red-800">{{ $preview['summary']['error'] }}</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-500 border-b">
                        <th class="p-2">Baris</th>
                        <th class="p-2">Nama</th>
                        <th class="p-2">Email</th>
                        <th class="p-2">Divisi</th>
                        <th class="p-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($preview['rows'] as $row)
                        <tr class="border-b last:border-0">
                            <td class="p-2 text-gray-400">{{ $row['line'] }}</td>
                            <td class="p-2">{{ $row['name'] }}</td>
                            <td class="p-2">{{ $row['email'] }}</td>
                            <td class="p-2">{{ $row['division'] ?: '—' }}</td>
                            <td class="p-2">
                                @switch($row['status'])
                                    @case('valid')
                                        <span class="text-xs px-2 py-0.5 rounded bg-green-100 text-green-800">Siap dibuat</span>
                                        @break
                                    @case('duplicate')
                                        <span class="text-xs px-2 py-0.5 rounded bg-yellow-100 text-yellow-800">{{ $row['error'] }}</span>
                                        @break
                                    @default
                                        <span class="text-xs px-2 py-0.5 rounded bg-red-100 text-red-800">{{ $row['error'] }}</span>
                                @endswitch
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($preview['summary']['valid'] > 0)
            <form method="POST" action="{{ route('perusahaan.employees.import.commit') }}" class="mt-4"
                onsubmit="return confirm('Buat {{ $preview['summary']['valid'] }} akun sekarang? Tindakan ini tidak bisa dibatalkan.')">
                @csrf
                <input type="hidden" name="file" value="{{ $preview['file'] }}">
                <button class="btn-pine text-sm">Buat {{ $preview['summary']['valid'] }} akun</button>
            </form>
        @else
            <p class="mt-4 text-sm text-gray-500">Tidak ada baris yang bisa dibuat. Perbaiki file lalu unggah ulang.</p>
        @endif
    </div>
@endif
@endsection