<?php $__env->startSection('content'); ?>
<div class="max-w-sm mx-auto mt-16 bg-white rounded-xl border p-8">
    <h1 class="text-lg font-bold mb-6">Masuk</h1>
    <form method="POST" action="<?php echo e(route('login')); ?>" class="space-y-4">
        <?php echo csrf_field(); ?>
        <div>
            <label class="block text-sm mb-1">Email</label>
            <input type="email" name="email" value="<?php echo e(old('email')); ?>" required class="w-full border rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm mb-1">Password</label>
            <input type="password" name="password" required class="w-full border rounded px-3 py-2">
        </div>
        <button class="w-full bg-emerald-700 text-white rounded py-2 font-semibold">Masuk</button>
    </form>
    <div class="mt-4 text-center">
        <a href="<?php echo e(route('login.google', 'google')); ?>" class="text-sm text-emerald-700 hover:underline">Masuk dengan Google</a>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/hermes/workspace/projects/soluusi-b2b-klinik/resources/views/auth/login.blade.php ENDPATH**/ ?>