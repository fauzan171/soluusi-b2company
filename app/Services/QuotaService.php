<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Package;
use App\Models\Perusahaan\ConsultationQuotaLedger;
use App\Models\Perusahaan\EmployeePackage;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * QuotaService (W2 / MASTER-PLAN K2-K3): satu-satunya pintu reservasi kuota.
 * Ledger append-only, periode calendar month (period_ym "YYYY-M-", tz app).
 * SEMUA jalur pembuatan perusahaan_sessions (book/startChat/requestConsultation)
 * WAJIB panggil reserve() dalam satu DB::transaction — sqlite NO lockForUpdate,
 * correctness dari UNIQUE(perusahaan_session_id, event) + re-check saldo.
 *
 * Saldo per (assignment, period) = monthly_quota
 *   - reserved - used - forfeited   (pakai / hold / hangus: TERHITUNG terpakai)
 *   + released + refunded           (kompensasi).
 * Employee tanpa employee_packages (data legacy) fallback kuota tenant lama
 * perusahaans.sessions_per_employee_month — tanpa ledger row (read-only).
 */
class QuotaService
{
    /** Definisi paket aktif employee (fallback legacy bila belum di-assign). */
    public static function assignmentFor(PerusahaanEmployee $employee): ?EmployeePackage
    {
        $ep = $employee->employeePackages()
            ->where('status', 'active')
            ->whereDate('starts_at', '<=', today())
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()))
            ->orderByDesc('id')->first();

        if ($ep) {
            return $ep;
        }

        // ponytail: fallback legacy = kuota flat tenant, tanpa ledger row;
        // upgrade: jalankan quota:backfill + assign package per employee.
        return null;
    }

    /** periode "YYYY-M-" dari waktu sesi (default now). */
    public static function periodOf(?Carbon $sessionAt): string
    {
        return ($sessionAt ?: now())->format('Y-m');
    }

    /** Sisa kuota bulan ini untuk employee (package → ledger, legacy → tenant quota). */
    public static function balanceFor(PerusahaanEmployee $employee, ?Carbon $sessionAt = null): int
    {
        $ep = static::assignmentFor($employee);
        $period = static::periodOf($sessionAt);

        if (! $ep) {
            // legacy: hitung sesi bulan ini langsung (pre-mission, tanpa ledger)
            $monthStart = now()->startOfMonth();
            $used = $employee->sessions()
                ->whereIn('status', ['scheduled', 'done'])
                ->where(fn ($q) => $q->where('scheduled_at', '>=', $monthStart)->orWhereNull('scheduled_at'))
                ->count();
            return max(0, (int) $employee->perusahaan?->sessions_per_employee_month ?? 0) - $used;
        }

        return max(0, static::quota($ep) - static::outflow($ep, $period) + static::inflow($ep, $period));
    }

    /** Nama package + kuota untuk pesan error/UI. */
    public static function packageLabel(PerusahaanEmployee $employee): array
    {
        $ep = static::assignmentFor($employee);
        if (! $ep?->package) {
            $quota = (int) $employee->perusahaan?->sessions_per_employee_month ?? 0;
            return ['name' => 'Paket Standar', 'monthly_quota' => $quota];
        }
        return ['name' => $ep->package->name, 'monthly_quota' => static::quota($ep)];
    }

    /**
     * Reserve 1 kuota untuk sesi baru. WAJIB dipanggil dalam DB::transaction
     * yang sama dengan insert perusahaan_sessions (entrypoint tunggal).
     * Idempotent: UNIQUE(session, event) menolak reserve ganda.
     * Employee tanpa assignment → auto-assign package default (pola
     * QuotaBackfillCommand) supaya data pre-mission tetap tercover ledger.
     */
    public static function reserve(PerusahaanEmployee $employee, PerusahaanSession $session, ?Carbon $sessionAt = null): ConsultationQuotaLedger
    {
        $ep = static::ensureAssignment($employee);
        $period = static::periodOf($sessionAt ?: $session->scheduled_at);
        $label = static::packageLabel($employee);

        $existing = ConsultationQuotaLedger::where('perusahaan_session_id', $session->id)
            ->where('event', ConsultationQuotaLedger::EVENT_RESERVED)->first();
        if ($existing) {
            return $existing; // idempotent (retry / double-submit)
        }

        $balance = max(0, static::quota($ep) - static::outflow($ep, $period) + static::inflow($ep, $period));
        abort_if($balance < 1, 403, "Kuota bulan ini habis ({$label['name']} ".static::quota($ep)."x).");

        return ConsultationQuotaLedger::create([
            'employee_package_id' => $ep->id,
            'perusahaan_session_id' => $session->id,
            'period_ym' => $period,
            'event' => ConsultationQuotaLedger::EVENT_RESERVED,
        ]);
    }

    /** Sesi selesai → event used (reserved jadi terminal, saldo tak berubah). */
    public static function consume(PerusahaanSession $session): ?ConsultationQuotaLedger
    {
        return static::append($session, ConsultationQuotaLedger::EVENT_USED);
    }

    /** Hangus (no_show / late-cancel) → event forfeited; TERHITUNG terpakai. */
    public static function forfeit(PerusahaanSession $session, string $reason = 'no_show'): ?ConsultationQuotaLedger
    {
        $session->update([
            'status' => 'no_show',
            'quota_consumed' => true,
            'delivery_status' => 'no_show',
        ]);

        return static::append($session, ConsultationQuotaLedger::EVENT_FORFEITED, $reason);
    }

    /**
     * Refund kuota: event baru (+1) oleh HR/superadmin, reason wajib,
     * audit_logs entry. Tidak pernah mengubah row existing (append-only).
     */
    public static function refund(ConsultationQuotaLedger $reserved, User $actor, string $reason): ConsultationQuotaLedger
    {
        abort_if($reserved->event !== ConsultationQuotaLedger::EVENT_RESERVED, 422, 'Hanya entry reserved yang bisa direfund.');
        $session = $reserved->session;
        abort_if(! $session, 422, 'Entry tidak terhubung ke sesi.');
        abort_if(ConsultationQuotaLedger::where('perusahaan_session_id', $session->id)
            ->where('event', ConsultationQuotaLedger::EVENT_REFUNDED)->exists(), 422, 'Sesi ini sudah direfund.');

        $ledger = DB::transaction(function () use ($reserved, $actor, $reason) {
            $row = ConsultationQuotaLedger::create([
                'employee_package_id' => $reserved->employee_package_id,
                'perusahaan_session_id' => $reserved->perusahaan_session_id,
                'period_ym' => $reserved->period_ym,
                'event' => ConsultationQuotaLedger::EVENT_REFUNDED,
                'reason' => $reason,
                'actor_id' => $actor->id,
            ]);

            AuditLog::create([
                'actor_id' => $actor->id,
                'action' => 'quota.refund',
                'subject_type' => 'consultation_quota_ledger',
                'subject_id' => $reserved->id,
                'meta' => ['reason' => $reason, 'period_ym' => $reserved->period_ym],
            ]);

            return $row;
        });

        return $ledger;
    }

    /** Pembatalan sebelum cutoff → released (+1); dipanggil BookingService::cancel. */
    public static function release(PerusahaanSession $session, string $reason): ?ConsultationQuotaLedger
    {
        return static::append($session, ConsultationQuotaLedger::EVENT_RELEASED, $reason);
    }

    // --- internals ---------------------------------------------------------

    /**
     * Assignment aktif; jika belum ada (data pre-mission) auto-create dari
     * package default aktif, fallback kuota tenant lama. Dipakai reserve().
     */
    private static function ensureAssignment(PerusahaanEmployee $employee): EmployeePackage
    {
        $ep = static::assignmentFor($employee);
        if ($ep) {
            return $ep;
        }

        $package = Package::where('is_active', true)->orderBy('id')->first()
            ?? Package::create([
                'name' => 'Paket Standar',
                'monthly_quota' => max(1, (int) $employee->perusahaan?->sessions_per_employee_month ?? 2),
                'is_active' => true,
            ]);

        return EmployeePackage::create([
            'perusahaan_employee_id' => $employee->id,
            'package_id' => $package->id,
            'perusahaan_id' => $employee->perusahaan_id,
            'starts_at' => now()->startOfMonth()->toDateString(),
            'status' => 'active',
        ]);
    }

    private static function append(PerusahaanSession $session, string $event, ?string $reason = null): ?ConsultationQuotaLedger
    {
        $existing = ConsultationQuotaLedger::where('perusahaan_session_id', $session->id)
            ->where('event', $event)->first();
        if ($existing) {
            return $existing; // idempotent
        }

        // cari assignment dari reserved row; tanpa reserved → tidak ada kuota tercatat
        $reserved = ConsultationQuotaLedger::where('perusahaan_session_id', $session->id)
            ->where('event', ConsultationQuotaLedger::EVENT_RESERVED)->first();
        if (! $reserved) {
            return null; // sesi pre-mission tanpa ledger — backfill urus
        }

        return ConsultationQuotaLedger::create([
            'employee_package_id' => $reserved->employee_package_id,
            'perusahaan_session_id' => $session->id,
            'period_ym' => $reserved->period_ym,
            'event' => $event,
            'reason' => $reason,
        ]);
    }

    private static function quota(EmployeePackage $ep): int
    {
        return max(0, (int) $ep->package?->monthly_quota ?? 0);
    }

    private static function outflow(EmployeePackage $ep, string $period): int
    {
        return ConsultationQuotaLedger::where('employee_package_id', $ep->id)
            ->where('period_ym', $period)
            ->whereIn('event', [
                ConsultationQuotaLedger::EVENT_RESERVED,
                ConsultationQuotaLedger::EVENT_USED,
                ConsultationQuotaLedger::EVENT_FORFEITED,
            ])->count();
    }

    private static function inflow(EmployeePackage $ep, string $period): int
    {
        return ConsultationQuotaLedger::where('employee_package_id', $ep->id)
            ->where('period_ym', $period)
            ->whereIn('event', [
                ConsultationQuotaLedger::EVENT_RELEASED,
                ConsultationQuotaLedger::EVENT_REFUNDED,
            ])->count();
    }
}
