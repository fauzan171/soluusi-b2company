@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto mt-16 bg-white rounded-xl border p-8 text-center">
    <div class="w-12 h-12 mx-auto rounded-full bg-amber-100 flex items-center justify-center mb-4">
        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <h1 class="text-xl font-bold mb-2">Program belum aktif</h1>
    <p class="text-sm text-gray-500 leading-relaxed">
        Wellbeing program <strong>{{ $perusahaan->brand }}</strong> belum diaktifkan.
        Setelah MoU + aktivasi selesai, dashboard HR dan booking karyawan akan aktif otomatis.
    </p>
    @if($isSuperAdmin)
    <div class="mt-6 pt-6 border-t text-left bg-indigo-50 border-indigo-200 rounded-lg p-4">
        <h2 class="text-sm font-semibold text-indigo-900 mb-1">Mode Super Admin</h2>
        <p class="text-xs text-indigo-500 mb-3">Aktifkan program perusahaan ini agar dashboard HR bisa diakses.</p>
        <form method="POST" action="{{ route('admin.perusahaans.activate', $perusahaan) }}">
            @csrf
            <button class="bg-pine-700 text-white text-sm font-semibold rounded px-4 py-2">Aktifkan program</button>
        </form>
    </div>
    @endif
</div>
@endsection
