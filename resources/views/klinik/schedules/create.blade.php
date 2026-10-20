@extends('layouts.app')

@section('content')
<div class="mb-5">
    <a href="{{ route('klinik.consultants.show', $consultant) }}" class="text-sm text-emerald-700 hover:underline">← Kembali ke profil konsultan</a>
    <h1 class="text-xl font-bold mt-2">Atur Jadwal Konsultan</h1>
    <p class="text-sm text-gray-500">Konsultan: {{ trim(($consultant->user->firstname ?? '') . ' ' . ($consultant->user->lastname ?? '')) }} · Klik tanggal di kalender untuk memilih hari slot.</p>
</div>

<div class="grid lg:grid-cols-5 gap-6">
    {{-- Kalender (mirror B2C: FullCalendar, dateClick = pilih tanggal) --}}
    <div class="lg:col-span-3 bg-white border rounded-lg p-4">
        <div id="calendar"></div>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('klinik.schedules.store') }}" class="lg:col-span-2 bg-white border rounded-lg p-6 space-y-4 self-start">
        @csrf
        <input type="hidden" name="consultant_id" value="{{ $consultant->id }}">

        <div>
            <label class="block text-sm mb-1">Tanggal</label>
            <input type="date" name="date" value="{{ old('date') }}" required class="w-full border rounded px-3 py-2" min="{{ now()->toDateString() }}">
            <p class="text-[11px] text-gray-400 mt-1">Pilih langsung dari kalender atau ketik manual.</p>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm mb-1">Jam mulai</label>
                <input type="time" name="time_start" value="{{ old('time_start', '09:00') }}" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm mb-1">Jam selesai</label>
                <input type="time" name="time_end" value="{{ old('time_end', '10:00') }}" required class="w-full border rounded px-3 py-2">
            </div>
        </div>

        <div>
            <label class="block text-sm mb-1">Mode layanan</label>
            <select name="mode" required class="w-full border rounded px-3 py-2">
                <option value="online" @selected(old('mode', 'online') === 'online')>Online</option>
                <option value="offline" @selected(old('mode') === 'offline')>Offline</option>
            </select>
        </div>

        <div id="field-location" class="hidden">
            <label class="block text-sm mb-1">Lokasi (untuk offline)</label>
            <input type="text" name="location" value="{{ old('location') }}" placeholder="Alamat / nama klinik / room" class="w-full border rounded px-3 py-2">
        </div>

        <div>
            <label class="block text-sm mb-1">Catatan internal</label>
            <textarea name="notes" rows="2" class="w-full border rounded px-3 py-2" placeholder="Instruksi tambahan untuk penjadwalan">{{ old('notes') }}</textarea>
        </div>

        @error('started_at')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
        @error('ended_at')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

        <button class="bg-emerald-700 text-white rounded px-4 py-2 font-semibold w-full">Simpan Jadwal</button>
    </form>
</div>

{{-- Controller tetap terima started_at/ended_at; compose dari date + time di sini --}}
<script>
// Date/time composition belongs to server; logout form remains untouched.

// Toggle lokasi sesuai mode. Link meeting dibuat setelah booking/payment.
const modeSelect = document.querySelector('[name=mode]');
function toggleModeFields() {
    document.getElementById('field-location').classList.toggle('hidden', modeSelect.value !== 'offline');
}
modeSelect.addEventListener('change', toggleModeFields);
toggleModeFields();
</script>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        height: 'auto',
        locale: 'id',
        buttonText: { today: 'Hari ini' },
        headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
        events: [
            @foreach ($existingSchedules as $s)
                {
                    title: '{{ $s->started_at->format('H:i') }} {{ strtoupper($s->mode) }}',
                    start: '{{ $s->started_at->toDateTimeString() }}',
                    end: '{{ $s->ended_at->toDateTimeString() }}',
                    backgroundColor: '{{ ($s->mode ?? 'online') === 'offline' ? '#f59e0b' : '#0d7a5f' }}',
                    display: 'block',
                },
            @endforeach
        ],
        dateClick: function (info) {
            // Tidak boleh pilih tanggal lampau
            if (info.dateStr < new Date().toISOString().slice(0, 10)) return;
            document.querySelector('input[name=date]').value = info.dateStr;
        },
        dayCellDidMount: function (arg) {
            arg.el.style.cursor = 'pointer';
        },
    });
    calendar.render();
});
</script>
@endsection
