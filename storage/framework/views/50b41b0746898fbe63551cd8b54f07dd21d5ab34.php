<?php $__env->startSection('content'); ?>
<?php
    $chartMax = max(1, (int) collect($chartKlinik['data'])->max());
    $statusCopy = [
        'waiting' => ['label' => 'Menunggu pembayaran', 'class' => 'bg-amber-50 text-amber-700 border-amber-100'],
        'scheduled' => ['label' => 'Terjadwal', 'class' => 'bg-sky-50 text-sky-700 border-sky-100'],
        'done' => ['label' => 'Selesai', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-100'],
        'canceled' => ['label' => 'Dibatalkan', 'class' => 'bg-rose-50 text-rose-700 border-rose-100'],
    ];
?>

<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-pine-700">Associate Partner</p>
        <h1 class="mt-2 text-2xl font-bold text-gray-950">Dashboard HPA</h1>
        <p class="mt-1 text-sm text-gray-500">Pantau klinik referral, progress stage pervorma, dan status pencairan komisi.</p>
    </div>
    <div class="rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
        <span class="font-semibold">Rule demo:</span> Stage 1 di 5 sesi, Stage 2 di 25 sesi, Stage 3 di 40 sesi.
    </div>
</div>

<div class="grid grid-cols-2 gap-3 md:grid-cols-5">
    <div class="rounded-lg border bg-white p-4">
        <div class="text-2xl font-bold"><?php echo e($totalClinics); ?></div>
        <div class="text-xs text-gray-500">Total klinik</div>
    </div>
    <div class="rounded-lg border bg-white p-4">
        <div class="text-2xl font-bold text-pine-700"><?php echo e($activeClinics); ?></div>
        <div class="text-xs text-gray-500">Klinik aktif</div>
    </div>
    <div class="rounded-lg border bg-white p-4">
        <div class="text-2xl font-bold text-amber-600">Rp<?php echo e(number_format($totalDue, 0, ',', '.')); ?></div>
        <div class="text-xs text-gray-500">Total belum cair</div>
    </div>
    <div class="rounded-lg border bg-white p-4">
        <div class="text-2xl font-bold text-sky-700">Rp<?php echo e(number_format($totalRequested, 0, ',', '.')); ?></div>
        <div class="text-xs text-gray-500">Sedang diajukan</div>
    </div>
    <div class="rounded-lg border bg-white p-4">
        <div class="text-2xl font-bold text-emerald-700">Rp<?php echo e(number_format($totalPaid, 0, ',', '.')); ?></div>
        <div class="text-xs text-gray-500">Sudah diterima</div>
    </div>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
    <section class="rounded-lg border bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-gray-950">Performa sesi per klinik</h2>
                <p class="text-xs text-gray-500">Semua sesi customer dari klinik referral HPA.</p>
            </div>
            <span class="rounded-full bg-pine-50 px-3 py-1 text-xs font-semibold text-pine-700"><?php echo e(collect($chartKlinik['data'])->sum()); ?> sesi</span>
        </div>
        <div class="space-y-3">
            <?php $__empty_1 = true; $__currentLoopData = $chartKlinik['labels']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $value = (int) ($chartKlinik['data'][$i] ?? 0);
                    $width = max(8, round(($value / $chartMax) * 100));
                ?>
                <div>
                    <div class="mb-1 flex items-center justify-between gap-3 text-sm">
                        <span class="truncate font-medium text-gray-800"><?php echo e($label); ?></span>
                        <span class="shrink-0 font-semibold text-gray-950"><?php echo e($value); ?> sesi</span>
                    </div>
                    <div class="h-3 overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-pine-700" style="width: <?php echo e($width); ?>%"></div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="py-8 text-center text-sm text-gray-400">Belum ada sesi dari klinik referral.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="rounded-lg border bg-white p-5">
        <div class="mb-4">
            <h2 class="font-semibold text-gray-950">Status pencairan dana</h2>
            <p class="text-xs text-gray-500">HPA bisa minta pencairan untuk komisi yang sudah due.</p>
        </div>
        <div class="space-y-3">
            <?php $__empty_1 = true; $__currentLoopData = $payoutItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $isPaid = $item->status === 'paid';
                    $isRequested = !$isPaid && filled($item->requested_at);
                    $label = $isPaid ? 'Sudah cair' : ($isRequested ? 'Diproses finance' : 'Siap diajukan');
                    $pill = $isPaid ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : ($isRequested ? 'bg-sky-50 text-sky-700 border-sky-100' : 'bg-amber-50 text-amber-700 border-amber-100');
                ?>
                <div class="border-b pb-3 last:border-b-0 last:pb-0">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-950"><?php echo e($item->klinik->brand_name ?? '-'); ?> · Stage <?php echo e($item->stage); ?></p>
                            <p class="mt-0.5 text-xs text-gray-500">Rp<?php echo e(number_format($item->amount, 0, ',', '.')); ?></p>
                        </div>
                        <span class="shrink-0 rounded-full border px-2.5 py-1 text-[11px] font-semibold <?php echo e($pill); ?>"><?php echo e($label); ?></span>
                    </div>
                    <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-gray-500">
                        <span>Diajukan: <?php echo e(optional($item->requested_at)->format('d M Y') ?? '-'); ?></span>
                        <span>Estimasi cair: <?php echo e(optional($item->due_at)->format('d M Y') ?? '-'); ?></span>
                    </div>
                    <?php if(!$isPaid && !$isRequested): ?>
                        <form method="POST" action="<?php echo e(route('associate.pervormas.request-payout', $item)); ?>" class="mt-3">
                            <?php echo csrf_field(); ?>
                            <button class="inline-flex items-center gap-2 rounded-lg bg-pine-700 px-3 py-2 text-xs font-semibold text-white hover:bg-pine-800">
                                Minta cairkan dana
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="py-8 text-center text-sm text-gray-400">Belum ada komisi pervorma.</p>
            <?php endif; ?>
        </div>
    </section>
</div>

<h2 class="mb-3 mt-8 font-semibold text-gray-950">Klinik referral HPA</h2>
<div class="grid gap-4 xl:grid-cols-2">
    <?php $__empty_1 = true; $__currentLoopData = $kliniks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $klinik): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
            $stats = $klinikStats[$klinik->id] ?? ['completed' => 0, 'stage1' => false, 'stage2' => false, 'stage3' => false, 'due' => 0, 'paid' => 0, 'pervormas' => collect()];
            $progress = min(100, round(($stats['completed'] / 40) * 100));
        ?>
        <article class="rounded-lg border bg-white p-5">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <h3 class="truncate font-semibold text-gray-950"><?php echo e($klinik->brand_name); ?></h3>
                    <p class="text-xs text-gray-500"><?php echo e($klinik->name); ?></p>
                    <p class="mt-1 text-xs text-gray-400">MoU <?php echo e(optional($klinik->mou_at)->format('d M Y') ?? optional($klinik->created_at)->format('d M Y')); ?></p>
                </div>
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold <?php echo e($klinik->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'); ?>">
                    <?php echo e($klinik->is_active ? 'Aktif' : 'Pending'); ?>

                </span>
            </div>

            <div class="mt-4 grid grid-cols-3 gap-3 text-sm">
                <div>
                    <div class="font-bold text-gray-950"><?php echo e($stats['completed']); ?> / 40</div>
                    <div class="text-xs text-gray-500">Sesi selesai</div>
                </div>
                <div>
                    <div class="font-bold text-gray-950"><?php echo e($klinik->checkout_links_count); ?></div>
                    <div class="text-xs text-gray-500">Checkout link</div>
                </div>
                <div>
                    <div class="font-bold text-gray-950"><?php echo e($klinik->split_settlements_count); ?></div>
                    <div class="text-xs text-gray-500">Split ledger</div>
                </div>
            </div>

            <div class="mt-4">
                <div class="mb-2 flex items-center justify-between text-xs text-gray-500">
                    <span>Progress stage</span>
                    <span><?php echo e($progress); ?>%</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                    <div class="h-full rounded-full bg-emerald-600" style="width: <?php echo e($progress); ?>%"></div>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold <?php echo e($stats['stage1'] ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-400'); ?>">Stage 1</span>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold <?php echo e($stats['stage2'] ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-400'); ?>">Stage 2</span>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold <?php echo e($stats['stage3'] ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-400'); ?>">Stage 3</span>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3 border-t pt-4 text-sm">
                <div>
                    <div class="font-bold text-amber-600">Rp<?php echo e(number_format($stats['due'], 0, ',', '.')); ?></div>
                    <div class="text-xs text-gray-500">Belum cair</div>
                </div>
                <div>
                    <div class="font-bold text-emerald-700">Rp<?php echo e(number_format($stats['paid'], 0, ',', '.')); ?></div>
                    <div class="text-xs text-gray-500">Sudah cair</div>
                </div>
            </div>
        </article>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="rounded-lg border bg-white p-8 text-center text-gray-400">
            Anda belum memiliki klinik referral.
        </div>
    <?php endif; ?>
</div>

<h2 class="mb-3 mt-8 font-semibold text-gray-950">Aktivitas terbaru</h2>
<div class="overflow-hidden rounded-lg border bg-white">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b bg-gray-50 text-left text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">
                <th class="p-3">Tanggal</th>
                <th class="p-3">Klinik</th>
                <th class="p-3">Status</th>
                <th class="p-3 text-right">Jumlah sesi</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $recentSessions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php $copy = $statusCopy[$s->status] ?? ['label' => ucfirst(str_replace('_', ' ', $s->status)), 'class' => 'bg-gray-50 text-gray-600 border-gray-100']; ?>
                <tr class="border-b last:border-b-0">
                    <td class="p-3"><?php echo e(\Illuminate\Support\Carbon::parse($s->activity_date)->format('d M Y')); ?></td>
                    <td class="p-3"><?php echo e($s->klinik->brand_name ?? '-'); ?></td>
                    <td class="p-3">
                        <span class="rounded-full border px-2.5 py-1 text-xs font-semibold <?php echo e($copy['class']); ?>"><?php echo e($copy['label']); ?></span>
                    </td>
                    <td class="p-3 text-right font-semibold"><?php echo e($s->session_count); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="4" class="p-4 text-center text-gray-400">Belum ada aktivitas.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/hermes/workspace/projects/soluusi-b2b-klinik/resources/views/associate/dashboard.blade.php ENDPATH**/ ?>