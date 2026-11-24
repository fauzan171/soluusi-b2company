@extends('layouts.app')

@section('content')
<div class="flex items-baseline justify-between mb-6">
    <h1 class="text-xl font-bold">Admin Dashboard</h1>
    <div class="flex gap-4 text-sm items-center">
        @php
            // A7: badge jumlah antrean ops terpadu (sesi review + klinik pending + pervorma due + aktivasi pending).
            $opsQueueCount = \App\Models\ConsultationSession::where('needs_ops_review', true)->whereNull('completion_verified_at')->count()
                + \App\Models\Klinik\Klinik::where('is_active', false)->count()
                + \App\Models\Klinik\HapPervorma::where('status', 'due')->count()
                + \App\Models\Perusahaan\Perusahaan::where('is_active', false)->count()
                + \App\Models\Sekolah\Sekolah::where('is_active', false)->count();
        @endphp
        <a href="{{ route('admin.ops-queue.index') }}" class="text-emerald-700 font-semibold hover:underline">
            Ops antrean: {{ $opsQueueCount }}{!! $opsQueueCount === 0 ? ' <span class="ops-queue-badge-zero"></span>' : '' !!}
        </a>
        <a href="{{ route('admin.referral-overview') }}" class="text-emerald-700 font-semibold hover:underline">Referral HPA → klinik →</a>
        <a href="{{ route('admin.consultant-per-klinik') }}" class="text-emerald-700 font-semibold hover:underline">Konsultan per klinik →</a>
        <a href="{{ route('admin.pervorma-sessions') }}" class="text-emerald-700 font-semibold hover:underline">Sesi kontributor pervorma →</a>
    </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <a href="{{ route('admin.referral-overview') }}" class="bg-white border rounded-lg p-4 hover:border-emerald-600">
        <div class="text-2xl font-bold">{{ $activeKliniks }}</div>
        <div class="text-xs text-gray-500">Mitra aktif → referral HPA</div>
    </a>
    <a href="#pending" class="bg-white border rounded-lg p-4 hover:border-emerald-600">
        <div class="text-2xl font-bold">{{ $pendingKliniks->count() }}</div>
        <div class="text-xs text-gray-500">Mitra pending approval</div>
    </a>
    <a href="{{ route('admin.consultant-per-klinik') }}" class="bg-white border rounded-lg p-4 hover:border-emerald-600">
        <div class="text-2xl font-bold">{{ $totalSessions }}</div>
        <div class="text-xs text-gray-500">Total sesi → konsultan per klinik</div>
    </a>
    <a href="{{ route('admin.pervorma-sessions') }}" class="bg-white border rounded-lg p-4 hover:border-emerald-600">
        <div class="text-2xl font-bold">Rp{{ number_format((int) $verifiedRevenue, 0, ',', '.') }}</div>
        <div class="text-xs text-gray-500">Gross terkumpul (verified) → sesi performa</div>
    </a>
</div>

{{-- F8: modul tenant & ops admin terjangkau dari UI, bukan cuma ketik URL manual. --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3 mb-8">
    <a href="{{ route('admin.tenants.kliniks.index') }}" class="bg-white border rounded-lg p-3 hover:border-emerald-600 text-sm font-medium">Klinik (tenant)</a>
    <a href="{{ route('admin.tenants.sekolahs.index') }}" class="bg-white border rounded-lg p-3 hover:border-emerald-600 text-sm font-medium">Sekolah (tenant)</a>
    <a href="{{ route('admin.tenants.perusahaans.index') }}" class="bg-white border rounded-lg p-3 hover:border-emerald-600 text-sm font-medium">Perusahaan (tenant)</a>
    <a href="{{ route('admin.consultant-assignment.index') }}" class="bg-white border rounded-lg p-3 hover:border-emerald-600 text-sm font-medium">Assignment Konsultan</a>
    <a href="{{ route('admin.audit-logs.index') }}" class="bg-white border rounded-lg p-3 hover:border-emerald-600 text-sm font-medium">Audit Log</a>
    <a href="{{ route('admin.system-health.index') }}" class="bg-white border rounded-lg p-3 hover:border-emerald-600 text-sm font-medium">System Health</a>
    <a href="{{ route('admin.ops-queue.index') }}" class="bg-white border rounded-lg p-3 hover:border-emerald-600 text-sm font-medium">Ops Queue</a>
</div>

<div class="grid md:grid-cols-3 gap-4 mb-8">
    <div class="md:col-span-2 bg-white border rounded-lg p-4">
        <h2 class="font-semibold text-sm mb-3">Sesi masuk per hari (30 hari)</h2>
        <canvas id="chartDaily" height="100"></canvas>
    </div>
    <div class="bg-white border rounded-lg p-4">
        <h2 class="font-semibold text-sm mb-3">Sesi per mitra (top 8)</h2>
        <canvas id="chartKlinik" height="160"></canvas>
    </div>
</div>

<h2 id="pending" class="font-semibold mb-2">Mitra menunggu approval ({{ $pendingKliniks->count() }})</h2>
<div class="overflow-x-auto bg-white border rounded-lg mb-8"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Klinik</th><th class="p-3">Brand</th><th class="p-3">Daftar</th><th class="p-3">Aksi</th>
    </tr></thead>
    <tbody>
        @forelse($pendingKliniks as $k)
        <tr class="border-b">
            <td class="p-3">{{ $k->name }}</td>
            <td class="p-3">{{ $k->brand }}</td>
            <td class="p-3">{{ $k->created_at->translatedFormat('d M Y H.i') }}</td>
            <td class="p-3">
                <form method="POST" action="{{ route('admin.kliniks.approve', $k) }}">@csrf
                    <button class="text-xs bg-emerald-700 text-white rounded px-3 py-1.5 font-semibold">Approve</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="4" class="p-3 text-gray-400">Tidak ada mitra pending.</td></tr>
        @endforelse
    </tbody>
</table></div>

<h2 class="font-semibold mb-2">Margin mitra menunggu payout</h2>
<div class="overflow-x-auto bg-white border rounded-lg mb-8"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Mitra</th><th class="p-3">Sesi</th><th class="p-3">Net (Soluusi)</th><th class="p-3">Margin (mitra)</th><th class="p-3">Aksi</th>
    </tr></thead>
    <tbody>
        @forelse($splitsPending as $sp)
        <tr class="border-b">
            <td class="p-3">{{ $sp->klinik->brand_name ?? '-' }}</td>
            <td class="p-3">#{{ $sp->session_id }}</td>
            <td class="p-3">Rp{{ number_format($sp->net_amount, 0, ',', '.') }}</td>
            <td class="p-3 font-semibold text-emerald-700">Rp{{ number_format($sp->klinik_margin, 0, ',', '.') }}</td>
            <td class="p-3">
                <form method="POST" action="{{ route('admin.splits.settle', $sp) }}">@csrf
                    <button class="text-xs bg-emerald-700 text-white rounded px-3 py-1.5 font-semibold">Settle</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="5" class="p-3 text-gray-400">Tidak ada margin pending.</td></tr>
        @endforelse
    </tbody>
</table></div>

<h2 class="font-semibold mb-2">Performa HPA jatuh tempo — <a href="{{ route('admin.pervorma-sessions') }}" class="text-emerald-700 hover:underline font-normal">lihat sesi kontributor →</a></h2>
<div class="overflow-x-auto bg-white border rounded-lg mb-8"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Klinik</th><th class="p-3">Associate</th><th class="p-3">Stage</th><th class="p-3">Amount</th><th class="p-3">Jatuh tempo</th><th class="p-3">Aksi</th>
    </tr></thead>
    <tbody>
        @forelse($pervormasDue as $c)
        <tr class="border-b">
            <td class="p-3"><a class="text-emerald-700 hover:underline" href="{{ route('admin.pervorma-sessions', ['klinik_id' => $c->klinik_id, 'stage' => $c->stage]) }}">{{ $c->klinik->brand_name ?? '-' }}</a></td>
            <td class="p-3">{{ $c->associate ? trim($c->associate->firstname . ' ' . $c->associate->lastname) : '-' }}</td>
            <td class="p-3">Stage {{ $c->stage }}</td>
            <td class="p-3">Rp{{ number_format($c->amount, 0, ',', '.') }}</td>
            <td class="p-3">{{ optional($c->due_at)->translatedFormat('d M Y') ?? '-' }}</td>
            <td class="p-3">
                <form method="POST" action="{{ route('admin.pervormas.pay', ['vertical' => 'hap', 'pervorma' => $c->id]) }}">@csrf
                    <button class="text-xs bg-emerald-700 text-white rounded px-3 py-1.5 font-semibold">Tandai dibayar</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="p-3 text-gray-400">Tidak ada performa due.</td></tr>
        @endforelse
    </tbody>
</table></div>

<h2 class="font-semibold mb-2">Aktivitas sesi terbaru (platform)</h2>
<div class="overflow-x-auto bg-white border rounded-lg mb-8"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Waktu order</th><th class="p-3">Mitra</th><th class="p-3">Konsultan</th><th class="p-3">Jadwal</th><th class="p-3">Bayar</th><th class="p-3">Status</th>
    </tr></thead>
    <tbody>
        @forelse($recentSessions as $s)
        <tr class="border-b">
            <td class="p-3">{{ $s->order_at->translatedFormat('d M Y H.i') }}</td>
            <td class="p-3">{{ $s->klinik->brand_name ?? '-' }}</td>
            <td class="p-3"><a class="text-emerald-700 hover:underline" href="{{ route('klinik.consultants.show', $s->consultant_id) }}">{{ trim(($s->consultant->user->firstname ?? '') . ' ' . ($s->consultant->user->lastname ?? '')) }}</a></td>
            <td class="p-3">{{ optional($s->schedule)->started_at?->translatedFormat('d M H.i') }}</td>
            <td class="p-3">
                <span class="text-xs px-2 py-0.5 rounded {{ $s->payment_status === 'verified' ? 'bg-emerald-100 text-emerald-800' : ($s->payment_status === 'waiting' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-500') }}">{{ ucfirst($s->payment_status) }}</span>
            </td>
            <td class="p-3">
                <span class="text-xs px-2 py-0.5 rounded {{ $s->status === 'done' ? 'bg-emerald-100 text-emerald-800' : ($s->status === 'no_show' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500') }}">{{ ucfirst(str_replace('_', ' ', $s->status)) }}</span>
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="p-3 text-gray-400">Belum ada sesi.</td></tr>
        @endforelse
    </tbody>
</table></div>

<h2 class="font-semibold mb-2">Mitra terbaru</h2>
<div class="overflow-x-auto bg-white border rounded-lg mb-8"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Klinik</th><th class="p-3">Associate (HPA)</th><th class="p-3">Status</th><th class="p-3">Daftar</th>
    </tr></thead>
    <tbody>
        @foreach($recentKliniks as $k)
        <tr class="border-b">
            <td class="p-3"><a class="text-emerald-700 hover:underline" href="{{ route('klinik.dashboard', ['klinik_id' => $k->id]) }}">{{ $k->name }} →</a></td>
            <td class="p-3">{{ $k->associate ? trim($k->associate->firstname . ' ' . $k->associate->lastname) : '-' }}</td>
            <td class="p-3">
                <span class="text-xs px-2 py-0.5 rounded {{ $k->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ $k->is_active ? 'Aktif' : 'Pending' }}</span>
            </td>
            <td class="p-3">{{ $k->created_at->translatedFormat('d M Y') }}</td>
        </tr>
        @endforeach
    </tbody>
</table></div>

<h2 class="font-semibold mb-2">Sesi perlu review ops</h2>
<p class="text-sm text-gray-500 mb-6">Dua sumber flag: (a) konsultan mark-done &gt; {{ config('klinik.confirm_window_hours') }} jam tanpa konfirmasi customer — possible fake; (b) pembayaran masuk setelah sesi ditutup (expired/batal/refund) — dana perlu dikembalikan atau sesi dijadwalkan ulang.</p>
<div class="overflow-x-auto bg-white border rounded-lg mb-8"><table class="w-full text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Sesi</th><th class="p-3">Alasan</th><th class="p-3">Konsultan</th><th class="p-3">Klinik</th><th class="p-3">Mark-done</th><th class="p-3">Aksi</th>
    </tr></thead>
    <tbody>
        @forelse($flagged as $s)
        <tr class="border-b">
            <td class="p-3">#{{ $s->id }} · {{ $s->order_number }}</td>
            <td class="p-3">
                @if(in_array($s->payment_status, [\App\Models\ConsultationSession::PAYMENT_STATUS_CANCELED, \App\Models\ConsultationSession::PAYMENT_STATUS_FAILED, \App\Models\ConsultationSession::PAYMENT_STATUS_REFUNDED], true))
                    <span class="text-xs bg-red-100 text-red-800 rounded px-2 py-1">Pembayaran masuk setelah sesi ditutup</span>
                @else
                    <span class="text-xs bg-amber-100 text-amber-800 rounded px-2 py-1">Menunggu konfirmasi &gt; {{ config('klinik.confirm_window_hours') }} jam</span>
                @endif
            </td>
            <td class="p-3">{{ trim(($s->consultant->user->firstname ?? '') . ' ' . ($s->consultant->user->lastname ?? '')) }}</td>
            <td class="p-3">{{ $s->klinik->brand_name ?? '-' }}</td>
            <td class="p-3">{{ optional($s->konsultan_done_at)->translatedFormat('d M Y H.i') ?: '-' }}</td>
            <td class="p-3">
                <form method="POST" action="{{ route('admin.sessions.verify', $s) }}">@csrf
                    <button class="text-xs bg-emerald-700 text-white rounded px-2 py-1">Verifikasi Manual</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="p-3 text-gray-400">Tidak ada sesi yang perlu review.</td></tr>
        @endforelse
    </tbody>
</table></div>

<div class="mt-4">{{ $flagged->links() }}</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
const daily = @json($chartDaily);
new Chart(document.getElementById('chartDaily'), {
    type: 'line',
    data: {
        labels: daily.labels,
        datasets: [{
            data: daily.data,
            borderColor: '#047857',
            backgroundColor: 'rgba(4,120,87,.12)',
            fill: true,
            tension: .35,
            pointRadius: 0,
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});
const perKlinik = @json($chartKlinik);
new Chart(document.getElementById('chartKlinik'), {
    type: 'bar',
    data: {
        labels: perKlinik.labels,
        datasets: [{ data: perKlinik.data, backgroundColor: '#0c3b2e', borderRadius: 6 }]
    },
    options: {
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});
</script>
@endsection
