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
        Artisan::call('migrate:rollback', ['--step' => 7, '--force' => true]);
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
