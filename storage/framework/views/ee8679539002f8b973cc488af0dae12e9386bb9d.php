<?php $__env->startSection('title', 'Roster Siswa — ' . $sekolah->brand); ?>

<?php $__env->startSection('content'); ?>
<div x-data="{ tab: 'roster', refer: null }">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-xl font-bold text-pine-900">Roster Siswa</h1>
            <p class="text-sm text-gray-500">Kelola partisipasi program. Data di halaman ini untuk pengelolaan — konten sesi tetap rahasia.</p>
        </div>
        <button @click="tab = 'tambah'" class="bg-pine-700 text-white text-sm px-4 py-2 rounded-xl">+ Tambah Siswa</button>
    </div>

    <?php if(session('success')): ?><div class="mb-4 p-3 rounded-xl bg-pine-100 text-pine-800 text-sm"><?php echo e(session('success')); ?></div><?php endif; ?>

    
    <div x-show="refer" x-cloak class="portal-card p-5 mb-4" x-data="{
        form: { student_id: '', konsultan_type: 'psikolog', mode: 'online', scheduled_at: '' },
        init() { this.$watch('refer', v => { if (v) this.form.student_id = v.id }) }
    }">
        <h3 class="dash-section-title">Refer konseling — <span x-text="refer ? refer.name : ''"></span></h3>
        <form method="POST" action="<?php echo e(route('sekolah.students.refer')); ?>" class="grid md:grid-cols-4 gap-3 mt-3">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="student_id" :value="form.student_id">
            <select name="konsultan_type" class="border border-[#e7e2d9] rounded-xl px-3 py-2 text-sm" x-model="form.konsultan_type">
                <option value="psikolog">Psikolog Anak & Remaja</option>
                <option value="karir">Konselor Karir</option>
            </select>
            <label class="text-sm">Slot konsultan (WIB)
                <select name="schedule_id" required class="w-full border rounded-xl px-3 py-2 text-sm">
                    <option value="">— Pilih slot sesuai jenis konsultan —</option>
                    <?php $__currentLoopData = $slots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $slot): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($slot->id); ?>"><?php echo e($slot->started_at->translatedFormat('d M Y H:i')); ?> · <?php echo e($slot->consultant->user->firstname); ?> · <?php echo e($slot->mode); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['schedule_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </label>
            <button class="bg-pine-700 text-white text-sm px-4 py-2 rounded-xl">Jadwalkan</button>
        </form>
    </div>

    <section class="portal-card p-5">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-500 border-b border-[#e7e2d9]">
                        <th class="py-2">Nama</th><th>Kelas</th><th>Email</th>
                        <th>Consent ortu</th><th>Akun</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="border-b border-[#e7e2d9]/60">
                        <td class="py-2 font-medium"><?php echo e($s->name); ?></td>
                        <td><?php echo e($s->kelas); ?></td>
                        <td class="text-gray-500"><?php echo e($s->email ?? '—'); ?></td>
                        <td>
                            <?php if($s->parent_consent_at): ?>
                            <span class="text-pine-700 text-xs font-semibold">✓ disetujui</span>
                            <?php else: ?>
                            <span class="text-terra-700 text-xs font-semibold">belum</span>
                            <?php endif; ?>
                            <details><summary>Catat/koreksi izin wali eksternal</summary>
                                <form method="POST" action="<?php echo e(route('sekolah.students.consent', $s)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <label>Status <select name="granted"><option value="1">Setuju</option><option value="0">Cabut</option></select></label>
                                    <label>Referensi bukti <input name="evidence" required maxlength="2000"></label>
                                    <label>Waktu persetujuan/perubahan <input type="datetime-local" name="agreed_at" required></label>
                                    <button>Simpan jejak consent</button>
                                </form>
                            </details>
                        </td>
                        <td>
                            <?php if($s->user_id): ?>
                            <span class="text-pine-700 text-xs">aktif</span>
                            <?php else: ?>
                            <form method="POST" action="<?php echo e(route('sekolah.students.invite')); ?>"><?php echo csrf_field(); ?>
                                <input type="hidden" name="student_id" value="<?php echo e($s->id); ?>">
                                <button class="text-xs text-terra-700 hover:underline" <?php if (! ($s->email)): ?> disabled <?php endif; ?>>undang</button>
                            </form>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($s->parent_consent_at): ?>
                            <button @click="refer = { id: <?php echo \Illuminate\Support\Js::from($s->id)->toHtml() ?>, name: <?php echo \Illuminate\Support\Js::from($s->name)->toHtml() ?> }" class="text-xs bg-pine-50 text-pine-700 px-3 py-1 rounded-lg border border-pine-200">Refer</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="py-6 text-center text-gray-500">Belum ada siswa. Tambah lewat form.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    
    <div x-show="tab === 'tambah'" x-cloak class="fixed inset-0 bg-black/40 z-50 grid place-items-center" @keydown.escape.window="tab = 'roster'">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md" @click.outside="tab = 'roster'">
            <h3 class="dash-section-title">Tambah siswa ke roster</h3>
            <form method="POST" action="<?php echo e(route('sekolah.students.store')); ?>" class="space-y-3 mt-4">
                <?php echo csrf_field(); ?>
                <input type="text" name="name" placeholder="Nama lengkap siswa" class="w-full border border-[#e7e2d9] rounded-xl px-3 py-2 text-sm" required>
                <input type="email" name="email" placeholder="Email siswa (opsional, untuk undangan akun)" class="w-full border border-[#e7e2d9] rounded-xl px-3 py-2 text-sm">
                <input type="text" name="kelas" placeholder="Kelas, mis. X IPA 2" class="w-full border border-[#e7e2d9] rounded-xl px-3 py-2 text-sm" required>
                <input type="text" name="parent_name" placeholder="Nama orang tua/wali" class="w-full border border-[#e7e2d9] rounded-xl px-3 py-2 text-sm" required>
                <div class="flex gap-2 justify-end">
                    <button type="button" @click="tab = 'roster'" class="text-sm px-4 py-2 rounded-xl border border-[#e7e2d9]">Batal</button>
                    <button class="bg-pine-700 text-white text-sm px-4 py-2 rounded-xl">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/hermes/workspace/projects/soluusi-b2b-klinik/resources/views/sekolah/roster.blade.php ENDPATH**/ ?>