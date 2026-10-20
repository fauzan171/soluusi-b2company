<?php $__env->startSection('content'); ?>
<a href="<?php echo e(route('klinik.consultants.index')); ?>" class="text-sm text-emerald-700 hover:underline">← Kembali ke daftar konsultan</a>


<div class="bg-white border rounded-lg p-6 mt-3 mb-6">
    <div class="flex flex-col md:flex-row gap-6">
        <div class="w-24 h-24 rounded-full bg-emerald-100 flex items-center justify-center text-3xl font-bold text-emerald-800 shrink-0">
            <?php echo e(substr($consultant->user->firstname ?? '?', 0, 1)); ?>

        </div>
        <div class="flex-1">
            <h1 class="text-2xl font-bold"><?php echo e($consultant->user->full_name); ?></h1>
            <p class="text-sm text-gray-500 mt-1">
                <?php echo e($consultant->user->regency->name ?? 'Lokasi fleksibel'); ?><?php echo e($consultant->user->regency?->province ? ', ' . $consultant->user->regency->province->name : ''); ?>

                · <?php echo e($consultant->experience ?: 0); ?> tahun pengalaman
            </p>
            <div class="flex flex-wrap gap-2 mt-3">
                <?php $__currentLoopData = $consultant->categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <span class="text-xs bg-emerald-50 text-emerald-800 rounded px-2 py-1"><?php echo e($category->name); ?></span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php $__currentLoopData = $consultant->skills->take(6); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $skill): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <span class="text-xs bg-gray-100 text-gray-700 rounded px-2 py-1"><?php echo e($skill->name); ?></span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <p class="text-sm text-gray-700 leading-6 mt-4"><?php echo e($consultant->about ?: 'Belum ada deskripsi profil.'); ?></p>
        </div>
        <div class="md:text-right text-sm text-gray-500 shrink-0">
            <div class="font-semibold text-gray-800 text-lg"><?php echo e($packages->count()); ?></div>
            <div class="text-xs">paket</div>
            <div class="font-semibold text-gray-800 text-lg mt-3"><?php echo e($upcomingSchedules->count()); ?></div>
            <div class="text-xs">slot tersedia</div>
        </div>
    </div>
</div>


<form action="<?php echo e(route('klinik.links.store')); ?>" method="POST" class="space-y-6">
    <?php echo csrf_field(); ?>

    
    <div class="bg-white border rounded-lg p-5">
        <h2 class="font-semibold mb-1">1. Pilih Paket</h2>
        <p class="text-xs text-gray-400 mb-4">Harga sudah termasuk margin klinik (<?php echo e($klinik->brand_name ?? $klinik->name ?? 'brand klinik'); ?>).</p>
        <div class="grid md:grid-cols-2 gap-3">
            <?php $__empty_1 = true; $__currentLoopData = $packages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $package): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $markup = (int) ($markups[$package->id] ?? 0);
                    $final = (int) $package->price + $markup;
                ?>
                <label class="package-option border-2 rounded-lg p-4 cursor-pointer transition-colors hover:border-emerald-400 has-checked:border-emerald-600 has-checked:bg-emerald-50">
                    <input type="radio" name="consultant_package_id" value="<?php echo e($package->id); ?>" class="sr-only" data-mode="<?php echo e($package->mode ?? 'online'); ?>" <?php if($loop->first): echo 'checked'; endif; ?> required>
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="font-semibold text-sm"><?php echo e($package->name); ?></div>
                            <div class="text-xs text-gray-500 mt-0.5"><?php echo e($package->duration); ?> menit · <?php echo e(strtoupper($package->mode ?? 'online')); ?></div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-sm font-bold text-gray-800">Rp<?php echo e(number_format($final, 0, ',', '.')); ?></div>
                            <?php if($markup > 0): ?>
                                <div class="text-[11px] text-gray-400">margin Rp<?php echo e(number_format($markup, 0, ',', '.')); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </label>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-sm text-gray-400">Belum ada paket aktif untuk konsultan ini.</p>
            <?php endif; ?>
        </div>
    </div>

    
    <div class="bg-white border rounded-lg p-5">
        <h2 class="font-semibold mb-1">2. Pilih Jadwal</h2>
        <p class="text-xs text-gray-400 mb-4">Slot yang tampil hanya yang mode-nya sama dengan paket terpilih.</p>
        <div id="schedule-list" class="grid md:grid-cols-3 gap-3">
            <?php $__empty_1 = true; $__currentLoopData = $upcomingSchedules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $schedule): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <label class="schedule-option border-2 rounded-lg p-4 cursor-pointer transition-colors hover:border-emerald-400 has-checked:border-emerald-600 has-checked:bg-emerald-50" data-mode="<?php echo e($schedule->mode ?? 'online'); ?>">
                    <input type="radio" name="schedule_id" value="<?php echo e($schedule->id); ?>" class="sr-only" <?php if(!$loop->first): echo 'disabled'; endif; ?>>
                    <div class="font-semibold text-sm"><?php echo e($schedule->started_at->format('d M Y')); ?></div>
                    <div class="text-xs text-gray-500 mt-0.5"><?php echo e($schedule->started_at->format('H:i')); ?> - <?php echo e($schedule->ended_at->format('H:i')); ?> · <?php echo e(strtoupper($schedule->mode ?? 'online')); ?></div>
                    <?php if(($schedule->mode ?? 'online') === 'offline' && $schedule->location): ?>
                        <div class="text-[11px] text-gray-400 mt-1 truncate"><?php echo e($schedule->location); ?></div>
                    <?php endif; ?>
                </label>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-sm text-gray-400 col-span-3">
                    Belum ada jadwal untuk klinik ini.
                    <a href="<?php echo e(route('klinik.schedules.create', ['consultant_id' => $consultant->id])); ?>" class="text-emerald-700 font-semibold">Atur jadwal dulu →</a>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <button type="submit" id="btn-next" class="bg-emerald-700 text-white rounded px-6 py-3 font-semibold disabled:opacity-40" <?php if($upcomingSchedules->isEmpty()): echo 'disabled'; endif; ?>>
        Generate Link Customer →
    </button>
</form>


<div class="grid lg:grid-cols-3 gap-6 mt-8">
    <div class="bg-white border rounded-lg p-5">
        <h2 class="font-semibold mb-3">Pendidikan</h2>
        <div class="space-y-3">
            <?php $__empty_1 = true; $__currentLoopData = $consultant->consultantEducations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $education): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div>
                    <div class="font-medium text-sm"><?php echo e($education->name ?? 'Riwayat pendidikan'); ?></div>
                    <p class="text-xs text-gray-500"><?php echo e($education->pivot->degree ?? '-'); ?> · <?php echo e($education->pivot->start_date ?? '-'); ?> - <?php echo e($education->pivot->end_date ?? 'Sekarang'); ?></p>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-sm text-gray-400">Belum ada data pendidikan.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="bg-white border rounded-lg p-5">
        <h2 class="font-semibold mb-3">Pengalaman</h2>
        <div class="space-y-3">
            <?php $__empty_1 = true; $__currentLoopData = $consultant->consultantCompanies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $company): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div>
                    <div class="font-medium text-sm"><?php echo e($company->name); ?></div>
                    <p class="text-xs text-gray-500"><?php echo e($company->pivot->position ?? '-'); ?></p>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-sm text-gray-400">Belum ada data pengalaman kerja.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="bg-white border rounded-lg p-5">
        <h2 class="font-semibold mb-3">Sertifikat</h2>
        <div class="space-y-2">
            <?php $__empty_1 = true; $__currentLoopData = $consultant->certificates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $certificate): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="text-sm text-gray-700"><?php echo e($certificate->name); ?> <span class="text-xs text-gray-400">(<?php echo e($certificate->year); ?>)</span></div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-sm text-gray-400">Belum ada data sertifikat.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Filter slot sesuai mode paket terpilih; preselect slot pertama yang cocok.
const radios = document.querySelectorAll('input[name="consultant_package_id"]');
const slots = document.querySelectorAll('.schedule-option');
function syncSlots() {
    const mode = document.querySelector('input[name="consultant_package_id"]:checked')?.dataset.mode;
    let firstVisible = null;
    slots.forEach(slot => {
        const match = slot.dataset.mode === mode;
        slot.style.display = match ? '' : 'none';
        const input = slot.querySelector('input');
        input.disabled = !match;
        input.checked = false;
        if (match && !firstVisible) firstVisible = input;
    });
    if (firstVisible) firstVisible.checked = true;
    document.getElementById('btn-next').disabled = !firstVisible;
}
radios.forEach(r => r.addEventListener('change', syncSlots));
syncSlots();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/hermes/workspace/projects/soluusi-b2b-klinik/resources/views/klinik/consultants/show.blade.php ENDPATH**/ ?>