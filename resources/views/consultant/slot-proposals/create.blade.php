@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-xl font-bold text-pine-900">Usulkan Opsi Jadwal</h1>
    <p class="text-sm text-gray-500 mt-1">Kirim 2–3 opsi waktu. Karyawan/HR akan memilih salah satu.</p>
</div>

<section class="portal-card max-w-xl p-5">
    <form method="POST" action="{{ route('consultant.sessions.propose-slots', $session) }}" class="space-y-4">
        @csrf
        @foreach (range(0, 2) as $i)
        <fieldset class="border border-[#e7e2d9] rounded-lg p-3">
            <legend class="text-xs font-semibold text-gray-500 px-1">Opsi {{ $i + 1 }}{{ $i > 1 ? ' (opsional)' : '' }}</legend>
            <div class="grid grid-cols-2 gap-3">
                <label class="block text-sm">
                    <span class="text-gray-600">Mulai</span>
                    <input type="datetime-local" name="slots[{{ $i }}][start]"
                           class="mt-1 w-full rounded border-gray-300 text-sm" {{ $i < 2 ? 'required' : '' }}>
                </label>
                <label class="block text-sm">
                    <span class="text-gray-600">Selesai</span>
                    <input type="datetime-local" name="slots[{{ $i }}][end]"
                           class="mt-1 w-full rounded border-gray-300 text-sm" {{ $i < 2 ? 'required' : '' }}>
                </label>
            </div>
        </fieldset>
        @endforeach

        @if($errors->any())
        <div class="text-sm text-red-600">{{ $errors->first() }}</div>
        @endif

        <button type="submit" class="px-4 py-2 bg-pine-700 text-white rounded-lg text-sm font-semibold">
            Kirim Opsi Jadwal
        </button>
    </form>
</section>
@endsection
