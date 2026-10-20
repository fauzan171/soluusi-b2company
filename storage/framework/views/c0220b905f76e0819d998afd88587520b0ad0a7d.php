<?php $__env->startSection('content'); ?>
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold">Checkout Link</h1>
    <a href="<?php echo e(route('klinik.links.create')); ?>" class="bg-emerald-700 text-white rounded px-4 py-2 text-sm font-semibold">+ Buat Link</a>
</div>

<section class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-sm font-semibold text-emerald-950">Link booking customer klinik</p>
            <p class="mt-1 text-xs leading-5 text-emerald-800">Kirim link ini ke customer agar mereka bisa memilih konsultan, jadwal, paket, lalu bayar sendiri.</p>
        </div>
        <a href="<?php echo e(route('checkout.klinik', $klinik->uuid)); ?>" target="_blank" class="shrink-0 rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Buka Link Customer</a>
    </div>
    <code class="mt-3 block break-all rounded-xl border border-emerald-200 bg-white px-3 py-2 text-xs text-emerald-900"><?php echo e(route('checkout.klinik', $klinik->uuid)); ?></code>
</section>

<?php if($createdLink): ?>
<section class="mb-5 rounded-lg border border-emerald-300 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-sm font-bold text-emerald-900">Link customer berhasil dibuat</p>
            <p class="mt-1 text-xs leading-5 text-gray-500">
                <?php echo e(trim(($createdLink->consultant->user->firstname ?? '') . ' ' . ($createdLink->consultant->user->lastname ?? ''))); ?>

                · <?php echo e($createdLink->package->name ?? 'Paket konsultasi'); ?>

                · <?php echo e($createdLink->schedule->started_at->format('d M Y H:i')); ?>

            </p>
            <div class="mt-3 space-y-2">
                <code class="block break-all rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-950"><?php echo e(route('checkout.show', $createdLink->token)); ?></code>
                <code class="block break-all rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600">Dashboard: <?php echo e(route('checkout.status', $createdLink->token)); ?></code>
            </div>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2">
            <a href="<?php echo e(route('checkout.show', $createdLink->token)); ?>" target="_blank" class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Buka Checkout</a>
            <a href="<?php echo e(route('checkout.status', $createdLink->token)); ?>" target="_blank" class="rounded-lg border border-emerald-200 px-4 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">Dashboard</a>
        </div>
    </div>
</section>
<?php endif; ?>

<table class="w-full bg-white border rounded-lg text-sm">
    <thead><tr class="border-b text-left text-gray-500">
        <th class="p-3">Konsultan</th><th class="p-3">Jadwal</th><th class="p-3">Paket</th><th class="p-3">Harga</th><th class="p-3">Status</th><th class="p-3">Link customer</th>
    </tr></thead>
    <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $links; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr class="border-b">
            <td class="p-3"><?php echo e(trim(($l->consultant->user->firstname ?? '') . ' ' . ($l->consultant->user->lastname ?? ''))); ?></td>
            <td class="p-3"><?php echo e($l->schedule->started_at->format('d M Y H:i')); ?></td>
            <td class="p-3"><?php echo e($l->package->name ?? '-'); ?></td>
            <td class="p-3">Rp<?php echo e(number_format($l->final_price, 0, ',', '.')); ?></td>
            <td class="p-3">
                <?php if($l->used_at): ?> <span class="text-emerald-700">Terpakai</span>
                <?php elseif($l->expires_at->isPast()): ?> <span class="text-red-600">Kedaluwarsa</span>
                <?php else: ?> <span class="text-gray-600">Aktif</span> <?php endif; ?>
            </td>
            <td class="p-3">
                <div class="space-y-2">
                    <?php if($l->isUsable() || $l->used_at): ?>
                        <code class="block break-all text-xs"><?php echo e(route('checkout.show', $l->token)); ?></code>
                    <?php else: ?>
                        <span class="text-gray-300">Checkout tidak aktif</span>
                    <?php endif; ?>
                    <a href="<?php echo e(route('checkout.status', $l->token)); ?>" target="_blank" class="inline-flex rounded border border-emerald-200 px-2 py-1 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-50">Dashboard customer</a>
                </div>
            </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="6" class="p-3 text-gray-400">Belum ada link.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<div class="mt-4"><?php echo e($links->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/hermes/workspace/projects/soluusi-b2b-klinik/resources/views/klinik/links/index.blade.php ENDPATH**/ ?>