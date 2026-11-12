{{-- Halaman status pengembangan — untuk presentasi. Angka diambil live dari DB. --}}
@php
    $bandColor = [
        'low' => 'bd bd-low',
        'medium' => 'bd bd-medium',
        'high' => 'bd bd-high',
        'critical' => 'bd bd-critical',
    ];
    $barColor = ['low' => 'bg-low', 'medium' => 'bg-medium', 'high' => 'bg-high', 'critical' => 'bg-critical'];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pengembangan — Soluusi B2B</title>
    @vite(['resources/css/app.css'])
    {{-- Build Tailwind lama belum memuat kelas warna baru; vite tidak terpasang di VPS.
         Cukup inline untuk kelas yang dipakai halaman ini. --}}
    <style>
        .pbar{height:.5rem;border-radius:9999px;background:#f1f5f9;overflow:hidden}
        .pbar>i{display:block;height:100%}
        .bg-low{background:#10b981}.bg-medium{background:#f59e0b}
        .bg-high{background:#f97316}.bg-critical{background:#ef4444}
        .bd{border-width:1px;border-style:solid;border-radius:9999px;padding:.25rem .5rem;font-size:.75rem}
        .bd-low{background:#d1fae5;color:#065f46;border-color:#6ee7b7}
        .bd-medium{background:#fef3c7;color:#92400e;border-color:#fcd34d}
        .bd-high{background:#ffedd5;color:#9a3412;border-color:#fdba74}
        .bd-critical{background:#fee2e2;color:#991b1b;border-color:#fca5a5}
    </style>
</head>
<body class="bg-slate-50 text-slate-900">
<div class="max-w-5xl mx-auto px-6 py-10">

    <header class="mb-10">
        <p class="text-xs uppercase tracking-widest text-slate-500">Soluusi B2B · Progress Report</p>
        <h1 class="text-3xl font-bold mt-1">Screening Multi-Domain</h1>
        <p class="text-slate-600 mt-2 max-w-2xl">
            Adopsi flow asesmen 3 domain dari kiosk TVS ke segmen perusahaan.
            Semua angka di halaman ini diambil langsung dari database — bukan mockup.
        </p>
    </header>

    {{-- Ringkasan domain --}}
    <section class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-10">
        @foreach(['mental_health' => 'Mental Health', 'nutrition_lifestyle' => 'Nutrition & Lifestyle', 'ergonomic' => 'Ergonomic'] as $key => $label)
        @php $r = $results[$key]; @endphp
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h2 class="font-semibold">{{ $label }}</h2>
            <p class="text-xs text-slate-500 mb-3">{{ $bySection[$key] ?? 0 }} pertanyaan</p>
            <div class="flex items-end gap-3">
                <span class="text-3xl font-bold">{{ $r['score'] }}</span>
                <span class="text-xs text-slate-500 mb-1">/ 100</span>
            </div>
            <div class="pbar mt-3">
                <i class="{{ $barColor[$r['band']] }}" style="width: {{ $r['score'] }}%"></i>
            </div>
            <span class="inline-block mt-3 {{ $bandColor[$r['band']] }}">
                {{ $r['band_label'] }} · {{ $r['consult'] }} sesi konsultasi
            </span>
            <p class="text-xs text-slate-500 mt-2">raw {{ $r['raw'] }} / maks {{ $r['max'] }}</p>
        </div>
        @endforeach
    </section>

    {{-- Rekomendasi agregat --}}
    <section class="bg-white rounded-xl border border-slate-200 p-6 mb-10">
        <h2 class="text-lg font-semibold mb-1">Rekomendasi: {{ $recommend['consult'] }} sesi konsultasi</h2>
        <p class="text-sm text-slate-600">
            Diambil dari domain terburuk (<span class="font-medium">{{ str_replace('_', ' ', $recommend['section'] ?? '-') }}</span>),
            bukan rata-rata. Satu temuan terburuk yang menentukan kebutuhan tindak lanjut.
        </p>
    </section>

    {{-- Angka nyata dari DB --}}
    <section class="mb-10">
        <h2 class="text-lg font-semibold mb-3">Data nyata di database</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @foreach([
                'Soal per vertical' => $stats['questions_per_vertical'],
                'Total soal (2 vertical)' => $stats['questions_total'],
                'Perusahaan' => $stats['perusahaan'],
                'Karyawan' => $stats['employees'],
                'Sudah consent' => $stats['employees_consent'],
                'Sesi perusahaan' => $stats['sessions'],
                'Siswa' => $stats['students'],
                'Akun user' => $stats['users'],
            ] as $label => $value)
            <div class="bg-white rounded-lg border border-slate-200 p-4">
                <p class="text-2xl font-bold">{{ number_format($value, 0, ',', '.') }}</p>
                <p class="text-xs text-slate-500 mt-1">{{ $label }}</p>
            </div>
            @endforeach
        </div>
    </section>

    {{-- Bank soal --}}
    <section class="mb-10">
        <h2 class="text-lg font-semibold mb-3">Bank pertanyaan (data, bukan kode)</h2>
        <div class="bg-white rounded-xl border border-slate-200 divide-y divide-slate-100">
            @foreach($questions->where('vertical', 'perusahaan')->groupBy('section') as $section => $rows)
            <div class="p-5">
                <h3 class="font-medium text-sm uppercase tracking-wide text-slate-500 mb-2">
                    {{ str_replace('_', ' ', $section) }} · {{ $rows->count() }} soal
                </h3>
                <ul class="space-y-1">
                    @foreach($rows as $q)
                    <li class="text-sm flex gap-2">
                        <span class="text-slate-400 w-9 shrink-0">{{ $q->code }}</span>
                        <span>{{ $q->text }}</span>
                        @if($q->reverse)
                        <span class="text-xs text-slate-400 shrink-0">(reverse)</span>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>
    </section>

    <footer class="text-xs text-slate-500 border-t pt-4">
        Soluusi B2B · screening multi-domain · {{ now()->translatedFormat('d M Y H.i') }}
    </footer>
</div>
</body>
</html>