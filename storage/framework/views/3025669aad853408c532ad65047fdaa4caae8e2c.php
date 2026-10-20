<?php $__env->startSection('content'); ?>
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold">Jadwal Konsultan</h1>
        <p class="text-sm text-gray-500">Jadwal yang diatur klinik untuk konsultan.</p>
    </div>
    <a href="<?php echo e(route('klinik.consultants.index')); ?>" class="bg-emerald-700 text-white rounded px-4 py-2 text-sm font-semibold">Pilih Konsultan</a>
</div>

<table class="w-full bg-white border rounded-lg text-sm">
    <thead>
        <tr class="border-b text-left text-gray-500">
            <th class="p-3">Konsultan</th>
            <th class="p-3">Mode</th>
            <th class="p-3">Jadwal</th>
            <th class="p-3">Lokasi / Link</th>
            <th class="p-3">Status</th>
        </tr>
    </thead>
    <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $schedules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $schedule): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr class="border-b align-top">
                <td class="p-3"><?php echo e(trim(($schedule->consultant->user->firstname ?? '') . ' ' . ($schedule->consultant->user->lastname ?? ''))); ?></td>
                <td class="p-3">
                    <span class="text-xs font-semibold rounded px-2 py-1 <?php echo e(($schedule->mode ?? 'online') === 'offline' ? 'bg-amber-100 text-amber-800' : 'bg-sky-100 text-sky-800'); ?>">
                        <?php echo e(strtoupper($schedule->mode ?? 'online')); ?>

                    </span>
                </td>
                <td class="p-3"><?php echo e($schedule->started_at->format('d M Y H:i')); ?> - <?php echo e($schedule->ended_at->format('H:i')); ?></td>
                <td class="p-3 text-xs text-gray-500">
                    <?php if(($schedule->mode ?? 'online') === 'offline'): ?>
                        <?php echo e($schedule->location ?: '-'); ?>

                    <?php else: ?>
                        <?php echo e($schedule->meeting_url ?: '-'); ?>

                    <?php endif; ?>
                </td>
                <td class="p-3"><?php echo e(ucfirst($schedule->status ?? 'confirmed')); ?></td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="5" class="p-3 text-gray-400">Belum ada jadwal. Pilih konsultan lalu atur slot pertama.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<div class="mt-4"><?php echo e($schedules->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/hermes/workspace/projects/soluusi-b2b-klinik/resources/views/klinik/schedules/index.blade.php ENDPATH**/ ?>