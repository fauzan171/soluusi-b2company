{{-- Shared portal layout. White-label: brand dari config. Sidebar role-aware. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $title ?? config('whitelabel.internal_brand', 'Soluusi'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Alpine global: banyak halaman portal pakai x-data/x-cloak; bundle Vite tidak memuatnya. --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
</head>
@php
    $user = auth()->user();
    $role = optional($user?->role)->name;
    $isPerusahaanRoute = request()->routeIs('perusahaan.*');
    $isSekolahRoute = request()->routeIs('sekolah.*');
    $perusahaanBrand = $isPerusahaanRoute ? ($perusahaan->brand ?? optional(\App\Support\PerusahaanContext::current())->brand ?? optional($user?->perusahaanEmployee?->perusahaan)->brand) : null;
    $sekolahBrand = $isSekolahRoute ? ($sekolah->brand ?? optional(\App\Support\SekolahContext::current())->brand) : null;
    $klinikBrand = request()->routeIs('klinik.*') ? optional(\App\Support\KlinikContext::current())->brand : null;
    $pageBrand = $perusahaanBrand ?: $sekolahBrand ?: $klinikBrand ?: config('whitelabel.internal_brand', 'Soluusi');

    // ponytail: nav didefinisikan di layout; pindah ke config kalau portal lain butuh menu dinamis
    $navItems = match (true) {
        $isSekolahRoute && in_array($role, ['bk', 'admin']) => [
            ['route' => 'sekolah.dashboard', 'pattern' => 'sekolah.dashboard', 'label' => 'Dashboard', 'icon' => 'chart'],
            ['route' => 'sekolah.students', 'pattern' => 'sekolah.students', 'label' => 'Siswa', 'icon' => 'users'],
            ['route' => 'sekolah.review', 'pattern' => 'sekolah.review*', 'label' => 'Review Permintaan', 'icon' => 'check'],
            ['route' => 'sekolah.consultants.pool', 'pattern' => 'sekolah.consultants.pool*', 'label' => 'Pool Konsultan', 'icon' => 'star'],
            ['route' => 'sekolah.students.import', 'pattern' => 'sekolah.students.import*', 'label' => 'Import Siswa', 'icon' => 'document-arrow-down'],
            ['route' => 'sekolah.report', 'pattern' => 'sekolah.report', 'label' => 'Laporan', 'icon' => 'document-arrow-down'],
        ],
        request()->routeIs('consultant.*') => [
            ['route' => 'consultant.sessions', 'pattern' => 'consultant.sessions*', 'label' => 'Sesi Saya', 'icon' => 'calendar'],
            ['route' => 'consultant.calendar', 'pattern' => 'consultant.calendar*', 'label' => 'Kalender', 'icon' => 'calendar'],
        ],
        $isSekolahRoute && $role === 'sekolah_staff' => [
            ['route' => 'sekolah.staff', 'pattern' => 'sekolah.staff', 'label' => 'Dashboard', 'icon' => 'heart'],
            ['route' => 'sekolah.staff.assessment.list', 'pattern' => 'sekolah.staff.assessment*', 'label' => 'Asesmen', 'icon' => 'sparkles'],
            ['route' => 'sekolah.staff.booking', 'pattern' => 'sekolah.staff.booking*', 'label' => 'Booking', 'icon' => 'calendar'],
        ],
        $isSekolahRoute => [
            ['route' => 'sekolah.student', 'pattern' => 'sekolah.student', 'label' => 'Dashboard', 'icon' => 'heart'],
            ['route' => 'sekolah.assessment.list', 'pattern' => 'sekolah.assessment*', 'label' => 'Asesmen', 'icon' => 'sparkles'],
            ['route' => 'sekolah.booking', 'pattern' => 'sekolah.booking*', 'label' => 'Booking', 'icon' => 'calendar'],
        ],
        $isPerusahaanRoute && in_array($role, ['hr_admin', 'admin']) => [
            // wv4: HR nav 10 menu (research2-dashboards).
            ['route' => 'perusahaan.dashboard', 'pattern' => 'perusahaan.dashboard', 'label' => 'Ringkasan Perusahaan', 'icon' => 'chart'],
            ['route' => 'perusahaan.engagement', 'pattern' => 'perusahaan.engagement*', 'label' => 'Keterlibatan Karyawan', 'icon' => 'users'],
            ['route' => 'perusahaan.report.assessment', 'pattern' => 'perusahaan.report.assessment*', 'label' => 'Asesmen Wellbeing', 'icon' => 'sparkles'],
            ['route' => 'perusahaan.wellbeing', 'pattern' => 'perusahaan.wellbeing*', 'label' => 'Insight Wellbeing', 'icon' => 'heart'],
            ['route' => 'perusahaan.expert-utilization', 'pattern' => 'perusahaan.expert-utilization*', 'label' => 'Utilisasi Expert', 'icon' => 'star'],
            ['route' => 'perusahaan.action-plan.index', 'pattern' => 'perusahaan.action-plan*', 'label' => 'Action Plan', 'icon' => 'check'],
            ['route' => 'perusahaan.program-progress', 'pattern' => 'perusahaan.program-progress*', 'label' => 'Progres Program', 'icon' => 'arrow-trending-up'],
            ['route' => 'perusahaan.report', 'pattern' => 'perusahaan.report*', 'label' => 'Laporan', 'icon' => 'document-arrow-down'],
            ['route' => 'perusahaan.program-subscription', 'pattern' => 'perusahaan.program-subscription*', 'label' => 'Program & Subscription', 'icon' => 'receipt'],
            ['route' => 'perusahaan.review', 'pattern' => 'perusahaan.review*', 'label' => 'Antrian Review', 'icon' => 'check'],
            ['route' => 'perusahaan.employees', 'pattern' => 'perusahaan.employees*', 'label' => 'Karyawan', 'icon' => 'users'],
            ['route' => 'perusahaan.consultants.pool', 'pattern' => 'perusahaan.consultants.*', 'label' => 'Konsultan', 'icon' => 'star'],
        ],
        $isPerusahaanRoute => [
            // wv4: employee nav 8 menu.
            ['route' => 'perusahaan.employee', 'pattern' => 'perusahaan.employee', 'label' => 'Kesejahteraanku', 'icon' => 'heart'],
            ['route' => 'perusahaan.assessment.instruments', 'pattern' => 'perusahaan.assessment.instruments*', 'label' => 'Asesmenku', 'icon' => 'sparkles'],
            ['route' => 'perusahaan.employee.results', 'pattern' => 'perusahaan.employee.results*', 'label' => 'Hasilku', 'icon' => 'document-arrow-down'],
            ['route' => 'perusahaan.consultants', 'pattern' => 'perusahaan.consultants*', 'label' => 'Expert Chat', 'icon' => 'chat'],
            ['route' => 'perusahaan.booking', 'pattern' => 'perusahaan.booking*', 'label' => 'Konsultasiku', 'icon' => 'calendar'],
            ['route' => 'perusahaan.employee.activities', 'pattern' => 'perusahaan.employee.activities*', 'label' => 'Aktivitasku', 'icon' => 'bolt'],
            ['route' => 'perusahaan.employee.privacy', 'pattern' => 'perusahaan.employee.privacy*', 'label' => 'Privasi & Consent', 'icon' => 'shield'],
        ],
        request()->routeIs('klinik.*') => [
            ['route' => 'klinik.dashboard', 'pattern' => 'klinik.dashboard', 'label' => 'Dashboard', 'icon' => 'chart'],
            ['route' => 'klinik.consultants.index', 'pattern' => 'klinik.consultants*', 'label' => 'Konsultan', 'icon' => 'users'],
            ['route' => 'klinik.schedules.index', 'pattern' => 'klinik.schedules*', 'label' => 'Jadwal', 'icon' => 'calendar'],
            ['route' => 'klinik.links.index', 'pattern' => 'klinik.links*', 'label' => 'Checkout Link', 'icon' => 'sparkles'],
        ],
        request()->routeIs('admin.*') => [
            // wv4: internal nav 8 menu (research2-dashboards). Menu lama
            // (tenant klinik/sekolah, ops, audit, health) tetap reachable
            // dari grid modul dashboard.
            ['route' => 'admin.dashboard', 'pattern' => 'admin.dashboard', 'label' => 'Overview', 'icon' => 'chart'],
            ['route' => 'admin.perusahaans.index', 'pattern' => 'admin.perusahaans*', 'label' => 'Perusahaan', 'icon' => 'building'],
            ['route' => 'admin.wellbeing-programs.index', 'pattern' => 'admin.wellbeing-programs*', 'label' => 'Program Wellbeing', 'icon' => 'heart'],
            ['route' => 'admin.employees.index', 'pattern' => 'admin.employees*', 'label' => 'Karyawan', 'icon' => 'users'],
            ['route' => 'admin.assessment-instruments.index', 'pattern' => 'admin.assessment-instruments*', 'label' => 'Asesmen', 'icon' => 'sparkles'],
            ['route' => 'admin.consultant-assignment.index', 'pattern' => 'admin.consultant-assignment*', 'label' => 'Expert & Konsultasi', 'icon' => 'star'],
            ['route' => 'admin.ops-queue.index', 'pattern' => 'admin.ops-queue*', 'label' => 'Ops Queue', 'icon' => 'document-arrow-down'],
            ['route' => 'admin.billing.index', 'pattern' => 'admin.billing*', 'label' => 'Billing & Kontrak', 'icon' => 'receipt'],
        ],
        request()->routeIs('associate.*') => [
            // ponytail: #komisi = anchor di halaman yang sama, jadi hanya Dashboard yang active (hindari double-active).
            ['route' => 'associate.dashboard', 'pattern' => 'associate.dashboard', 'label' => 'Dashboard HPA', 'icon' => 'chart'],
            // F4: deep-link payout (anchor #komisi) — nav associate bukan dead-end.
            ['route' => 'associate.dashboard', 'pattern' => '', 'label' => 'Komisi & Payout', 'icon' => 'receipt', 'hash' => '#komisi'],
        ],
        default => [],
    };
@endphp
@php($bare = $bare ?? false)
<body class="bg-paper text-ink min-h-screen">
    <div class="flex min-h-screen">
        @if(count($navItems))
        <aside class="portal-sidebar hidden md:flex w-60 shrink-0 flex-col p-4 text-white sticky top-0 h-screen overflow-hidden">
            <div class="flex shrink-0 items-center gap-3 border-b border-white/10 pb-4 px-2">
                <div class="grid h-10 w-10 place-items-center rounded-2xl bg-white/12 text-base font-bold">
                    {{ strtoupper(substr($pageBrand, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] uppercase tracking-[0.22em] text-white/60">Portal</p>
                    <h1 class="text-sm font-semibold leading-tight truncate">{{ $pageBrand }}</h1>
                </div>
            </div>
            <nav class="portal-nav mt-4 space-y-1 flex-1 min-h-0 overflow-y-auto overscroll-contain">
                @foreach($navItems as $item)
                    <a href="{{ route($item['route']) }}{{ $item['hash'] ?? '' }}"
                       class="portal-nav-link {{ request()->routeIs($item['pattern']) ? 'is-active' : '' }}">
                        @include('perusahaan.partials.icon', ['name' => $item['icon']])
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
            @auth
            <a href="{{ route('password.change') }}" class="block shrink-0 border-t border-white/10 pt-3 px-2 text-sm text-white/60 hover:text-white">Ubah password</a>
            <form method="POST" action="{{ route('logout') }}" onsubmit="try{Object.keys(sessionStorage).forEach(function(k){if(k.indexOf('assessment:')===0)sessionStorage.removeItem(k)})}catch(e){}" class="shrink-0 pt-2 px-2">@csrf
                <button class="text-sm text-white/60 hover:text-white w-full text-left">Keluar</button>
            </form>
            @endauth
        </aside>
        @endif

        <div class="flex-1 min-w-0">
            @unless($bare)
            <header class="bg-white/80 backdrop-blur border-b border-[#e7e2d9] px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between sticky top-0 z-10">
                <div class="flex items-center gap-4">
                    @if(!count($navItems))
                        <span class="font-bold text-pine-700">{{ $pageBrand }}</span>
                    @else
                        <span class="font-semibold text-pine-900">@yield('title', $title ?? 'Dashboard')</span>
                    @endif
                    @auth
                    {{-- Super admin bisa buka portal klinik/perusahaan mana saja — pilih di sini. --}}
                    @if($role === 'admin')
                        @if(request()->routeIs('perusahaan.*'))
                            <select onchange="if(this.value){ const u = new URL(location); u.searchParams.set('perusahaan_id', this.value); location = u; }"
                                    class="text-sm border border-[#e7e2d9] rounded-lg px-2 py-1 bg-white">
                                <option value="">— pilih perusahaan —</option>
                                @foreach(\App\Models\Perusahaan\Perusahaan::orderBy('brand')->get() as $p)
                                    <option value="{{ $p->id }}" @selected(session('admin_perusahaan_id') == $p->id)>{{ $p->brand }}{{ $p->is_active ? '' : ' (nonaktif)' }}</option>
                                @endforeach
                            </select>
                        @elseif(request()->routeIs('sekolah.*'))
                            <select onchange="if(this.value){ const u = new URL(location); u.searchParams.set('sekolah_id', this.value); location = u; }"
                                    class="text-sm border border-[#e7e2d9] rounded-lg px-2 py-1 bg-white">
                                <option value="">— pilih sekolah —</option>
                                @foreach(\App\Models\Sekolah\Sekolah::orderBy('brand')->get() as $s)
                                    <option value="{{ $s->id }}" @selected(session('admin_sekolah_id') == $s->id)>{{ $s->brand }}{{ $s->is_active ? '' : ' (nonaktif)' }}</option>
                                @endforeach
                            </select>
                        @elseif(request()->routeIs('klinik.*'))
                            <select onchange="if(this.value) location.href = '{{ url('/klinik/dashboard') }}?klinik_id=' + this.value"
                                    class="text-sm border border-[#e7e2d9] rounded-lg px-2 py-1 bg-white">
                                <option value="">— pilih klinik —</option>
                                @foreach(\App\Models\Klinik\Klinik::orderBy('brand')->get() as $k)
                                    <option value="{{ $k->id }}" @selected(session('admin_klinik_id') == $k->id)>{{ $k->brand }}{{ $k->is_active ? '' : ' (pending)' }}</option>
                                @endforeach
                            </select>
                        @endif
                        {{-- A4: di halaman admin, switcher 3 vertikal — pilih tenant langsung buka portal dashboard-nya
                             dengan ?{tenant}_id= context override (Context::current simpan di session). --}}
                        @if(request()->routeIs('admin.*'))
                            <select data-tenant-switcher="klinik" onchange="if(this.value) location.href = '{{ url('/klinik/dashboard') }}?klinik_id=' + this.value"
                                    class="text-sm border border-[#e7e2d9] rounded-lg px-2 py-1 bg-white">
                                <option value="">— pilih klinik —</option>
                                @foreach(\App\Models\Klinik\Klinik::orderBy('brand')->get() as $k)
                                    <option value="{{ $k->id }}" @selected(session('admin_klinik_id') == $k->id)>{{ $k->brand }}{{ $k->is_active ? '' : ' (pending)' }}</option>
                                @endforeach
                            </select>
                            <select data-tenant-switcher="perusahaan" onchange="if(this.value) location.href = '{{ url('/perusahaan/dashboard') }}?perusahaan_id=' + this.value"
                                    class="text-sm border border-[#e7e2d9] rounded-lg px-2 py-1 bg-white">
                                <option value="">— pilih perusahaan —</option>
                                {{-- ponytail: hanya tenant aktif — tenant pending aktivasi kelola via ops-queue (A7). --}}
                                @foreach(\App\Models\Perusahaan\Perusahaan::where('is_active', true)->orderBy('brand')->get() as $p)
                                    <option value="{{ $p->id }}" @selected(session('admin_perusahaan_id') == $p->id)>{{ $p->brand }}</option>
                                @endforeach
                            </select>
                            <select data-tenant-switcher="sekolah" onchange="if(this.value) location.href = '{{ url('/sekolah-portal/dashboard') }}?sekolah_id=' + this.value"
                                    class="text-sm border border-[#e7e2d9] rounded-lg px-2 py-1 bg-white">
                                <option value="">— pilih sekolah —</option>
                                {{-- ponytail: hanya tenant aktif — tenant pending aktivasi kelola via ops-queue (A7). --}}
                                @foreach(\App\Models\Sekolah\Sekolah::where('is_active', true)->orderBy('brand')->get() as $s)
                                    <option value="{{ $s->id }}" @selected(session('admin_sekolah_id') == $s->id)>{{ $s->brand }}</option>
                                @endforeach
                            </select>
                        @endif
                    @endif
                    @endauth
                </div>
            </header>

            <details class="md:hidden group bg-white border-b">
                <summary class="flex items-center justify-between px-4 sm:px-6 py-3 cursor-pointer list-none font-semibold text-sm" aria-label="Menu portal">
                    <span>Menu</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </summary>
                <nav aria-label="Menu portal" class="flex flex-col gap-2 px-4 sm:px-6 pb-3">
                    @foreach($navItems as $item)
                        <a href="{{ route($item['route']) }}{{ $item['hash'] ?? '' }}" class="text-sm py-1 {{ request()->routeIs($item['pattern']) ? 'font-bold' : '' }}" @if(request()->routeIs($item['pattern'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                    @endforeach
                    @auth
                        <a href="{{ route('password.change') }}" class="text-sm py-1 text-gray-500">Ubah password</a>
                        <form method="POST" action="{{ route('logout') }}" onsubmit="try{Object.keys(sessionStorage).forEach(function(k){if(k.indexOf('assessment:')===0)sessionStorage.removeItem(k)})}catch(e){}">@csrf
                            <button class="text-left text-sm py-1 text-gray-500">Keluar</button>
                        </form>
                    @endauth
                </nav>
            </details>
            @endunless
            <main @class(['w-full', 'px-4 py-6 sm:px-6 lg:px-8' => ! $bare])>
                <div @class(['w-full', 'mx-auto max-w-7xl' => ! $bare, 'px-4 pt-4 sm:px-6' => $bare && (session('success') || session('error') || session('info') || session('crisis') || (isset($errors) && $errors->any()))])>
                @if(!empty($viewAs))
                    <div class="mb-4 p-3 rounded-xl bg-amber-100 text-amber-900 text-sm flex items-center justify-between gap-3" role="status">
                        <span>Anda sedang melihat sebagai <strong>{{ $viewAs['name'] }}</strong> <span class="text-amber-700">({{ $viewAs['target_role'] ?? '?' }})</span> — mode view-as superadmin, read-only.
                            @if(!empty($viewAs['expires_at']))
                                <span class="text-amber-700">Sisa waktu: <strong>{{ now()->diffInMinutes(\Illuminate\Support\Carbon::parse($viewAs['expires_at']), false) }} menit</strong>.</span>
                            @endif
                        </span>
                        <form method="POST" action="{{ route('admin.view-as.stop') }}">@csrf
                            <button class="text-xs font-semibold underline hover:no-underline">Berhenti</button>
                        </form>
                    </div>
                @endif
                @if(session('success'))
                    <div class="mb-4 p-3 rounded-xl bg-pine-100 text-pine-800 text-sm break-all">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="mb-4 p-3 rounded-xl bg-red-100 text-red-800 text-sm">{{ session('error') }}</div>
                @endif
                @if(session('info'))
                    <div class="mb-4 p-3 rounded-xl bg-blue-100 text-blue-800 text-sm">{{ session('info') }}</div>
                @endif
                @if(session('crisis'))
                    <div class="mb-4 p-4 rounded-xl bg-red-600 text-white text-sm font-semibold" role="alert">{{ session('crisis') }}</div>
                @endif
                @if(isset($errors) && $errors->any())
                    <div class="mb-4 p-3 rounded-xl bg-red-100 text-red-800 text-sm">
                        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                    </div>
                @endif
                @yield('content')
                </div>
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
