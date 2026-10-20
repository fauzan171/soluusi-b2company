@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto text-center py-20">
    <h1 class="text-xl font-bold text-pine-900">{{ $sekolah->brand }}</h1>
    <p class="text-sm text-gray-500 mt-2">Program wellbeing sekolah kamu belum diaktifkan. Hubungi tim Soluusi.</p>
    @if($isSuperAdmin)
    <form method="POST" action="{{ route('admin.sekolahs.activate', $sekolah) }}" class="mt-4">@csrf
        <button class="bg-pine-700 text-white text-sm px-4 py-2 rounded-xl">Aktifkan Program</button>
    </form>
    @endif
</div>
@endsection
