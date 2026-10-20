<?php $__env->startSection('content'); ?>
<?php
    $checkpointPct = min(100, round($completedCount / max(1, $stageTarget) * 100));
    $toneClasses = [
        'pine' => ['chip' => 'bg-pine-100 text-pine-800', 'icon' => 'bg-pine-50 text-pine-700', 'text' => 'text-pine-700'],
        'terra' => ['chip' => 'bg-terra-100 text-terra-800', 'icon' => 'bg-terra-50 text-terra-600', 'text' => 'text-terra-700'],
        'red' => ['chip' => 'bg-red-100 text-red-800', 'icon' => 'bg-red-50 text-red-600', 'text' => 'text-red-700'],
        'slate' => ['chip' => 'bg-gray-100 text-gray-700', 'icon' => 'bg-gray-100 text-gray-600', 'text' => 'text-gray-900'],
    ];
    $statCards = [
        ['label' => 'Total penjualan', 'value' => 'Rp' . number_format($grossSales, 0, ',', '.'), 'icon' => 'receipt', 'tone' => 'slate', 'detail' => 'gross sebelum potongan'],
        ['label' => 'Sesi terjadwal', 'value' => $scheduledCount, 'icon' => 'calendar', 'tone' => 'terra', 'detail' => 'menunggu pelaksanaan'],
        ['label' => 'Pendapatan bersih', 'value' => 'Rp' . number_format($marginSettled, 0, ',', '.'), 'icon' => 'check', 'tone' => 'pine', 'detail' => 'sudah settled'],
        ['label' => 'Menunggu payout', 'value' => 'Rp' . number_format($marginPending, 0, ',', '.'), 'icon' => 'arrow-trending-up', 'tone' => 'terra', 'detail' => 'margin belum cair'],
    ];
?>

<style>
    html { scroll-behavior: smooth; }
    .dash-card {
        border: 1px solid #e7e2d9;
        background: #fff;
        border-radius: 1.25rem;
        box-shadow: 0 1px 2px rgba(12, 59, 46, 0.04), 0 12px 28px rgba(12, 59, 46, 0.04);
    }
    .dash-section-title { font-size: .95rem; font-weight: 600; color: #1a2420; }
    .dash-section-subtitle { margin-top: .25rem; font-size: .75rem; color: #6b7280; }
    .panel-surface {
        border-radius: 1rem;
        border: 1px solid #e7e2d9;
        background: linear-gradient(180deg, #fdfcfa, #f7f4ee);
        padding: 1rem;
    }
    .hero-band {
        border-radius: 1.25rem;
        background: linear-gradient(135deg, #062a20 0%, #0c3b2e 45%, #007648 100%);
        color: white;
        overflow: hidden;
        position: relative;
    }
    .hero-band::after {
        content: "";
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at top right, rgba(255,255,255,0.14), transparent 24%),
            linear-gradient(90deg, transparent, rgba(255,255,255,0.03));
        pointer-events: none;
    }
    .hero-metric {
        border-radius: 1rem;
        border: 1px solid rgba(255,255,255,0.12);
        background: rgba(255,255,255,0.08);
        padding: 1rem;
        position: relative;
        z-index: 1;
    }
    .chart-wrap { position: relative; height: 190px; }
    .chart-wrap--donut { position: relative; height: 190px; }
    .table-dashed th { font-size: .68rem; text-transform: uppercase; letter-spacing: .08em; color: #94a3b8; font-weight: 600; padding: .75rem 1rem; text-align: left; }
    .table-dashed td { padding: .8rem 1rem; border-top: 1px dashed #e7e2d9; font-size: .84rem; color: #334155; vertical-align: middle; }
    .fade-up { animation: fadeUp .55s ease both; }
    .fade-up:nth-child(2) { animation-delay: .04s; }
    .fade-up:nth-child(3) { animation-delay: .08s; }
    .fade-up:nth-child(4) { animation-delay: .12s; }
    .fade-up:nth-child(5) { animation-delay: .16s; }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="space-y-4">

    
    <?php if($isSuperAdmin): ?>
    <section class="fade-up dash-card p-5 border-indigo-200" style="border-color:#c7d2fe;">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-2xl bg-indigo-50 text-indigo-700">
                    <?php echo $__env->make('perusahaan.partials.icon', ['name' => 'shield'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </span>
                <div>
                    <h3 class="dash-section-title text-indigo-900">Mode Super Admin — kelola mitra</h3>
                    <p class="dash-section-subtitle">Info internal Soluusi; tidak pernah terlihat oleh klinik.</p>
                </div>
            </div>
            <div class="flex gap-2 text-xs">
                <a href="<?php echo e(route('admin.kliniks.packages.index', $klinik)); ?>" class="rounded-full bg-indigo-700 px-3 py-1.5 font-semibold text-white hover:bg-indigo-800">Kelola paket</a>
                <a href="<?php echo e(route('admin.kliniks.markups.index', $klinik)); ?>" class="rounded-full border border-indigo-200 px-3 py-1.5 font-semibold text-indigo-700 hover:bg-indigo-50">Atur markup</a>
                <a href="<?php echo e(route('admin.referral-overview')); ?>" class="rounded-full border border-indigo-200 px-3 py-1.5 font-semibold text-indigo-700 hover:bg-indigo-50">Referral HPA</a>
            </div>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-5">
            <div class="panel-surface bg-white">
                <p class="text-[11px] uppercase tracking-[0.14em] text-gray-400">HPA</p>
                <p class="mt-1.5 text-sm font-semibold text-gray-900"><?php echo e($klinik->associate ? trim($klinik->associate->firstname . ' ' . $klinik->associate->lastname) : '— belum ditugaskan'); ?></p>
            </div>
            <div class="panel-surface bg-white">
                <p class="text-[11px] uppercase tracking-[0.14em] text-gray-400">MoU</p>
                <p class="mt-1.5 text-sm font-semibold text-gray-900"><?php echo e($klinik->mou_at ? $klinik->mou_at->format('d M Y') : '— belum ada MoU'); ?></p>
            </div>
            <div class="panel-surface bg-white">
                <p class="text-[11px] uppercase tracking-[0.14em] text-gray-400">Rate total pervorma</p>
                <p class="mt-1.5 text-sm font-semibold text-gray-900">Rp<?php echo e(number_format($klinik->total_rate, 0, ',', '.')); ?></p>
            </div>
            <div class="panel-surface bg-white">
                <p class="text-[11px] uppercase tracking-[0.14em] text-gray-400">Status</p>
                <?php if($klinik->is_active): ?>
                    <p class="mt-1.5"><span class="rounded-full bg-pine-100 px-3 py-1 text-xs font-semibold text-pine-800">Aktif</span></p>
                <?php else: ?>
                    <form method="POST" action="<?php echo e(route('admin.kliniks.approve', $klinik)); ?>">
                        <?php echo csrf_field(); ?>
                        <button class="mt-1 rounded-full bg-pine-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-pine-800">Approve mitra</button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="panel-surface bg-white">
                <p class="text-[11px] uppercase tracking-[0.14em] text-gray-400">Checkout link</p>
                <p class="mt-1.5 text-sm font-semibold text-gray-900">
                    <?php if($linkStats): ?>
                        <?php echo e($linkStats['aktif']); ?> aktif · <?php echo e($linkStats['terpakai']); ?> pakai · <?php echo e($linkStats['kadaluarsa']); ?> expired
                    <?php else: ?> — <?php endif; ?>
                </p>
            </div>
        </div>

        <div class="mt-4 grid gap-3 xl:grid-cols-2">
            <div class="panel-surface bg-white">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-indigo-900">Komisi pervorma HPA</p>
                    <span class="rounded-full bg-indigo-50 px-3 py-1 text-[11px] font-semibold text-indigo-700"><?php echo e($pervormas->count()); ?> tahap</span>
                </div>
                <?php if($pervormas->isEmpty()): ?>
                    <p class="mt-3 text-xs text-gray-400">Belum ada pervorma terpicu. Tahap 1 terpicu saat MoU + 5 sesi terverifikasi dalam 90 hari.</p>
                <?php else: ?>
                    <div class="mt-3 overflow-x-auto">
                        <table class="table-dashed w-full">
                            <thead><tr><th>Tahap</th><th>Nominal</th><th>Status</th><th>Dibayar</th></tr></thead>
                            <tbody>
                                <?php $__currentLoopData = $pervormas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td class="font-semibold text-gray-900">Tahap <?php echo e($p->stage); ?></td>
                                    <td>Rp<?php echo e(number_format($p->amount, 0, ',', '.')); ?></td>
                                    <td>
                                        <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold <?php echo e($p->status === 'paid' ? 'bg-pine-100 text-pine-800' : 'bg-amber-100 text-amber-800'); ?>"><?php echo e($p->status === 'paid' ? 'Dibayar' : 'Jatuh tempo'); ?></span>
                                    </td>
                                    <td><?php echo e($p->paid_at ? $p->paid_at->format('d M Y') : '—'); ?></td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="panel-surface bg-white">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-indigo-900">Markup rules</p>
                    <span class="rounded-full bg-indigo-50 px-3 py-1 text-[11px] font-semibold text-indigo-700"><?php echo e($markupRules->count()); ?> paket</span>
                </div>
                <?php if($markupRules->isEmpty()): ?>
                    <p class="mt-3 text-xs text-gray-400">Belum ada markup rule untuk klinik ini.</p>
                <?php else: ?>
                    <div class="mt-3 overflow-x-auto">
                        <table class="table-dashed w-full">
                            <thead><tr><th>Paket</th><th>Netto</th><th>Markup</th><th>Final</th></tr></thead>
                            <tbody>
                                <?php $__currentLoopData = $markupRules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td class="font-medium text-gray-900"><?php echo e($r->package->name ?? 'paket #' . $r->consultant_package_id); ?></td>
                                    <td>Rp<?php echo e(number_format($r->package->price ?? 0, 0, ',', '.')); ?></td>
                                    <td>Rp<?php echo e(number_format($r->markup_amount, 0, ',', '.')); ?></td>
                                    <td class="font-semibold text-gray-900">Rp<?php echo e(number_format(($r->package->price ?? 0) + $r->markup_amount, 0, ',', '.')); ?></td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    
    <section class="fade-up dash-card p-4 md:p-5">
        <div class="hero-band p-5 md:p-6">
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1.15fr),minmax(300px,0.85fr)]">
                <div class="relative z-10">
                    <div class="flex flex-wrap items-center gap-2 text-[11px]">
                        <span class="rounded-full bg-white/12 px-3 py-1 font-semibold uppercase tracking-[0.18em] text-white">Dashboard Mitra</span>
                        <span class="rounded-full bg-white/10 px-3 py-1 font-medium text-pine-50"><?php echo e($klinik->brand_name); ?></span>
                    </div>
                    <h2 class="mt-3 max-w-2xl text-[1.6rem] md:text-[1.8rem] font-semibold tracking-tight text-white">Checkpoint <?php echo e($stageTarget); ?> Konsultasi</h2>
                    <p class="mt-2 max-w-2xl text-[13px] leading-6 text-pine-50/85">Sesi terselesaikan &amp; terverifikasi sejak MoU, dengan target aktivasi 90 hari. Data agregat — konten sesi &amp; identitas customer tidak ditampilkan.</p>

                    <div class="mt-5">
                        <div class="flex items-baseline justify-between text-sm">
                            <span class="text-2xl font-semibold text-white"><?php echo e($completedCount); ?><span class="text-base font-normal text-pine-100/70"> / <?php echo e($stageTarget); ?> sesi</span></span>
                            <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-pine-50"><?php echo e($checkpointPct); ?>%</span>
                        </div>
                        <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-white/15">
                            <div class="h-full rounded-full bg-gradient-to-r from-terra-400 via-pine-300 to-pine-200" style="width: <?php echo e($checkpointPct); ?>%"></div>
                        </div>
                        <p class="mt-2 text-[11px] text-pine-50/70">Sisa <?php echo e(max(0, $stageTarget - $completedCount)); ?> sesi menuju checkpoint berikutnya.</p>
                    </div>
                </div>

                <div class="relative z-10 grid content-start gap-3">
                    <div class="hero-metric">
                        <div class="text-[11px] uppercase tracking-[0.16em] text-pine-100/80">Total penjualan</div>
                        <div class="mt-2 text-2xl font-semibold">Rp<?php echo e(number_format($grossSales, 0, ',', '.')); ?></div>
                        <div class="mt-1 text-[13px] text-pine-50/85">gross payment customer terverifikasi sebelum potongan</div>
                    </div>
                    <div class="hero-metric">
                        <div class="text-[11px] uppercase tracking-[0.16em] text-pine-100/80">Total pendapatan bersih</div>
                        <div class="mt-2 text-2xl font-semibold">Rp<?php echo e(number_format($marginSettled, 0, ',', '.')); ?></div>
                        <div class="mt-1 text-[13px] text-pine-50/85">margin klinik yang sudah settled</div>
                    </div>
                    <div class="hero-metric">
                        <div class="text-[11px] uppercase tracking-[0.16em] text-pine-100/80">Pendapatan menunggu payout</div>
                        <div class="mt-2 text-2xl font-semibold">Rp<?php echo e(number_format($marginPending, 0, ',', '.')); ?></div>
                        <div class="mt-1 text-[13px] text-pine-50/85">payment masuk dulu ke sistem, lalu diproses payout</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    
    <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <?php $__currentLoopData = $statCards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php $tone = $toneClasses[$card['tone']]; ?>
            <div class="fade-up dash-card p-4">
                <div class="flex items-start justify-between gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-2xl <?php echo e($tone['icon']); ?>">
                        <?php echo $__env->make('perusahaan.partials.icon', ['name' => $card['icon']], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    </span>
                    <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold <?php echo e($tone['chip']); ?>"><?php echo e($card['detail']); ?></span>
                </div>
                <div class="mt-5 text-2xl font-semibold tracking-tight text-gray-900"><?php echo e($card['value']); ?></div>
                <p class="mt-1 text-[13px] text-gray-500"><?php echo e($card['label']); ?></p>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </section>

    
    <section class="grid gap-3 lg:grid-cols-2">
        <div class="fade-up dash-card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="dash-section-title">Sesi selesai per minggu</h3>
                    <p class="dash-section-subtitle">Rolling 12 minggu terakhir</p>
                </div>
                <span class="rounded-full bg-pine-50 px-3 py-1 text-xs font-semibold text-pine-700"><?php echo e(collect($chartWeekly['data'])->sum()); ?> sesi</span>
            </div>
            <div class="chart-wrap mt-4"><canvas id="chartWeekly"></canvas></div>
        </div>
        <div class="fade-up dash-card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="dash-section-title">Status sesi</h3>
                    <p class="dash-section-subtitle">Distribusi seluruh sesi</p>
                </div>
                <span class="rounded-full bg-pine-50 px-3 py-1 text-xs font-semibold text-pine-700"><?php echo e(collect($chartStatus['data'])->sum()); ?> sesi</span>
            </div>
            <div class="chart-wrap--donut mt-4"><canvas id="chartStatus"></canvas></div>
        </div>
    </section>

    
    <section class="fade-up dash-card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="dash-section-title">Paket kamu</h3>
                <p class="dash-section-subtitle">Paket disusun bersama tim Soluusi sesuai kebutuhan &amp; budget klinik. Hubungi kami untuk mengubah.</p>
            </div>
            <?php if($isSuperAdmin): ?>
                <a href="<?php echo e(route('admin.kliniks.packages.index', $klinik)); ?>" class="rounded-full bg-pine-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-pine-800">Kelola paket</a>
            <?php endif; ?>
        </div>
        <?php if($klinikPackages->count()): ?>
        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            <?php $__currentLoopData = $klinikPackages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="panel-surface bg-white">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2.5">
                        <span class="grid h-9 w-9 place-items-center rounded-2xl bg-pine-50 text-pine-700">
                            <?php echo $__env->make('perusahaan.partials.icon', ['name' => 'heart'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                        </span>
                        <h4 class="text-sm font-semibold text-gray-900"><?php echo e($p->name); ?></h4>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold <?php echo e($p->mode === 'offline' ? 'bg-amber-100 text-amber-800' : 'bg-pine-100 text-pine-800'); ?>"><?php echo e(ucfirst($p->mode)); ?></span>
                </div>
                <?php if($p->description): ?><p class="mt-2 text-xs leading-6 text-gray-500"><?php echo e($p->description); ?></p><?php endif; ?>
                <div class="mt-3 flex items-baseline justify-between border-t border-dashed border-[#e7e2d9] pt-3">
                    <span class="text-base font-bold text-pine-700">Rp<?php echo e(number_format($p->price, 0, ',', '.')); ?></span>
                    <span class="text-[11px] text-gray-400"><?php echo e($p->duration_minutes); ?> menit</span>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php else: ?>
        <p class="mt-4 rounded-3xl bg-gray-50 px-4 py-8 text-center text-sm text-gray-400">Belum ada paket untuk klinik kamu.</p>
        <?php endif; ?>
    </section>

    
    <section class="grid gap-3 xl:grid-cols-[minmax(0,1.1fr),minmax(0,0.9fr)]">
        <div class="fade-up dash-card p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="dash-section-title">Konsultan di daerah kamu</h3>
                    <p class="dash-section-subtitle">Konsultan terverifikasi dengan slot aktif</p>
                </div>
                <a href="<?php echo e(route('klinik.consultants.index')); ?>" class="rounded-full bg-pine-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-pine-800">Lihat semua</a>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <?php $__empty_1 = true; $__currentLoopData = $nearbyConsultants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="panel-surface bg-white flex flex-col">
                    <div class="flex items-start justify-between">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-900"><?php echo e(trim(($c->user->firstname ?? '') . ' ' . ($c->user->lastname ?? ''))); ?></h4>
                            <p class="mt-0.5 text-xs text-gray-500"><?php echo e($c->user->regency->name ?? 'Lokasi fleksibel'); ?></p>
                        </div>
                        <?php if($c->schedules_count > 0): ?>
                            <span class="rounded-full bg-pine-100 px-2.5 py-1 text-[10px] font-semibold text-pine-800"><?php echo e($c->schedules_count); ?> slot</span>
                        <?php endif; ?>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-1">
                        <?php $__currentLoopData = $c->categories->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-600"><?php echo e($cat->name); ?></span>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <div class="mt-auto pt-3">
                        <a href="<?php echo e(route('klinik.consultants.show', $c)); ?>" class="inline-block rounded-full border border-pine-200 px-3 py-1.5 text-xs font-semibold text-pine-700 hover:bg-pine-50">Lihat profil</a>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="sm:col-span-2 rounded-3xl bg-gray-50 px-4 py-8 text-center text-sm text-gray-400">
                    Belum ada konsultan di daerah kamu. <a href="<?php echo e(route('klinik.consultants.index', ['semua_daerah' => 1])); ?>" class="font-semibold text-pine-700">Lihat konsultan semua daerah</a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="fade-up dash-card p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="dash-section-title">Aktivitas terbaru</h3>
                    <p class="dash-section-subtitle">Order terakhir tanpa identitas customer</p>
                </div>
                <a href="<?php echo e(route('klinik.links.index')); ?>" class="rounded-full border border-pine-200 px-3 py-1.5 text-xs font-semibold text-pine-700 hover:bg-pine-50">Checkout link</a>
            </div>
            <div class="mt-3 overflow-x-auto">
                <table class="table-dashed w-full">
                    <thead><tr><th>Waktu</th><th>Konsultan</th><th>Bayar</th><th>Status</th><th class="text-right">Margin</th></tr></thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $recentSessions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="whitespace-nowrap"><?php echo e($s->order_at->format('d M H:i')); ?></td>
                            <td>
                                <span class="font-medium text-gray-900"><?php echo e(trim(($s->consultant->user->firstname ?? '') . ' ' . ($s->consultant->user->lastname ?? ''))); ?></span>
                                <span class="block text-[11px] text-gray-400"><?php echo e($s->consultantPackage->name ?? '-'); ?></span>
                            </td>
                            <td>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold <?php echo e($s->payment_status === 'verified' ? 'bg-pine-100 text-pine-800' : ($s->payment_status === 'waiting' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-500')); ?>"><?php echo e(ucfirst($s->payment_status)); ?></span>
                            </td>
                            <td>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold <?php echo e($s->status === 'done' ? 'bg-pine-100 text-pine-800' : 'bg-gray-100 text-gray-500'); ?>"><?php echo e(ucfirst(str_replace('_', ' ', $s->status))); ?></span>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <?php if($s->splitSettlement): ?>
                                    <span class="font-semibold text-pine-700">Rp<?php echo e(number_format($s->splitSettlement->klinik_margin, 0, ',', '.')); ?></span>
                                <?php else: ?>
                                    <span class="text-gray-300">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="5" class="text-center text-gray-400">Belum ada aktivitas. Buat checkout link pertama kamu.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    
    <section class="fade-up flex flex-wrap gap-3">
        <a href="<?php echo e(route('checkout.klinik', $klinik->uuid)); ?>" target="_blank" class="rounded-full bg-terra-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-terra-600">Buka Link Customer</a>
        <a href="<?php echo e(route('klinik.consultants.index')); ?>" class="rounded-full bg-pine-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-pine-800">Pilih Konsultan</a>
        <a href="<?php echo e(route('klinik.schedules.index')); ?>" class="rounded-full border border-[#e7e2d9] bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:border-pine-300">Kelola Jadwal</a>
        <a href="<?php echo e(route('klinik.links.index')); ?>" class="rounded-full border border-pine-200 bg-pine-50 px-5 py-2.5 text-sm font-semibold text-pine-700 hover:bg-pine-100">Kelola Checkout Link</a>
    </section>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
const chartFont = { family: 'Instrument Sans, sans-serif', size: 11 };
const gridColor = 'rgba(148, 163, 184, 0.18)';
const axisColor = '#64748b';
const PINE = '#007648', PINE_LIGHT = '#1e8a5e', PINE_SOFT = '#4aa37c', TERRA = '#e2703a', RED = '#dc2626';

const weekly = <?php echo json_encode($chartWeekly, 15, 512) ?>;
new Chart(document.getElementById('chartWeekly'), {
    type: 'bar',
    data: {
        labels: weekly.labels,
        datasets: [{
            data: weekly.data,
            backgroundColor: weekly.data.map((v, i) => i === weekly.data.length - 1 ? PINE : PINE_LIGHT),
            borderRadius: 999,
            borderSkipped: false,
            maxBarThickness: 18,
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color: axisColor, font: chartFont, maxRotation: 45 }, grid: { display: false } },
            y: { beginAtZero: true, ticks: { color: axisColor, font: chartFont, precision: 0 }, grid: { color: gridColor } }
        }
    }
});

const st = <?php echo json_encode($chartStatus, 15, 512) ?>;
new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
        labels: st.labels,
        datasets: [{
            data: st.data,
            backgroundColor: [PINE, PINE_SOFT, TERRA, RED, '#94a3b8'],
            borderWidth: 0,
            cutout: '62%',
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 10, color: axisColor, font: chartFont } } }
    }
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/hermes/workspace/projects/soluusi-b2b-klinik/resources/views/klinik/dashboard.blade.php ENDPATH**/ ?>