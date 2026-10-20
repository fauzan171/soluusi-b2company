
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $__env->yieldContent('title', $title ?? config('whitelabel.default_brand')); ?></title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<?php
    $user = auth()->user();
    $role = optional($user?->role)->name;
    $isPerusahaanRoute = request()->routeIs('perusahaan.*');
    $isSekolahRoute = request()->routeIs('sekolah.*');
    $perusahaanBrand = $isPerusahaanRoute ? ($perusahaan->brand ?? optional(\App\Support\PerusahaanContext::current())->brand) : null;
    $sekolahBrand = $isSekolahRoute ? ($sekolah->brand ?? optional(\App\Support\SekolahContext::current())->brand) : null;
    $klinikBrand = request()->routeIs('klinik.*') ? optional(\App\Support\KlinikContext::current())->brand : null;
    $pageBrand = $perusahaanBrand ?: $sekolahBrand ?: $klinikBrand ?: config('whitelabel.default_brand');

    // ponytail: nav didefinisikan di layout; pindah ke config kalau portal lain butuh menu dinamis
    $navItems = match (true) {
        $isSekolahRoute && in_array($role, ['bk', 'admin']) => [
            ['route' => 'sekolah.dashboard', 'pattern' => 'sekolah.dashboard', 'label' => 'Dashboard', 'icon' => 'chart'],
            ['route' => 'sekolah.students', 'pattern' => 'sekolah.students*', 'label' => 'Siswa', 'icon' => 'users'],
        ],
        $isSekolahRoute => [
            ['route' => 'sekolah.student', 'pattern' => 'sekolah.student', 'label' => 'Dashboard', 'icon' => 'heart'],
            ['route' => 'sekolah.assessment.form', 'pattern' => 'sekolah.assessment*', 'label' => 'Asesmen', 'icon' => 'sparkles'],
            ['route' => 'sekolah.booking', 'pattern' => 'sekolah.booking*', 'label' => 'Booking', 'icon' => 'calendar'],
        ],
        $isPerusahaanRoute && in_array($role, ['hr_admin', 'admin']) => [
            ['route' => 'perusahaan.dashboard', 'pattern' => 'perusahaan.dashboard', 'label' => 'Dashboard', 'icon' => 'chart'],
            ['route' => 'perusahaan.report', 'pattern' => 'perusahaan.report', 'label' => 'Laporan', 'icon' => 'document-arrow-down'],
            ['route' => 'perusahaan.employees', 'pattern' => 'perusahaan.employees*', 'label' => 'Karyawan', 'icon' => 'users'],
        ],
        $isPerusahaanRoute => [
            ['route' => 'perusahaan.employee', 'pattern' => 'perusahaan.employee', 'label' => 'Dashboard', 'icon' => 'heart'],
            ['route' => 'perusahaan.assessment.form', 'pattern' => 'perusahaan.assessment*', 'label' => 'Asesmen', 'icon' => 'sparkles'],
            ['route' => 'perusahaan.booking', 'pattern' => 'perusahaan.booking*', 'label' => 'Booking', 'icon' => 'calendar'],
        ],
        request()->routeIs('klinik.*') => [
            ['route' => 'klinik.dashboard', 'pattern' => 'klinik.dashboard', 'label' => 'Dashboard', 'icon' => 'chart'],
            ['route' => 'klinik.consultants.index', 'pattern' => 'klinik.consultants*', 'label' => 'Konsultan', 'icon' => 'users'],
            ['route' => 'klinik.schedules.index', 'pattern' => 'klinik.schedules*', 'label' => 'Jadwal', 'icon' => 'calendar'],
            ['route' => 'klinik.links.index', 'pattern' => 'klinik.links*', 'label' => 'Checkout Link', 'icon' => 'sparkles'],
        ],
        request()->routeIs('admin.*') => [
            ['route' => 'admin.dashboard', 'pattern' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'chart'],
        ],
        request()->routeIs('associate.*') => [
            ['route' => 'associate.dashboard', 'pattern' => 'associate.dashboard', 'label' => 'Dashboard HPA', 'icon' => 'chart'],
        ],
        default => [],
    };
?>
<body class="bg-paper text-ink min-h-screen">
    <div class="flex min-h-screen">
        <?php if(count($navItems)): ?>
        <aside class="portal-sidebar hidden md:flex w-60 shrink-0 flex-col p-4 text-white sticky top-0 h-screen">
            <div class="flex items-center gap-3 border-b border-white/10 pb-4 px-2">
                <div class="grid h-10 w-10 place-items-center rounded-2xl bg-white/12 text-base font-bold">
                    <?php echo e(strtoupper(substr($pageBrand, 0, 1))); ?>

                </div>
                <div class="min-w-0">
                    <p class="text-[10px] uppercase tracking-[0.22em] text-white/60">Portal</p>
                    <h1 class="text-sm font-semibold leading-tight truncate"><?php echo e($pageBrand); ?></h1>
                </div>
            </div>
            <nav class="mt-4 space-y-1 flex-1">
                <?php $__currentLoopData = $navItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e(route($item['route'])); ?>"
                       class="portal-nav-link <?php echo e(request()->routeIs($item['pattern']) ? 'is-active' : ''); ?>">
                        <?php echo $__env->make('perusahaan.partials.icon', ['name' => $item['icon']], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                        <?php echo e($item['label']); ?>

                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </nav>
            <?php if(auth()->guard()->check()): ?>
            <form method="POST" action="<?php echo e(route('logout')); ?>" class="border-t border-white/10 pt-3 px-2"><?php echo csrf_field(); ?>
                <button class="text-sm text-white/60 hover:text-white w-full text-left">Keluar</button>
            </form>
            <?php endif; ?>
        </aside>
        <?php endif; ?>

        <div class="flex-1 min-w-0">
            <header class="bg-white/80 backdrop-blur border-b border-[#e7e2d9] px-6 py-3 flex items-center justify-between sticky top-0 z-10">
                <div class="flex items-center gap-4">
                    <?php if(!count($navItems)): ?>
                        <span class="font-bold text-pine-700"><?php echo e($pageBrand); ?></span>
                    <?php else: ?>
                        <span class="font-semibold text-pine-900"><?php echo $__env->yieldContent('title', $title ?? 'Dashboard'); ?></span>
                    <?php endif; ?>
                    <?php if(auth()->guard()->check()): ?>
                    
                    <?php if($role === 'admin'): ?>
                        <?php if(request()->routeIs('perusahaan.*')): ?>
                            <select onchange="if(this.value) location.search = '?perusahaan_id=' + this.value"
                                    class="text-sm border border-[#e7e2d9] rounded-lg px-2 py-1 bg-white">
                                <option value="">— pilih perusahaan —</option>
                                <?php $__currentLoopData = \App\Models\Perusahaan\Perusahaan::orderBy('brand')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($p->id); ?>" <?php if(session('admin_perusahaan_id') == $p->id): echo 'selected'; endif; ?>><?php echo e($p->brand); ?><?php echo e($p->is_active ? '' : ' (nonaktif)'); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        <?php elseif(request()->routeIs('sekolah.*')): ?>
                            <select onchange="if(this.value) location.search = '?sekolah_id=' + this.value"
                                    class="text-sm border border-[#e7e2d9] rounded-lg px-2 py-1 bg-white">
                                <option value="">— pilih sekolah —</option>
                                <?php $__currentLoopData = \App\Models\Sekolah\Sekolah::orderBy('brand')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($s->id); ?>" <?php if(session('admin_sekolah_id') == $s->id): echo 'selected'; endif; ?>><?php echo e($s->brand); ?><?php echo e($s->is_active ? '' : ' (nonaktif)'); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        <?php elseif(request()->routeIs('klinik.*') || request()->routeIs('admin.*')): ?>
                            <select onchange="if(this.value) location.search = '?klinik_id=' + this.value"
                                    class="text-sm border border-[#e7e2d9] rounded-lg px-2 py-1 bg-white">
                                <option value="">— pilih klinik —</option>
                                <?php $__currentLoopData = \App\Models\Klinik\Klinik::orderBy('brand')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($k->id); ?>" <?php if(session('admin_klinik_id') == $k->id): echo 'selected'; endif; ?>><?php echo e($k->brand); ?><?php echo e($k->is_active ? '' : ' (pending)'); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php if(auth()->guard()->check()): ?>
                    <form method="POST" action="<?php echo e(route('logout')); ?>"><?php echo csrf_field(); ?>
                        <button class="text-sm text-gray-500 hover:text-gray-800">Keluar</button>
                    </form>
                <?php endif; ?>
            </header>

            <nav aria-label="Menu portal" class="md:hidden flex flex-wrap gap-3 px-6 py-3 bg-white border-b">
                <?php $__currentLoopData = $navItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route($item['route'])); ?>" <?php if(request()->routeIs($item['pattern'])): ?> aria-current="page" <?php endif; ?>><?php echo e($item['label']); ?></a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </nav>
            <main class="w-full px-6 py-6 lg:px-8">
                <div class="mx-auto w-full max-w-[1400px]">
                <?php if(session('success')): ?>
                    <div class="mb-4 p-3 rounded-xl bg-pine-100 text-pine-800 text-sm break-all"><?php echo e(session('success')); ?></div>
                <?php endif; ?>
                <?php if(session('error')): ?>
                    <div class="mb-4 p-3 rounded-xl bg-red-100 text-red-800 text-sm"><?php echo e(session('error')); ?></div>
                <?php endif; ?>
                <?php if(session('info')): ?>
                    <div class="mb-4 p-3 rounded-xl bg-blue-100 text-blue-800 text-sm"><?php echo e(session('info')); ?></div>
                <?php endif; ?>
                <?php if(isset($errors) && $errors->any()): ?>
                    <div class="mb-4 p-3 rounded-xl bg-red-100 text-red-800 text-sm">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><div><?php echo e($e); ?></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>
                <?php echo $__env->yieldContent('content'); ?>
                </div>
            </main>
        </div>
    </div>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /home/hermes/workspace/projects/soluusi-b2b-klinik/resources/views/layouts/app.blade.php ENDPATH**/ ?>