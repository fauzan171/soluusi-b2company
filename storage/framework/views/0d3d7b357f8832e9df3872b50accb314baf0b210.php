<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun — <?php echo e(config('whitelabel.default_brand')); ?></title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="bg-paper text-ink min-h-screen grid place-items-center">
    <div class="w-full max-w-md p-6">
        <div class="portal-card p-6">
            <h1 class="text-lg font-bold text-pine-900">Daftar akun <?php echo e($type === 'siswa' ? 'siswa' : 'karyawan'); ?></h1>
            <p class="text-sm text-gray-500 mt-1 mb-5">
                <?php if($type === 'siswa'): ?>
                Gunakan email yang terdaftar di roster sekolah (dari guru BK). Izin orang tua wajib untuk mendaftar.
                <?php else: ?>
                Gunakan email yang terdaftar di roster perusahaan (dari HR).
                <?php endif; ?>
            </p>

            <?php if(session('error')): ?><div class="mb-4 p-3 rounded-xl bg-red-100 text-red-800 text-sm"><?php echo e(session('error')); ?></div><?php endif; ?>
            <?php if(isset($errors) && $errors->any()): ?>
            <div class="mb-4 p-3 rounded-xl bg-red-100 text-red-800 text-sm">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><div><?php echo e($e); ?></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo e(request()->fullUrl()); ?>" class="space-y-3">
                <?php echo csrf_field(); ?>
                <input type="text" name="name" placeholder="Nama lengkap (sesuai roster)" value="<?php echo e(old('name')); ?>"
                       class="w-full border border-[#e7e2d9] rounded-xl px-3 py-2 text-sm" required>
                <input type="email" name="email" placeholder="Email terdaftar" value="<?php echo e(old('email')); ?>"
                       class="w-full border border-[#e7e2d9] rounded-xl px-3 py-2 text-sm" required>
                <input type="password" name="password" placeholder="Password (min. 8 karakter)"
                       class="w-full border border-[#e7e2d9] rounded-xl px-3 py-2 text-sm" required minlength="8">
                <p class="text-xs text-gray-600">Undangan untuk <?php echo e($invitation->email); ?>. Akun existing wajib login sebagai pemilik email; password existing tidak diubah. Consent wali/program dicatat HR/BK terpisah.</p>
                <button class="w-full bg-pine-700 text-white text-sm font-semibold py-3 rounded-xl">Daftar & Masuk</button>
            </form>
            <p class="text-xs text-gray-400 mt-4">Sudah punya akun? <a href="<?php echo e(route('loginIndex')); ?>" class="text-pine-700 font-semibold">Login di sini</a></p>
        </div>
    </div>
</body>
</html>
<?php /**PATH /home/hermes/workspace/projects/soluusi-b2b-klinik/resources/views/auth/self-register.blade.php ENDPATH**/ ?>