<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CompanyWellbeingProgram;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use App\Models\WellbeingQuotaLedger;
use App\Models\WellbeingWebinar;
use Illuminate\Support\Facades\DB;

/**
 * WellbeingQuotaService (wb-T2 / MASTER-PLAN K6, R1/R2/R11): kuota kontrak-level,
 * 2 bucket expert_chat|deep_support, TANPA period_ym — window = umur kontrak
 * (status=active && starts_at <= today <= ends_at). Saldo hangus saat ends_at,
 * tidak dipindahtuangkan ke kontrak baru. Append-only mirror pola QuotaService
 * (idempotent UNIQUE(session,event) + re-check saldo dalam DB::transaction).
 *
 * QuotaService (bulanan per employee) TIDAK diubah — call-site routing if-guard:
 * program aktif → jalur ini; tanpa program → fallback legacy identik.
 */
class WellbeingQuotaService
{
    public const BUCKET_EXPERT_CHAT = 'expert_chat';
    public const BUCKET_DEEP_SUPPORT = 'deep_support';

    /** Program wellbeing aktif perusahaan (K8: app-level 1 aktif; latest menang). */
    public static function activeProgram(Perusahaan $company): ?CompanyWellbeingProgram
    {
        return $company->wellbeingPrograms()
            ->where('status', 'active')
            ->whereDate('starts_at', '<=', today())
            ->whereDate('ends_at', '>=', today())
            ->orderByDesc('id')->first();
    }

    /**
     * Bucket kuota sesi menurut kontrak aktif (routing R2):
     * chat → expert_chat; one_on_one|online_meeting → deep_support (mode
     * kontrak menentukan snapshot deep_support yang dikonsumsi, bukan bucket).
     */
    public static function bucketFor(string $consultationType): string
    {
        return $consultationType === 'chat'
            ? self::BUCKET_EXPERT_CHAT
            : self::BUCKET_DEEP_SUPPORT;
    }

    /** Sisa kuota bucket program (R1: hanya berarti saat program aktif). */
    public static function bucketBalance(CompanyWellbeingProgram $program, string $bucket): int
    {
        if ($program->status !== 'active'
            || $program->starts_at->gt(today()) || $program->ends_at->lt(today())) {
            return 0;
        }

        $snapshot = (int) match ($bucket) {
            self::BUCKET_EXPERT_CHAT => $program->expert_chat_quota,
            'webinar' => $program->webinar_quota, // wv2 §2: bucket webinar, snapshot sendiri
            default => $program->deep_support_quota,
        };
        // Rows ber-sesi dihitung distinct per sesi (1 sesi = 1 kuota, idempotent
        // UNIQUE(session,event)); rows tanpa sesi (webinar, penyesuaian manual)
        // dihitung per-row — COUNT(DISTINCT col) di sqlite mengabaikan NULL.
        $outflow = WellbeingQuotaLedger::where('program_id', $program->id)
            ->where('bucket', $bucket)
            ->whereIn('event', [
                WellbeingQuotaLedger::EVENT_RESERVED,
                WellbeingQuotaLedger::EVENT_USED,
                WellbeingQuotaLedger::EVENT_FORFEITED,
            ])
            ->distinct('perusahaan_session_id')
            ->count('perusahaan_session_id')
            + WellbeingQuotaLedger::where('program_id', $program->id)
                ->where('bucket', $bucket)
                ->whereNull('perusahaan_session_id')
                ->whereIn('event', [
                    WellbeingQuotaLedger::EVENT_RESERVED,
                    WellbeingQuotaLedger::EVENT_USED,
                    WellbeingQuotaLedger::EVENT_FORFEITED,
                ])
                ->count();
        $inflow = WellbeingQuotaLedger::where('program_id', $program->id)
            ->where('bucket', $bucket)
            ->whereIn('event', [
                WellbeingQuotaLedger::EVENT_RELEASED,
                WellbeingQuotaLedger::EVENT_REFUNDED,
            ])->count();

        return max(0, $snapshot - $outflow + $inflow);
    }

    /**
     * Reserve 1 kuota bucket kontrak. WAJIB dalam DB::transaction yang sama
     * dengan insert perusahaan_sessions (entrypoint tunggal, mirror QuotaService).
     */
    public static function reserve(PerusahaanEmployee $employee, PerusahaanSession $session, string $bucket): WellbeingQuotaLedger
    {
        $program = self::activeProgram($employee->perusahaan);
        abort_unless($program, 403, 'Tidak ada program wellbeing aktif.');

        // QA7-F2: kunci baris program supaya cek saldo + insert ter-serialisasi
        // lintas karyawan yang sama-sama mengambil unit terakhir bucket.
        // WAJIB dipanggil dalam DB::transaction (BookingService::book).
        // SQLite: no-op (single writer); MySQL prod: lock nyata.
        CompanyWellbeingProgram::whereKey($program->id)->lockForUpdate()->first();

        // SEC MED (K12 Discovery): deep support Discovery hanya untuk karyawan
        // terpilih (max 8) yang di-set superadmin saat assign — bukan siapa pun.
        if ($bucket === self::BUCKET_DEEP_SUPPORT
            && $program->selected_employee_ids
            && ! in_array($employee->id, (array) $program->selected_employee_ids)) {
            abort(403, 'Sesi deep support program ini terbatas untuk peserta terpilih. Hubungi HR.');
        }

        $existing = WellbeingQuotaLedger::where('perusahaan_session_id', $session->id)
            ->where('event', WellbeingQuotaLedger::EVENT_RESERVED)->first();
        if ($existing) {
            return $existing; // idempotent (retry / double-submit)
        }

        $balance = self::bucketBalance($program, $bucket);
        abort_if($balance < 1, 403, self::exhaustedMessage($program, $bucket));

        return WellbeingQuotaLedger::create([
            'program_id' => $program->id,
            'bucket' => $bucket,
            'perusahaan_session_id' => $session->id,
            'perusahaan_employee_id' => $employee->id,
            'event' => WellbeingQuotaLedger::EVENT_RESERVED,
        ]);
    }

    /** Sesi selesai → event used (terminal). Null bila sesi bukan milik kontrak wellbeing. */
    public static function consume(PerusahaanSession $session, string $bucket): ?WellbeingQuotaLedger
    {
        return self::append($session, $bucket, WellbeingQuotaLedger::EVENT_USED);
    }

    /** No-show/late-cancel → event forfeited; TERHITUNG terpakai (R11). */
    public static function forfeit(PerusahaanSession $session, string $reason = 'no_show'): ?WellbeingQuotaLedger
    {
        $session->update([
            'status' => 'no_show',
            'quota_consumed' => true,
            'delivery_status' => 'no_show',
        ]);

        // bucket diambil dari row reserved (forfeit tak punya konteks tipe sesi).
        $reserved = WellbeingQuotaLedger::where('perusahaan_session_id', $session->id)
            ->where('event', WellbeingQuotaLedger::EVENT_RESERVED)->first();
        if (! $reserved) {
            return null; // sesi bukan milik kontrak wellbeing
        }

        return self::append($session, $reserved->bucket, WellbeingQuotaLedger::EVENT_FORFEITED, $reason);
    }

    /** Pembatalan sebelum cutoff → released (+1). */
    public static function release(PerusahaanSession $session, string $reason): ?WellbeingQuotaLedger
    {
        $reserved = WellbeingQuotaLedger::where('perusahaan_session_id', $session->id)
            ->where('event', WellbeingQuotaLedger::EVENT_RESERVED)->first();
        if (! $reserved) {
            return null;
        }

        return self::append($session, $reserved->bucket, WellbeingQuotaLedger::EVENT_RELEASED, $reason);
    }

    /** Refund kuota oleh HR/superadmin: event baru (+1), reason wajib, audit log. */
    public static function refund(WellbeingQuotaLedger $reserved, User $actor, string $reason): WellbeingQuotaLedger
    {
        abort_if($reserved->event !== WellbeingQuotaLedger::EVENT_RESERVED, 422, 'Hanya entry reserved yang bisa direfund.');
        abort_if(WellbeingQuotaLedger::where('perusahaan_session_id', $reserved->perusahaan_session_id)
            ->where('event', WellbeingQuotaLedger::EVENT_REFUNDED)->exists(), 422, 'Sesi ini sudah direfund.');

        return DB::transaction(function () use ($reserved, $actor, $reason) {
            $row = WellbeingQuotaLedger::create([
                'program_id' => $reserved->program_id,
                'bucket' => $reserved->bucket,
                'perusahaan_session_id' => $reserved->perusahaan_session_id,
                'perusahaan_employee_id' => $reserved->perusahaan_employee_id,
                'event' => WellbeingQuotaLedger::EVENT_REFUNDED,
                'reason' => $reason,
                'actor_id' => $actor->id,
            ]);

            AuditLog::create([
                'actor_id' => $actor->id,
                'action' => 'quota.refund',
                'subject_type' => 'wellbeing_quota_ledger',
                'subject_id' => $reserved->id,
                'meta' => ['reason' => $reason, 'bucket' => $reserved->bucket],
            ]);

            return $row;
        });
    }

    /**
     * wv2 (research2-infra §2): webinar selesai → ledger row bucket 'webinar'
     * event 'used' TANPA sesi (counting per-row di bucketBalance). Guard saldo
     * + window aktif; idempotent per webinar (reason "webinar:{id}").
     */
    public static function consumeWebinar(CompanyWellbeingProgram $program, WellbeingWebinar $webinar, User $actor): WellbeingQuotaLedger
    {
        $ledger = WellbeingQuotaLedger::where('program_id', $program->id)
            ->where('bucket', 'webinar')
            ->where('event', WellbeingQuotaLedger::EVENT_USED)
            ->where('reason', 'webinar:'.$webinar->id)
            ->first();
        if ($ledger) {
            return $ledger; // idempotent (retry / double-submit)
        }

        $balance = self::bucketBalance($program, 'webinar');
        abort_if($balance < 1, 422, 'Kuota webinar kontrak sudah habis atau program tidak aktif.');

        return WellbeingQuotaLedger::create([
            'program_id' => $program->id,
            'bucket' => 'webinar',
            'perusahaan_session_id' => null,
            'event' => WellbeingQuotaLedger::EVENT_USED,
            'reason' => 'webinar:'.$webinar->id,
            'actor_id' => $actor->id,
        ]);
    }

    /**
     * wv2: no-show webinar → event forfeited manual (TERHITUNG terpakai, R11).
     * Row per webinar, idempotent (reason "webinar:{id} no-show").
     */
    public static function forfeitWebinar(CompanyWellbeingProgram $program, WellbeingWebinar $webinar, User $actor, string $reason): WellbeingQuotaLedger
    {
        // REV HIGH-1: idempotent — lookup "webinar:{id} no-show:" (dengan
        // delimiter spasi) supaya webinar 5 TIDAK match row webinar 51
        // (prefix "webinar:5%" menabrak "webinar:51 no-show: ..." — QA4 HIGH).
        $existing = WellbeingQuotaLedger::where('program_id', $program->id)
            ->where('bucket', 'webinar')
            ->where('event', WellbeingQuotaLedger::EVENT_FORFEITED)
            ->where('reason', 'like', 'webinar:'.$webinar->id.' no-show:%')
            ->first();
        if ($existing) {
            return $existing;
        }

        return WellbeingQuotaLedger::create([
            'program_id' => $program->id,
            'bucket' => 'webinar',
            'perusahaan_session_id' => null,
            'event' => WellbeingQuotaLedger::EVENT_FORFEITED,
            'reason' => 'webinar:'.$webinar->id.' no-show: '.$reason,
            'actor_id' => $actor->id,
        ]);
    }

    /** Pesan 403 kuota habis (nama paket kontrak + bucket). */
    public static function exhaustedMessage(CompanyWellbeingProgram $program, string $bucket): string
    {
        $label = $bucket === self::BUCKET_EXPERT_CHAT ? 'kuota chat' : 'kuota deep support';
        $name = $program->wellbeingPackage?->name ?? 'Program Wellbeing';

        return "Kuota kontrak {$label} sudah habis ({$name}). Hubungi HR untuk info program.";
    }

    /** Entry reserved wellbeing milik sesi ini (routing call-site consume/forfeit). */
    public static function reservedLedgerFor(PerusahaanSession $session): ?WellbeingQuotaLedger
    {
        return WellbeingQuotaLedger::where('perusahaan_session_id', $session->id)
            ->where('event', WellbeingQuotaLedger::EVENT_RESERVED)->first();
    }

    // --- internals ---------------------------------------------------------

    private static function append(PerusahaanSession $session, string $bucket, string $event, ?string $reason = null): ?WellbeingQuotaLedger
    {
        $existing = WellbeingQuotaLedger::where('perusahaan_session_id', $session->id)
            ->where('event', $event)->first();
        if ($existing) {
            return $existing; // idempotent
        }

        $reserved = WellbeingQuotaLedger::where('perusahaan_session_id', $session->id)
            ->where('event', WellbeingQuotaLedger::EVENT_RESERVED)->first();
        if (! $reserved) {
            return null; // sesi bukan milik kontrak wellbeing — caller fallback QuotaService
        }

        return WellbeingQuotaLedger::create([
            'program_id' => $reserved->program_id,
            'bucket' => $reserved->bucket ?: $bucket,
            'perusahaan_session_id' => $session->id,
            'perusahaan_employee_id' => $reserved->perusahaan_employee_id,
            'event' => $event,
            'reason' => $reason,
        ]);
    }
}
