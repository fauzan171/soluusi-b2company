<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class W1RollbackCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_mission_migrations_rollback_clean()
    {
        $this->assertTrue(Schema::hasTable('audit_logs'));
        // W4 menambah assessment_instruments, W5 migrasi sender_role
        // (2026_11_22_000001), prod rebase menambah consultant_requests
        // (2026_11_21_000001), wellbeing katalog+kontrak menambah 2 migrasi
        // (2026_11_23_000001/2) + wb-T3 description (2026_11_23_000003)
        // + wv1 domain/dimensions/grants (2026_11_23_000100)
        // + wv2 webinars+reviews (2026_11_23_000004/5)
        // → step 7+1+1+1+2+1+1+2.
        Artisan::call('migrate:rollback', ['--step' => 16, '--force' => true]);
        $this->assertFalse(Schema::hasTable('assessment_instruments'));
        foreach (['packages', 'employee_packages', 'consultation_quota_ledger', 'consultant_work_hours',
            'consultant_availability_exceptions', 'consultation_notes', 'consultation_results',
            'consultation_reviews', 'rooms', 'audit_logs'] as $t) {
            $this->assertFalse(Schema::hasTable($t), "{$t} should be dropped");
        }
        $this->assertFalse(Schema::hasColumn('consultant_pools', 'status'));
        $this->assertFalse(Schema::hasColumn('perusahaans', 'consultant_request_enabled'));
        $this->assertFalse(Schema::hasColumn('perusahaan_sessions', 'consultation_type'));
        $this->assertFalse(Schema::hasColumn('screening_questions', 'instrument_code'));
        // re-migrate must work clean
        Artisan::call('migrate', ['--force' => true]);
        $this->assertTrue(Schema::hasTable('audit_logs'));
        $this->assertTrue(Schema::hasColumn('consultant_pools', 'status'));
    }
}
