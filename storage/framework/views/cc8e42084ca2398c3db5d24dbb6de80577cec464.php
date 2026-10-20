<?php $__env->startSection('content'); ?>
<div class="flex flex-wrap items-start justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl font-bold">Markup Paket — <?php echo e($klinik->brand_name); ?></h1>
        <p class="text-sm text-gray-500 mt-1">Harga dikunci saat checkout link dibuat. Nilai di atas cap otomatis ditolak.</p>
    </div>
    <a href="<?php echo e(route('klinik.dashboard', ['klinik_id' => $klinik->id])); ?>" class="text-sm text-emerald-700 font-semibold hover:underline">← Dashboard klinik</a>
</div>

<form method="GET" class="mb-4 flex gap-2 max-w-xl">
    <input name="q" value="<?php echo e(request('q')); ?>" placeholder="Cari paket atau konsultan" class="flex-1 border rounded-lg px-3 py-2 text-sm">
    <button class="bg-emerald-700 text-white rounded-lg px-4 py-2 text-sm font-semibold">Cari</button>
</form>

<div class="overflow-x-auto bg-white border rounded-lg">
    <table class="w-full text-sm">
        <thead><tr class="border-b text-left text-gray-500">
            <th class="p-3">Konsultan</th><th class="p-3">Paket</th><th class="p-3">Net</th><th class="p-3">Markup</th><th class="p-3">Final</th><th class="p-3">Aksi</th>
        </tr></thead>
        <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $packages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $package): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
                $markup = (int) optional($rules->get($package->id))->markup_amount;
                $formId = 'markup-' . $package->id;
            ?>
            <tr class="border-b">
                <td class="p-3"><?php echo e($package->consultant->user->full_name ?? '—'); ?></td>
                <td class="p-3"><span class="font-medium"><?php echo e($package->name); ?></span><div class="text-xs text-gray-400"><?php echo e(strtoupper($package->mode)); ?> · <?php echo e($package->duration); ?> menit</div></td>
                <td class="p-3">Rp<?php echo e(number_format($package->price, 0, ',', '.')); ?></td>
                <td class="p-3">
                    <form id="<?php echo e($formId); ?>" method="POST" action="<?php echo e(route('admin.kliniks.markups.store', $klinik)); ?>"><?php echo csrf_field(); ?>
                        <input type="hidden" name="consultant_package_id" value="<?php echo e($package->id); ?>">
                        <input type="number" name="markup_amount" min="0" step="1000" value="<?php echo e(old('consultant_package_id') == $package->id ? old('markup_amount') : $markup); ?>" class="w-36 border rounded px-2 py-1.5">
                    </form>
                </td>
                <td class="p-3 font-semibold">Rp<?php echo e(number_format((int) $package->price + $markup, 0, ',', '.')); ?></td>
                <td class="p-3"><button form="<?php echo e($formId); ?>" class="text-xs bg-emerald-700 text-white rounded px-3 py-1.5 font-semibold">Simpan</button></td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="6" class="p-6 text-center text-gray-400">Tidak ada paket konsultan aktif.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-4"><?php echo e($packages->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/hermes/workspace/projects/soluusi-b2b-klinik/resources/views/admin/markup-rules.blade.php ENDPATH**/ ?>