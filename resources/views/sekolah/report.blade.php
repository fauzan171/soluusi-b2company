@extends('layouts.app')
@section('title', 'Laporan wellbeing — '.$sekolah->brand)
@section('content')
<h1 class="text-xl font-bold">Laporan wellbeing {{ $month }}</h1>
<form method="GET"><label>Bulan <input type="month" name="month" value="{{ $month }}" required></label><button>Tampilkan</button></form>
<p>Data agregat berdasarkan orang unik. Breakdown dan data pelengkap disembunyikan jika contributor nonzero kurang dari lima; bukan jaminan anonimitas absolut.</p>
<dl><dt>Siswa terdaftar</dt><dd>{{ $registered }}</dd><dt>Sesi selesai</dt><dd>{{ $done ?? '—' }}</dd><dt>Peserta unik</dt><dd>{{ $participants ?? '—' }}</dd><dt>Partisipasi</dt><dd>{{ $participation === null ? '—' : $participation.'%' }}</dd><dt>No-show</dt><dd>{{ $noShow ?? '—' }}</dd><dt>Rating rata-rata</dt><dd>{{ $avgRating ?? '—' }}</dd></dl>
@if($suppressed)<p>Data sesi tidak tersedia untuk perlindungan privasi.</p>@endif
<h2>Kelas</h2><ul>@foreach($groups as $group)<li>{{ $group['name'] }}: {{ $group['participants'] }} peserta / {{ $group['registered'] }} siswa</li>@endforeach</ul>
<h2>Topik</h2><ul>@foreach($topics as $topic => $count)<li>{{ $topic }}: {{ $count }}</li>@endforeach</ul>
<h2>Jenis layanan</h2><ul>@foreach($byType as $type => $count)<li>{{ $type }}: {{ $count }}</li>@endforeach</ul>
<h2>WHO-5</h2>@if($riskDist === null)<p>— Data asesmen tidak tersedia.</p>@else<ul>@foreach($riskDist as $band => $count)<li>{{ $band }}: {{ $count }}</li>@endforeach</ul>@endif
<button type="button" onclick="window.print()">Cetak / PDF</button>
@endsection
