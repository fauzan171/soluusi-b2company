<?php $__env->startSection('content'); ?>
<h1 class="text-xl font-bold mb-1">Buat Checkout Link</h1>
<div class="mb-4 flex items-center justify-between bg-emerald-50 border border-emerald-200 rounded-lg px-4 py-3">
    <p class="text-sm text-emerald-900">Konsultan terpilih: <strong><?php echo e(trim(($selectedConsultant->user->firstname ?? '') . ' ' . ($selectedConsultant->user->lastname ?? ''))); ?></strong></p>
    <div class="flex gap-3 text-xs">
        <a href="<?php echo e(route('klinik.schedules.create', ['consultant_id' => $selectedConsultant->id])); ?>" class="text-emerald-700 hover:underline">Atur jadwal</a>
        <a href="<?php echo e(route('klinik.consultants.show', $selectedConsultant)); ?>" class="text-emerald-700 hover:underline">Lihat profil</a>
        <a href="<?php echo e(route('klinik.consultants.index')); ?>" class="text-emerald-700 hover:underline">Ganti konsultan</a>
    </div>
</div>

<form method="POST" action="<?php echo e(route('klinik.links.store')); ?>" class="bg-white border rounded-lg p-6 max-w-xl space-y-4">
    <?php echo csrf_field(); ?>
    <div>
        <label class="block text-sm mb-1">Jadwal konsultan (slot tersedia)</label>
        <select name="schedule_id" required class="w-full border rounded px-3 py-2">
            <option value="">— pilih jadwal —</option>
            <?php $__currentLoopData = $schedules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($s->id); ?>" <?php if(old('schedule_id') == $s->id || request('schedule_id') == $s->id): echo 'selected'; endif; ?>>
                [<?php echo e(strtoupper($s->mode ?? 'online')); ?>] <?php echo e($s->started_at->format('d M Y H:i')); ?>

            </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <?php if($schedules->isEmpty()): ?>
            <p class="text-xs text-amber-700 mt-1">Belum ada jadwal untuk konsultan ini. Atur jadwal dulu sebelum membuat link.</p>
        <?php endif; ?>
    </div>
    <div>
        <label class="block text-sm mb-1">Paket</label>
        <select name="consultant_package_id" required class="w-full border rounded px-3 py-2">
            <option value="">— pilih paket —</option>
            <?php $__currentLoopData = $packages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $markup = (int) (optional($markups->get($p->id))->markup_amount ?? 0);
                $final = (int) $p->price + $markup;
            ?>
            <option value="<?php echo e($p->id); ?>" <?php if(old('consultant_package_id') == $p->id || request('consultant_package_id') == $p->id): echo 'selected'; endif; ?>>
                [<?php echo e(strtoupper($p->mode ?? 'online')); ?>] <?php echo e($p->name); ?> — Rp<?php echo e(number_format($final, 0, ',', '.')); ?> · <?php echo e($p->duration); ?> mnt
            </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <p class="text-xs text-gray-400 mt-1">Harga sudah termasuk margin klinik. Pilih paket dan jadwal dengan mode yang sama.</p>
    </div>
    <button class="bg-emerald-700 text-white rounded px-4 py-2 font-semibold" <?php if($schedules->isEmpty()): echo 'disabled'; endif; ?>>Generate Link</button>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/hermes/workspace/projects/soluusi-b2b-klinik/resources/views/klinik/links/create.blade.php ENDPATH**/ ?>