<?php

namespace App\Services;

use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\ConsultationSession;
use App\Models\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public static function available(string $vertical, int $tenantId)
    {
        $ids = collect(ConsultantPool::poolFor($vertical, $tenantId))->pluck('id');
        return Schedule::where($vertical.'_id', $tenantId)->whereNull('klinik_id')
            ->whereIn('consultant_id', $ids)->where('status', Schedule::STATUS_CONFIRMED)
            ->where('started_at', '>', now())->whereNotNull('ended_at')
            ->whereNotIn('id', DB::table('booking_claims')->select('schedule_id'))
            ->with('consultant.user')->orderBy('started_at')->get()
            ->filter(fn ($slot) => !self::blocked($slot));
    }

    public static function blocked(Schedule $slot, ?int $exceptLink = null): bool
    {
        $ids = Schedule::where('consultant_id', $slot->consultant_id)
            ->where('started_at', '<', $slot->ended_at)->where('ended_at', '>', $slot->started_at)->pluck('id');
        if (ConsultationSession::whereIn('schedule_id', $ids)->blocking()->exists()
            || DB::table('booking_claims')->whereIn('schedule_id', $ids)->exists()
            || \App\Models\Klinik\CheckoutLink::whereIn('schedule_id', $ids)->whereNull('used_at')
                ->where('expires_at', '>', now())->when($exceptLink, fn ($q) => $q->where('id', '!=', $exceptLink))->exists()) {
            return true;
        }
        foreach ([\App\Models\Perusahaan\PerusahaanSession::class, \App\Models\Sekolah\SekolahSession::class] as $model) {
            if ($model::where('consultant_id', $slot->consultant_id)->whereIn('status', ['scheduled', 'done'])
                ->where('scheduled_at', '<', $slot->ended_at)
                ->where(fn ($q) => $q->where('ended_at', '>', $slot->started_at)
                    ->orWhere(fn ($q2) => $q2->whereNull('ended_at')
                        // ponytail: ended_at NULL dianggap durasi 1 jam; upgrade: kolom durasi eksplisit
                        ->where('scheduled_at', '>', $slot->started_at->copy()->subHour())))->exists()) {
                return true;
            }
        }
        return false;
    }

    /**
     * D.3: satu-satunya kalkulasi kuota bulan berjalan. Request pending review
     * (scheduled + scheduled_at NULL) ikut memakai kuota karena ia mereservasi slot.
     */
    public static function monthQuotaUsed($row): int
    {
        $start = \Illuminate\Support\Carbon::now()->startOfMonth();
        return $row->sessions()
            ->where(fn ($q) => $q
                ->where(fn ($w) => $w->where('scheduled_at', '>=', $start)->where('scheduled_at', '<', $start->copy()->addMonth()))
                ->orWhere(fn ($w) => $w->whereNull('scheduled_at')->where('status', 'scheduled'))
                // Baris pending yang di-cancel dengan kuota tetap terpakai tidak
                // punya anchor bulan — hitung apa pun statusnya.
                ->orWhere(fn ($w) => $w->whereNull('scheduled_at')->where('quota_consumed', true)))
            ->where(fn ($q) => $q->whereIn('status', ['scheduled', 'done'])
                ->orWhere(fn ($q2) => $q2->whereIn('status', ['cancelled', 'no_show'])->where('quota_consumed', true)))
            ->count();
    }

    public static function quotaUsed($row, $date, ?int $except = null): int
    {
        $start = \Illuminate\Support\Carbon::parse($date)->startOfMonth();
        return $row->sessions()->where('scheduled_at', '>=', $start)->where('scheduled_at', '<', $start->copy()->addMonth())
            ->when($except, fn ($q) => $q->where('id', '!=', $except))
            ->where(fn ($q) => $q->whereIn('status', ['scheduled', 'done'])
                ->orWhere(fn ($q) => $q->whereIn('status', ['cancelled', 'no_show'])->where('quota_consumed', true)))->count();
    }

    public static function cancel(string $vertical, $session, string $reason): bool
    {
        return DB::transaction(function () use ($vertical, $session, $reason) {
            if ($session->consultant_id) {
                Consultant::whereKey($session->consultant_id)->lockForUpdate()->firstOrFail();
            }
            $session = $session->newQuery()->whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_unless($session->status === 'scheduled', 422, 'Hanya sesi terjadwal dapat dibatalkan.');
            $late = !$session->scheduled_at || now()->gte($session->scheduled_at->copy()->subHours($vertical === 'sekolah' ? 24 : 2));
            $session->update(['status' => 'cancelled', 'quota_consumed' => $late, 'cancel_reason' => $reason, 'cancelled_at' => now()]);
            DB::table('booking_claims')->where('owner_type', $vertical)->where('owner_id', $session->id)->delete();
            return !$late;
        });
    }

    public static function reschedule(string $vertical, $row, $session, int $scheduleId)
    {
        return DB::transaction(function () use ($vertical, $row, $session, $scheduleId) {
            $target = Schedule::findOrFail($scheduleId);
            Consultant::whereIn('id', array_filter([$session->consultant_id, $target->consultant_id]))
                ->orderBy('id')->lockForUpdate()->get();
            $session = $row->sessions()->whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_if($session->schedule_id === $scheduleId, 422, 'Pilih slot baru.');
            // ADR 001: version jadwal lanjut ke sesi pengganti (cancel+book = reschedule).
            $nextVersion = (int) ($session->schedule_version ?? 0) + 1;
            $mode = $session->mode;
            self::cancel($vertical, $session, 'Perubahan jadwal');
            return self::book($vertical, $row, [
                'schedule_id' => $scheduleId, 'konsultan_type' => $session->konsultan_type,
                // Mode pilihan karyawan dipertahankan saat ganti jadwal — jangan
                // senyap ditimpa mode slot baru.
                'mode' => $mode,
                ...($vertical === 'sekolah' ? ['origin' => $session->origin] : []),
            ] + ($vertical === 'perusahaan' ? ['schedule_version' => $nextVersion] : []));
        });
    }

    public static function book(string $vertical, $row, array $data)
    {
        $slot = Schedule::findOrFail($data['schedule_id']);
        // W5 (K9): tipe konsultasi default 'chat' — validasi enum di controller.
        $consultationType = $data['consultation_type'] ?? 'chat';
        return DB::transaction(function () use ($vertical, $row, $data, $slot, $consultationType) {
            Consultant::whereKey($slot->consultant_id)->lockForUpdate()->firstOrFail();
            // SQLite starts deferred transactions; this write serializes competing writers before reads.
            $row->newQuery()->whereKey($row->id)->update(['updated_at' => now()]);
            $row = $row->newQuery()->whereKey($row->id)->lockForUpdate()->firstOrFail();
            $slot = Schedule::whereKey($slot->id)->lockForUpdate()->firstOrFail();
            $tenantKey = $vertical.'_id';
            $tenant = $row->$vertical;
            $school = $vertical === 'sekolah';
            // E.1: staf sekolah (dewasa, tanpa wali) pakai consent_at; siswa tetap wajib parent_consent_at.
            $consentOk = $school
                ? ($row->isStaff() ? (bool) $row->consent_at : (bool) $row->parent_consent_at)
                : (bool) $row->consent_at;
            abort_unless($tenant?->is_active && $consentOk, 403, 'Program/consent belum aktif.');
            abort_unless($slot->$tenantKey === $row->$tenantKey && !$slot->klinik_id
                && $slot->status === Schedule::STATUS_CONFIRMED && $slot->started_at->isFuture()
                && $slot->ended_at && $slot->ended_at->gt($slot->started_at)
                && ConsultantPool::validChoice($vertical, $tenant->id, $slot->consultant_id, $data['konsultan_type'])
                && $slot->consultant?->is_active && $slot->consultant?->status === Consultant::ACCEPT, 422, 'Slot/konsultan tidak tersedia.');
            abort_if(($school ? self::quotaUsed($row, $slot->started_at) : self::monthQuotaUsed($row)) >= ($school ? $tenant->sessions_per_student_month : $tenant->sessions_per_employee_month), 403, 'Kuota bulan tanggal sesi sudah habis.');
            if (self::blocked($slot)) {
                throw ValidationException::withMessages(['schedule_id' => 'Slot sudah dipesan. Pilih slot lain.']);
            }

            // W5: tipe one_on_one → assign room fisik bebas (anti-konflik).
            // ponytail: kalau W3 (consultation_types per work_hours) sudah merge,
            // filter kandidat slot/room by tipe yang didukung konsultan di sini —
            // seam: RoomService::pickFreeRoom + RoomService::roomConflicts.
            $roomId = null;
            if ($vertical === 'perusahaan' && $consultationType === 'one_on_one') {
                $roomId = \App\Services\RoomService::pickFreeRoom($row->perusahaan_id ?? $tenant->id, $slot->started_at, $slot->ended_at)
                    ?->id;
                abort_if($roomId === null, 422, 'Tidak ada ruang 1-on-1 bebas di jam itu. Hubungi HR untuk penempatan manual.');
            }

            $session = $row->sessions()->create([
                $tenantKey => $tenant->id,
                $school ? 'kelas' : 'departement_id' => $school ? $row->kelas : $row->departement_id,
                'consultant_id' => $slot->consultant_id, 'schedule_id' => $slot->id,
                'konsultan_type' => $data['konsultan_type'], 'status' => 'scheduled',
                'scheduled_at' => $slot->started_at, 'ended_at' => $slot->ended_at,
                ...($school ? ['origin' => $data['origin'] ?? 'self_booking', 'mode' => $slot->mode] : []),
                ...(!$school ? [
                    'schedule_version' => (int) ($data['schedule_version'] ?? 1),
                    'consultation_type' => $consultationType,
                    'room_id' => $roomId,
                    'mode' => $data['mode'] ?? $slot->mode ?? 'online',
                ] : []),
            ]);
            // W2: entrypoint tunggal kuota — reserve di transaction yang sama.
            if ($vertical === 'perusahaan') {
                \App\Services\QuotaService::reserve($row, $session, $slot->started_at);
            }
            DB::table('booking_claims')->insert(['schedule_id' => $slot->id, 'owner_type' => $vertical, 'owner_id' => $session->id, 'created_at' => now(), 'updated_at' => now()]);
            return $session;
        });
    }
}
