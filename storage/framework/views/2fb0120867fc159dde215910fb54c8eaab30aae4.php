<?php $__env->startSection('content'); ?>
<div class="max-w-3xl">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-xl font-bold">Booking Sesi Konsultasi</h1>
            <p class="text-sm text-gray-500">Sisa kuota bulan ini: <span class="font-semibold <?php echo e($quotaLeft > 0 ? 'text-pine-700' : 'text-red-600'); ?>"><?php echo e($quotaLeft); ?> sesi</span></p>
        </div>
        <a href="<?php echo e(route('perusahaan.employee')); ?>" class="text-sm text-pine-700 hover:underline">← Kembali</a>
    </div>

    <?php if($quotaLeft <= 0): ?>
    <div class="bg-terra-50 border border-terra-200 rounded-lg p-4 text-sm text-terra-800">
        Kuota bulan ini habis. Pilih slot bulan depan, atau hubungi HR kamu.
    </div>
    <?php endif; ?>
    <form method="POST" action="<?php echo e(route('perusahaan.booking.store')); ?>" class="space-y-4">
        <?php echo csrf_field(); ?>

        <div>
            <label for="konsultan_type" class="block text-sm font-medium mb-2">Jenis konsultan</label>
            <select id="konsultan_type" name="konsultan_type" required class="w-full border rounded px-3 py-2">
                <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($key); ?>" <?php if(old('konsultan_type') === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <?php $__errorArgs = ['konsultan_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div>
            <label for="schedule_id" class="block text-sm font-medium mb-2">Slot konsultan tersedia (WIB)</label>
            <select id="schedule_id" name="schedule_id" required class="w-full border rounded px-3 py-2">
                <option value="">— Pilih slot sesuai jenis konsultan —</option>
                <?php $__currentLoopData = $slots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $slot): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($slot->id); ?>" <?php if(old('schedule_id') == $slot->id): echo 'selected'; endif; ?>><?php echo e($slot->started_at->translatedFormat('D, d M Y H:i')); ?>–<?php echo e($slot->ended_at->format('H:i')); ?> · <?php echo e($slot->consultant->user->firstname); ?> · <?php echo e($slot->mode); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <?php if($slots->isEmpty()): ?><p class="text-sm text-gray-500 mt-2">Belum ada slot tersedia. Hubungi pengelola program.</p><?php endif; ?>
            <?php $__errorArgs = ['schedule_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-800">
            Sesi berlangsung online, rahasia penuh. HR hanya melihat jumlah agregat — tidak pernah isi atau identitas sesi kamu.
        </div>

        <button type="submit" class="w-full bg-pine-700 text-white text-sm font-semibold rounded px-4 py-2.5 hover:bg-pine-800">Jadwalkan Sesi</button>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/hermes/workspace/projects/soluusi-b2b-klinik/resources/views/perusahaan/booking.blade.php ENDPATH**/ ?>