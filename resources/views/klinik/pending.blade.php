@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto mt-12 bg-white rounded-xl border p-8 text-center">
    <div class="w-12 h-12 mx-auto rounded-full bg-amber-100 flex items-center justify-center mb-4">
        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <h1 class="text-xl font-bold mb-2">Akun menunggu verifikasi</h1>
    <p class="text-sm text-gray-500 leading-relaxed">
        Terima kasih, <strong>{{ $klinik->name }}</strong> sudah terdaftar. Tim kami sedang memverifikasi data kamu (maks 1×24 jam kerja).
        Setelah disetujui, dashboard mitra, pemilihan konsultan, dan pembuatan checkout link akan aktif otomatis.
    </p>
    <p class="text-xs text-gray-400 mt-6">Butuh bantuan? Hubungi tim partnership kami.</p>

    {{-- Super admin melihat kontrol approve langsung. --}}
    @if($isSuperAdmin ?? false)
    <div class="mt-6 pt-6 border-t text-left bg-indigo-50 border-indigo-200 rounded-lg p-4">
        <h2 class="text-sm font-semibold text-indigo-900 mb-1">Mode Super Admin</h2>
        <p class="text-xs text-indigo-500 mb-3">Mitra belum di-approve. Setelah approve, dashboard mitra, pemilihan konsultan, dan checkout link aktif otomatis.</p>
        <div class="flex gap-2">
            <form method="POST" action="{{ route('admin.kliniks.approve', $klinik) }}">
                @csrf
                <button class="bg-emerald-700 text-white text-sm font-semibold rounded px-4 py-2">Approve mitra</button>
            </form>
            <a href="{{ route('admin.kliniks.packages.index', $klinik) }}" class="inline-block border border-indigo-300 text-indigo-700 text-sm font-semibold rounded px-4 py-2">Kelola paket</a>
        </div>
    </div>
    @endif
</div>
@endsection
