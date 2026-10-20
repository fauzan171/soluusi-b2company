<?php $__env->startSection('content'); ?>
<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Katalog layanan klinik</p>
        <h1 class="mt-1 text-2xl font-bold text-gray-900">Pilih Konsultan</h1>
        <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-500">
            Pilih dari katalog konsultan aktif untuk <?php echo e($klinik->name); ?>. Lihat kategori, mode layanan, harga mulai, dan slot yang bisa langsung dibuat checkout link.
            <?php if($defaultRegency): ?>
                <a href="<?php echo e(route('klinik.consultants.index', ['semua_daerah' => 1])); ?>" class="font-semibold text-emerald-700">Lihat semua daerah</a>
            <?php endif; ?>
        </p>
    </div>
    <?php if($consultants->total() > 0): ?>
        <div class="rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
            <span class="font-bold"><?php echo e($consultants->total()); ?></span> konsultan tersedia
        </div>
    <?php endif; ?>
</div>


<form method="GET" class="mb-6 rounded-lg border border-[#e7e2d9] bg-white p-4 shadow-sm">
    <div class="grid gap-3 lg:grid-cols-[minmax(220px,1fr),190px,170px,190px,190px,auto] lg:items-end">
        <div>
            <label class="mb-1.5 block text-xs font-semibold text-gray-500">Cari kebutuhan</label>
            <input name="q" value="<?php echo e(request('q')); ?>" placeholder="Contoh: anxiety, anak, gizi, keluarga" class="w-full rounded-lg border border-[#ddd8cf] px-3 py-2.5 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-semibold text-gray-500">Kategori</label>
            <select name="category_id" class="w-full rounded-lg border border-[#ddd8cf] px-3 py-2.5 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                <option value="">Semua</option>
                <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($c->id); ?>" <?php if(request('category_id') == $c->id): echo 'selected'; endif; ?>><?php echo e($c->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-semibold text-gray-500">Mode</label>
            <select name="mode" class="w-full rounded-lg border border-[#ddd8cf] px-3 py-2.5 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                <option value="">Semua</option>
                <option value="online" <?php if(request('mode') === 'online'): echo 'selected'; endif; ?>>Online</option>
                <option value="offline" <?php if(request('mode') === 'offline'): echo 'selected'; endif; ?>>Tatap muka</option>
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-semibold text-gray-500">Provinsi</label>
            <select name="province_id" class="w-full rounded-lg border border-[#ddd8cf] px-3 py-2.5 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100" onchange="this.form.submit()">
                <option value="">Semua</option>
                <?php $__currentLoopData = $provinces; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($p->id); ?>" <?php if(request('province_id') == $p->id): echo 'selected'; endif; ?>><?php echo e($p->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-semibold text-gray-500">Kota/Kabupaten <?php if($klinik->regency_id): ?><a href="<?php echo e(route('klinik.consultants.index', array_merge(request()->query(), ['regency_id' => $klinik->regency_id]))); ?>" class="text-emerald-700">(daerah klinik)</a><?php endif; ?></label>
            <select name="regency_id" class="w-full rounded-lg border border-[#ddd8cf] px-3 py-2.5 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                <option value="">Semua</option>
                <?php $__currentLoopData = $regencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($r->id); ?>" <?php if(request('regency_id') == $r->id): echo 'selected'; endif; ?>><?php echo e($r->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="flex gap-2">
            <button class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Filter</button>
            <a href="<?php echo e(route('klinik.consultants.index')); ?>" class="rounded-lg border border-[#ddd8cf] px-4 py-2.5 text-sm font-semibold text-gray-500 hover:bg-gray-50">Reset</a>
        </div>
    </div>
</form>


<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    <?php $__empty_1 = true; $__currentLoopData = $consultants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <?php
        $name = trim(($c->user->firstname ?? '') . ' ' . ($c->user->lastname ?? ''));
        $startingPrice = $c->packages
            ->map(fn ($package) => (int) $package->price + (int) ($markups[$package->id] ?? 0))
            ->filter()
            ->min();
        $primaryCategory = $c->categories->first()?->name ?? 'Konsultasi';
    ?>
    <article class="flex min-h-[310px] flex-col rounded-lg border border-[#e7e2d9] bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md">
        <div class="flex items-start gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg bg-emerald-700 text-lg font-bold text-white">
                <?php echo e(strtoupper(substr($name ?: 'K', 0, 1))); ?>

            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-800"><?php echo e($primaryCategory); ?></span>
                    <span class="rounded bg-gray-100 px-2 py-1 text-[11px] font-semibold text-gray-600"><?php echo e($c->schedules_count); ?> slot</span>
                </div>
                <h3 class="mt-2 line-clamp-2 text-base font-bold leading-6 text-gray-900"><?php echo e($name ?: 'Konsultan Klinik'); ?></h3>
                <p class="mt-1 text-xs text-gray-500"><?php echo e($c->user->regency->name ?? 'Lokasi fleksibel'); ?><?php echo e($c->user->regency?->province ? ', ' . $c->user->regency->province->name : ''); ?></p>
            </div>
        </div>

        <p class="mt-4 line-clamp-3 text-sm leading-6 text-gray-600"><?php echo e($c->about ?: 'Profil konsultan tersedia untuk sesi klinik.'); ?></p>

        <div class="mt-4 grid grid-cols-3 gap-2">
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                <div class="text-[11px] font-semibold uppercase text-gray-400">Mulai</div>
                <div class="mt-1 text-sm font-bold text-gray-900">
                    <?php echo e($startingPrice ? 'Rp' . number_format($startingPrice, 0, ',', '.') : '-'); ?>

                </div>
            </div>
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                <div class="text-[11px] font-semibold uppercase text-gray-400">Online</div>
                <div class="mt-1 text-sm font-bold text-gray-900"><?php echo e($c->online_schedules_count); ?> slot</div>
            </div>
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                <div class="text-[11px] font-semibold uppercase text-gray-400">Offline</div>
                <div class="mt-1 text-sm font-bold text-gray-900"><?php echo e($c->offline_schedules_count); ?> slot</div>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-1.5">
            <?php $__currentLoopData = $c->categories->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <span class="rounded border border-gray-200 px-2 py-1 text-[11px] font-medium text-gray-600"><?php echo e($cat->name); ?></span>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <div class="mt-auto flex items-center justify-between gap-3 border-t pt-4">
            <span class="text-xs text-gray-500"><?php echo e($c->packages_count); ?> paket layanan</span>
            <a href="<?php echo e(route('klinik.consultants.show', $c)); ?>" class="rounded-lg bg-emerald-700 px-4 py-2.5 text-xs font-semibold text-white hover:bg-emerald-800">Pilih Paket</a>
        </div>
    </article>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="col-span-full rounded-lg border border-dashed border-[#d8d2c8] bg-white p-8 text-center">
        <div class="text-base font-semibold text-gray-800">Katalog belum menemukan konsultan.</div>
        <p class="mt-2 text-sm text-gray-500">Coba reset filter, pilih semua daerah, atau tambahkan slot jadwal untuk konsultan klinik.</p>
        <a href="<?php echo e(route('klinik.consultants.index')); ?>" class="mt-4 inline-flex rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Reset katalog</a>
    </div>
    <?php endif; ?>
</div>

<div class="mt-6"><?php echo e($consultants->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/hermes/workspace/projects/soluusi-b2b-klinik/resources/views/klinik/consultants/index.blade.php ENDPATH**/ ?>