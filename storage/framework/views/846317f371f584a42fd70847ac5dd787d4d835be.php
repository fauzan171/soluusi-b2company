<?php $__env->startSection('content'); ?>
<div class="mb-5">
    <a href="<?php echo e(route('klinik.consultants.show', $consultant)); ?>" class="text-sm text-emerald-700 hover:underline">← Kembali ke profil konsultan</a>
    <h1 class="text-xl font-bold mt-2">Atur Jadwal Konsultan</h1>
    <p class="text-sm text-gray-500">Konsultan: <?php echo e(trim(($consultant->user->firstname ?? '') . ' ' . ($consultant->user->lastname ?? ''))); ?> · Klik tanggal di kalender untuk memilih hari slot.</p>
</div>

<div class="grid lg:grid-cols-5 gap-6">
    
    <div class="lg:col-span-3 bg-white border rounded-lg p-4">
        <div id="calendar"></div>
    </div>

    
    <form method="POST" action="<?php echo e(route('klinik.schedules.store')); ?>" class="lg:col-span-2 bg-white border rounded-lg p-6 space-y-4 self-start">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="consultant_id" value="<?php echo e($consultant->id); ?>">

        <div>
            <label class="block text-sm mb-1">Tanggal</label>
            <input type="date" name="date" value="<?php echo e(old('date')); ?>" required class="w-full border rounded px-3 py-2" min="<?php echo e(now()->toDateString()); ?>">
            <p class="text-[11px] text-gray-400 mt-1">Pilih langsung dari kalender atau ketik manual.</p>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm mb-1">Jam mulai</label>
                <input type="time" name="time_start" value="<?php echo e(old('time_start', '09:00')); ?>" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm mb-1">Jam selesai</label>
                <input type="time" name="time_end" value="<?php echo e(old('time_end', '10:00')); ?>" required class="w-full border rounded px-3 py-2">
            </div>
        </div>

        <div>
            <label class="block text-sm mb-1">Mode layanan</label>
            <select name="mode" required class="w-full border rounded px-3 py-2">
                <option value="online" <?php if(old('mode', 'online') === 'online'): echo 'selected'; endif; ?>>Online</option>
                <option value="offline" <?php if(old('mode') === 'offline'): echo 'selected'; endif; ?>>Offline</option>
            </select>
        </div>

        <div id="field-location" class="hidden">
            <label class="block text-sm mb-1">Lokasi (untuk offline)</label>
            <input type="text" name="location" value="<?php echo e(old('location')); ?>" placeholder="Alamat / nama klinik / room" class="w-full border rounded px-3 py-2">
        </div>

        <div>
            <label class="block text-sm mb-1">Catatan internal</label>
            <textarea name="notes" rows="2" class="w-full border rounded px-3 py-2" placeholder="Instruksi tambahan untuk penjadwalan"><?php echo e(old('notes')); ?></textarea>
        </div>

        <?php $__errorArgs = ['started_at'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-xs text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        <?php $__errorArgs = ['ended_at'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-xs text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

        <button class="bg-emerald-700 text-white rounded px-4 py-2 font-semibold w-full">Simpan Jadwal</button>
    </form>
</div>


<script>
// Date/time composition belongs to server; logout form remains untouched.

// Toggle lokasi sesuai mode. Link meeting dibuat setelah booking/payment.
const modeSelect = document.querySelector('[name=mode]');
function toggleModeFields() {
    document.getElementById('field-location').classList.toggle('hidden', modeSelect.value !== 'offline');
}
modeSelect.addEventListener('change', toggleModeFields);
toggleModeFields();
</script>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        height: 'auto',
        locale: 'id',
        buttonText: { today: 'Hari ini' },
        headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
        events: [
            <?php $__currentLoopData = $existingSchedules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                {
                    title: '<?php echo e($s->started_at->format('H:i')); ?> <?php echo e(strtoupper($s->mode)); ?>',
                    start: '<?php echo e($s->started_at->toDateTimeString()); ?>',
                    end: '<?php echo e($s->ended_at->toDateTimeString()); ?>',
                    backgroundColor: '<?php echo e(($s->mode ?? 'online') === 'offline' ? '#f59e0b' : '#0d7a5f'); ?>',
                    display: 'block',
                },
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        ],
        dateClick: function (info) {
            // Tidak boleh pilih tanggal lampau
            if (info.dateStr < new Date().toISOString().slice(0, 10)) return;
            document.querySelector('input[name=date]').value = info.dateStr;
        },
        dayCellDidMount: function (arg) {
            arg.el.style.cursor = 'pointer';
        },
    });
    calendar.render();
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/hermes/workspace/projects/soluusi-b2b-klinik/resources/views/klinik/schedules/create.blade.php ENDPATH**/ ?>