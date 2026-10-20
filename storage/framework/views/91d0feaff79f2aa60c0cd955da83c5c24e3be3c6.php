
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($brand); ?> — Checkout Konsultasi</title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen">
    <main class="max-w-lg mx-auto p-6">
        <div class="text-xl font-bold text-emerald-700 mb-6"><?php echo e($brand); ?></div>

        <?php if(session('error')): ?>
            <div class="mb-4 p-3 rounded bg-red-100 text-red-800 text-sm"><?php echo e(session('error')); ?></div>
        <?php endif; ?>
        <?php if($errors->any()): ?>
            <div class="mb-4 p-3 rounded bg-red-100 text-red-800 text-sm">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><div><?php echo e($e); ?></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        
        <div class="bg-white border rounded-xl p-6 mb-4">
            <h2 class="font-bold text-lg mb-4">Konsultan Kamu</h2>
            <div class="flex gap-4">
                <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center text-xl font-bold shrink-0">
                    <?php echo e(substr(trim($link->consultant->user->firstname ?? '?'), 0, 1)); ?>

                </div>
                <div class="min-w-0">
                    <div class="font-bold"><?php echo e(trim(($link->consultant->user->firstname ?? '') . ' ' . ($link->consultant->user->lastname ?? ''))); ?></div>
                    <div class="text-sm text-gray-500">
                        <?php echo e($link->consultant->user->regency->name ?? ''); ?><?php echo e($link->consultant->user->regency?->province ? ', ' . $link->consultant->user->regency->province->name : ''); ?>

                    </div>
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        <?php $__currentLoopData = $link->consultant->categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <span class="text-[11px] bg-emerald-50 text-emerald-800 rounded px-2 py-0.5"><?php echo e($category->name); ?></span>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>
            <?php if($link->consultant->about): ?>
                <p class="text-sm text-gray-600 leading-relaxed mt-4"><?php echo e($link->consultant->about); ?></p>
            <?php endif; ?>
        </div>

        <div class="bg-white border rounded-xl p-6 mb-6">
            <h1 class="font-bold text-lg mb-4">Detail Sesi</h1>
            <dl class="text-sm space-y-2">
                <div class="flex justify-between"><dt class="text-gray-500">Konsultan</dt><dd class="font-semibold"><?php echo e(trim(($link->consultant->user->firstname ?? '') . ' ' . ($link->consultant->user->lastname ?? ''))); ?></dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Paket</dt><dd class="font-semibold"><?php echo e($link->package->name ?? '-'); ?> (<?php echo e($link->package->duration ?? '-'); ?> mnt)</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Jadwal</dt><dd class="font-semibold"><?php echo e($link->schedule->started_at->translatedFormat('l, d M Y H:i')); ?> WIB</dd></div>
                <div class="flex justify-between border-t pt-2 mt-2"><dt class="text-gray-500">Total</dt><dd class="font-bold text-emerald-700">Rp<?php echo e(number_format($link->final_price, 0, ',', '.')); ?></dd></div>
            </dl>
        </div>

        <form method="POST" action="<?php echo e(route('checkout.pay', $link->token)); ?>" class="bg-white border rounded-xl p-6 space-y-4">
            <?php echo csrf_field(); ?>
            <h2 class="font-bold">Data Kamu</h2>
            <div>
                <label class="block text-sm mb-1">Nama lengkap</label>
                <input name="customer_name" value="<?php echo e(old('customer_name')); ?>" required class="w-full border rounded px-3 py-2">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm mb-1">Email</label>
                    <input type="email" name="customer_email" value="<?php echo e(old('customer_email')); ?>" required class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm mb-1">No. HP / WA</label>
                    <input name="customer_phone" value="<?php echo e(old('customer_phone')); ?>" required class="w-full border rounded px-3 py-2">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm mb-1">Tanggal lahir</label>
                    <input type="date" name="customer_dob" value="<?php echo e(old('customer_dob')); ?>" required class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm mb-1">Jenis kelamin</label>
                    <select name="customer_gender" required class="w-full border rounded px-3 py-2">
                        <option value="">—</option>
                        <option value="male" <?php if(old('customer_gender') === 'male'): echo 'selected'; endif; ?>>Laki-laki</option>
                        <option value="female" <?php if(old('customer_gender') === 'female'): echo 'selected'; endif; ?>>Perempuan</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm mb-1">Kota/Kabupaten domisili</label>
                <select name="customer_regency_id" required class="w-full border rounded px-3 py-2">
                    <option value="">— pilih —</option>
                    <?php $__currentLoopData = $regencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($r->id); ?>" <?php if(old('customer_regency_id') == $r->id): echo 'selected'; endif; ?>><?php echo e($r->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div>
                <label class="block text-sm mb-1">Keluhan singkat (untuk konsultan)</label>
                <textarea name="complaint" rows="3" class="w-full border rounded px-3 py-2"><?php echo e(old('complaint')); ?></textarea>
            </div>
            <div>
                <label class="block text-sm mb-1">Kode voucher (opsional)</label>
                <input name="voucher" value="<?php echo e(old('voucher')); ?>" class="w-full border rounded px-3 py-2">
            </div>
            <label class="flex items-start gap-2 text-xs text-gray-600">
                <input type="checkbox" name="consent" value="1" required class="mt-0.5">
                <span>Saya menyetujui pemrosesan data pribadi saya untuk keperluan pemesanan dan penyelenggaraan sesi konsultasi ini (UU PDP).</span>
            </label>
            <button class="w-full bg-emerald-700 text-white rounded py-3 font-bold">Bayar Rp<?php echo e(number_format($link->final_price, 0, ',', '.')); ?></button>
        </form>
    </main>
</body>
</html>
<?php /**PATH /home/hermes/workspace/projects/soluusi-b2b-klinik/resources/views/checkout/show.blade.php ENDPATH**/ ?>