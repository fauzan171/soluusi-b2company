<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// Backfill migration: sesi legacy (scheduled_at terisi, schedule_version=0)
// harus di-upgrade ke schedule_version=1.
class ScheduleVersionBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_scheduled_session_backfilled_to_version_1(): void
    {
        // RefreshDatabase sudah migrate:fresh, jadi backfill sudah jalan.
        // Simulasi ulang: reset ke kondisi legacy, lalu jalankan migrasi backfill lagi.
        $perusahaanId = DB::table('perusahaans')->insertGetId([
            'name' => 'PT Backfill', 'brand' => 'BackfillCo',
            'sessions_per_employee_month' => 5, 'employee_count' => 1,
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $legacyId = DB::table('perusahaan_sessions')->insertGetId([
            'perusahaan_id' => $perusahaanId,
            'konsultan_type' => 'psikolog',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
            'schedule_version' => 0, // kondisi legacy
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $unscheduledId = DB::table('perusahaan_sessions')->insertGetId([
            'perusahaan_id' => $perusahaanId,
            'konsultan_type' => 'psikolog',
            'status' => 'scheduled',
            'scheduled_at' => null,
            'schedule_version' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Jalankan ulang migrasi backfill (fresh DB sudah migrate, tapi row di atas
        // dibuat setelahnya dalam kondisi legacy).
        $migration = include database_path('migrations/2026_11_15_000005_backfill_schedule_version_on_perusahaan_sessions_table.php');
        $migration->up();

        $this->assertSame(1, (int) DB::table('perusahaan_sessions')->where('id', $legacyId)->value('schedule_version'));
        $this->assertSame(0, (int) DB::table('perusahaan_sessions')->where('id', $unscheduledId)->value('schedule_version'));
    }
}
