
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($brand); ?> - Booking Konsultasi</title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="min-h-screen bg-paper text-ink antialiased">
    <main class="mx-auto max-w-6xl px-4 py-5 sm:px-6 lg:py-8">
        <section class="overflow-hidden rounded-[1.35rem] bg-gradient-to-br from-pine-950 via-pine-900 to-pine-700 px-5 py-6 text-white shadow-[0_22px_60px_rgba(6,42,32,.18)] sm:px-7">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-pine-200">Booking Konsultasi</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl"><?php echo e($brand); ?></h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-pine-50/80">Pilih konsultan dan jadwal yang tersedia, lalu lengkapi data untuk melanjutkan pembayaran.</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-sm text-pine-50">
                    <span class="font-semibold"><?php echo e($consultants->total()); ?></span> konsultan dengan slot aktif
                </div>
            </div>
        </section>

        <?php if(session('error')): ?>
            <div class="mt-5 rounded-xl bg-red-100 px-4 py-3 text-sm text-red-800"><?php echo e(session('error')); ?></div>
        <?php endif; ?>
        <?php if($errors->any()): ?>
            <div class="mt-5 rounded-xl bg-red-100 px-4 py-3 text-sm text-red-800">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><div><?php echo e($e); ?></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        <section class="mt-5 grid gap-4 lg:grid-cols-[minmax(0,1.04fr),minmax(360px,.96fr)]">
            <div class="space-y-4">
                <form method="GET" class="rounded-2xl border border-[#e7e2d9] bg-white p-4 shadow-sm">
                    <div class="grid gap-3 md:grid-cols-[minmax(0,1fr),180px,180px,auto] md:items-end">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-gray-500">Cari konsultan</label>
                            <input name="q" value="<?php echo e(request('q')); ?>" placeholder="Nama konsultan" class="w-full rounded-xl border border-[#ddd8cf] px-3 py-2.5 text-sm outline-none focus:border-pine-600 focus:ring-4 focus:ring-pine-100">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-gray-500">Keahlian</label>
                            <select name="category_id" class="w-full rounded-xl border border-[#ddd8cf] px-3 py-2.5 text-sm outline-none focus:border-pine-600 focus:ring-4 focus:ring-pine-100">
                                <option value="">Semua</option>
                                <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($category->id); ?>" <?php if(request('category_id') == $category->id): echo 'selected'; endif; ?>><?php echo e($category->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-gray-500">Mode</label>
                            <select name="mode" class="w-full rounded-xl border border-[#ddd8cf] px-3 py-2.5 text-sm outline-none focus:border-pine-600 focus:ring-4 focus:ring-pine-100">
                                <option value="">Semua</option>
                                <option value="online" <?php if(request('mode') === 'online'): echo 'selected'; endif; ?>>Online</option>
                                <option value="offline" <?php if(request('mode') === 'offline'): echo 'selected'; endif; ?>>Tatap muka</option>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button class="rounded-xl bg-pine-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-pine-800">Filter</button>
                            <a href="<?php echo e(route('checkout.klinik', $klinik->uuid)); ?>" class="rounded-xl border border-[#ddd8cf] px-4 py-2.5 text-sm font-semibold text-gray-500 hover:bg-gray-50">Reset</a>
                        </div>
                    </div>
                </form>

                <div class="grid gap-3 md:grid-cols-2">
                    <?php $__empty_1 = true; $__currentLoopData = $consultants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $consultant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $selected = $selectedConsultant?->id === $consultant->id;
                            $name = trim(($consultant->user->firstname ?? '') . ' ' . ($consultant->user->lastname ?? ''));
                            $url = route('checkout.klinik', array_filter([
                                'klinik' => $klinik->uuid,
                                'consultant_id' => $consultant->id,
                                'q' => request('q'),
                                'category_id' => request('category_id'),
                                'mode' => request('mode'),
                            ]));
                        ?>
                        <a href="<?php echo e($url); ?>" class="rounded-2xl border bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-pine-300 hover:shadow-md <?php echo e($selected ? 'border-pine-500 ring-4 ring-pine-100' : 'border-[#e7e2d9]'); ?>">
                            <div class="flex gap-3">
                                <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-pine-50 text-lg font-bold text-pine-700"><?php echo e(substr($name ?: '?', 0, 1)); ?></div>
                                <div class="min-w-0">
                                    <h2 class="truncate text-sm font-semibold text-gray-900"><?php echo e($name ?: 'Konsultan'); ?></h2>
                                    <p class="mt-0.5 text-xs text-gray-500"><?php echo e($consultant->user->regency->name ?? 'Lokasi fleksibel'); ?></p>
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        <?php $__currentLoopData = $consultant->categories->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-600"><?php echo e($category->name); ?></span>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center justify-between border-t border-dashed border-[#e7e2d9] pt-3 text-xs">
                                <span class="font-semibold text-pine-700"><?php echo e($consultant->schedules_count); ?> slot tersedia</span>
                                <span class="text-gray-400">Pilih</span>
                            </div>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="rounded-2xl border border-[#e7e2d9] bg-white p-8 text-center text-sm text-gray-400 md:col-span-2">Belum ada konsultan yang sesuai dengan filter.</div>
                    <?php endif; ?>
                </div>

                <div><?php echo e($consultants->links()); ?></div>
            </div>

            <aside class="rounded-2xl border border-[#e7e2d9] bg-white p-5 shadow-sm lg:sticky lg:top-5 lg:self-start">
                <?php if(!$selectedConsultant): ?>
                    <div class="py-10 text-center">
                        <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-pine-50 text-pine-700">
                            <?php echo $__env->make('perusahaan.partials.icon', ['name' => 'users'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                        </div>
                        <h2 class="mt-4 text-lg font-semibold text-gray-900">Pilih konsultan dulu</h2>
                        <p class="mt-2 text-sm leading-6 text-gray-500">Setelah memilih konsultan, jadwal dan paket yang tersedia akan muncul di sini.</p>
                    </div>
                <?php else: ?>
                    <?php
                        $selectedName = trim(($selectedConsultant->user->firstname ?? '') . ' ' . ($selectedConsultant->user->lastname ?? ''));
                    ?>
                    <div class="border-b border-[#e7e2d9] pb-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-pine-700">Konsultan terpilih</p>
                        <h2 class="mt-1 text-lg font-semibold text-gray-900"><?php echo e($selectedName); ?></h2>
                        <?php if($selectedConsultant->about): ?>
                            <p class="mt-2 line-clamp-3 text-sm leading-6 text-gray-500"><?php echo e($selectedConsultant->about); ?></p>
                        <?php endif; ?>
                    </div>

                    <form method="POST" action="<?php echo e(route('checkout.klinik.pay', $klinik->uuid)); ?>" class="mt-4 space-y-4">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="consultant_id" value="<?php echo e($selectedConsultant->id); ?>">

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Jadwal</label>
                            <select id="booking-schedule" name="schedule_id" required class="w-full rounded-xl border border-[#ddd8cf] px-3 py-2.5 text-sm outline-none focus:border-pine-600 focus:ring-4 focus:ring-pine-100">
                                <option value="">Pilih jadwal</option>
                                <?php $__currentLoopData = $schedules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $schedule): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($schedule->id); ?>" data-mode="<?php echo e($schedule->mode); ?>" <?php if(old('schedule_id') == $schedule->id): echo 'selected'; endif; ?>>
                                        [<?php echo e(strtoupper($schedule->mode ?? 'online')); ?>] <?php echo e($schedule->started_at->translatedFormat('D, d M Y H:i')); ?> WIB
                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Paket</label>
                            <select id="booking-package" name="consultant_package_id" required class="w-full rounded-xl border border-[#ddd8cf] px-3 py-2.5 text-sm outline-none focus:border-pine-600 focus:ring-4 focus:ring-pine-100">
                                <option value="">Pilih paket</option>
                                <?php $__currentLoopData = $packages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $package): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $markup = (int) (optional($markups->get($package->id))->markup_amount ?? 0);
                                        $final = (int) $package->price + $markup;
                                    ?>
                                    <option value="<?php echo e($package->id); ?>" data-mode="<?php echo e($package->mode); ?>" <?php if(old('consultant_package_id') == $package->id): echo 'selected'; endif; ?>>
                                        [<?php echo e(strtoupper($package->mode ?? 'online')); ?>] <?php echo e($package->name); ?> - Rp<?php echo e(number_format($final, 0, ',', '.')); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <p class="mt-1 text-xs text-gray-400">Pilih paket dan jadwal dengan mode yang sama.</p>
                            <p id="booking-mode-hint" class="mt-1 hidden text-xs font-medium text-amber-700">Paket disesuaikan dengan mode jadwal yang dipilih.</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">Nama lengkap</label>
                                <input name="customer_name" value="<?php echo e(old('customer_name')); ?>" required class="w-full rounded-xl border border-[#ddd8cf] px-3 py-2.5 text-sm outline-none focus:border-pine-600 focus:ring-4 focus:ring-pine-100">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" name="customer_email" value="<?php echo e(old('customer_email')); ?>" required class="w-full rounded-xl border border-[#ddd8cf] px-3 py-2.5 text-sm outline-none focus:border-pine-600 focus:ring-4 focus:ring-pine-100">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">No. HP / WA</label>
                                <input name="customer_phone" value="<?php echo e(old('customer_phone')); ?>" required class="w-full rounded-xl border border-[#ddd8cf] px-3 py-2.5 text-sm outline-none focus:border-pine-600 focus:ring-4 focus:ring-pine-100">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">Tanggal lahir</label>
                                <input type="date" name="customer_dob" value="<?php echo e(old('customer_dob')); ?>" required class="w-full rounded-xl border border-[#ddd8cf] px-3 py-2.5 text-sm outline-none focus:border-pine-600 focus:ring-4 focus:ring-pine-100">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">Jenis kelamin</label>
                                <select name="customer_gender" required class="w-full rounded-xl border border-[#ddd8cf] px-3 py-2.5 text-sm outline-none focus:border-pine-600 focus:ring-4 focus:ring-pine-100">
                                    <option value="">Pilih</option>
                                    <option value="male" <?php if(old('customer_gender') === 'male'): echo 'selected'; endif; ?>>Laki-laki</option>
                                    <option value="female" <?php if(old('customer_gender') === 'female'): echo 'selected'; endif; ?>>Perempuan</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Kota/Kabupaten domisili</label>
                            <select name="customer_regency_id" required class="w-full rounded-xl border border-[#ddd8cf] px-3 py-2.5 text-sm outline-none focus:border-pine-600 focus:ring-4 focus:ring-pine-100">
                                <option value="">Pilih domisili</option>
                                <?php $__currentLoopData = $regencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $regency): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($regency->id); ?>" <?php if(old('customer_regency_id') == $regency->id): echo 'selected'; endif; ?>><?php echo e($regency->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Keluhan singkat</label>
                            <textarea name="complaint" rows="3" class="w-full rounded-xl border border-[#ddd8cf] px-3 py-2.5 text-sm outline-none focus:border-pine-600 focus:ring-4 focus:ring-pine-100"><?php echo e(old('complaint')); ?></textarea>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Kode voucher</label>
                            <input name="voucher" value="<?php echo e(old('voucher')); ?>" placeholder="Opsional" class="w-full rounded-xl border border-[#ddd8cf] px-3 py-2.5 text-sm outline-none focus:border-pine-600 focus:ring-4 focus:ring-pine-100">
                        </div>

                        <label class="flex items-start gap-2 rounded-xl bg-pine-50 p-3 text-xs leading-5 text-gray-600">
                            <input type="checkbox" name="consent" value="1" required class="mt-0.5 accent-pine-700">
                            <span>Saya menyetujui pemrosesan data pribadi untuk pemesanan, pembayaran, dan penyelenggaraan sesi konsultasi ini.</span>
                        </label>

                        <button id="booking-submit" class="w-full rounded-xl bg-pine-700 px-5 py-3 text-sm font-semibold text-white hover:bg-pine-800 disabled:cursor-not-allowed disabled:opacity-50" <?php if($schedules->isEmpty() || $packages->isEmpty()): echo 'disabled'; endif; ?>>
                            Lanjut ke Pembayaran
                        </button>
                    </form>
                <?php endif; ?>
            </aside>
        </section>
    </main>
    <?php if($selectedConsultant): ?>
    <script>
        const scheduleSelect = document.getElementById('booking-schedule');
        const packageSelect = document.getElementById('booking-package');
        const submitButton = document.getElementById('booking-submit');
        const modeHint = document.getElementById('booking-mode-hint');

        function selectedMode(select) {
            return select?.selectedOptions?.[0]?.dataset?.mode || '';
        }

        function syncPackageMode() {
            if (!scheduleSelect || !packageSelect) return;

            const scheduleMode = selectedMode(scheduleSelect);
            let firstMatchingPackage = null;
            let selectedPackageStillValid = false;

            [...packageSelect.options].forEach((option) => {
                if (!option.value) return;

                const matches = !scheduleMode || option.dataset.mode === scheduleMode;
                option.disabled = !matches;
                option.hidden = !matches;

                if (matches && !firstMatchingPackage) {
                    firstMatchingPackage = option;
                }
                if (matches && option.selected) {
                    selectedPackageStillValid = true;
                }
            });

            if (scheduleMode && !selectedPackageStillValid) {
                packageSelect.value = firstMatchingPackage?.value || '';
                modeHint?.classList.remove('hidden');
            } else {
                modeHint?.classList.add('hidden');
            }

            const packageMode = selectedMode(packageSelect);
            const valid = Boolean(scheduleSelect.value && packageSelect.value && (!scheduleMode || scheduleMode === packageMode));
            if (submitButton) {
                submitButton.disabled = !valid;
            }
        }

        scheduleSelect?.addEventListener('change', syncPackageMode);
        packageSelect?.addEventListener('change', syncPackageMode);
        syncPackageMode();
    </script>
    <?php endif; ?>
</body>
</html>
<?php /**PATH /home/hermes/workspace/projects/soluusi-b2b-klinik/resources/views/checkout/klinik.blade.php ENDPATH**/ ?>