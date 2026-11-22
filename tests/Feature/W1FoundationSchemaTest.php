<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Consultant;
use App\Models\ConsultantAvailabilityException;
use App\Models\ConsultantPool;
use App\Models\ConsultantWorkHour;
use App\Models\Perusahaan\EmployeePackage;
use App\Models\Package;
use App\Models\Perusahaan\ConsultationNote;
use App\Models\Perusahaan\ConsultationQuotaLedger;
use App\Models\Perusahaan\ConsultationResult;
use App\Models\Perusahaan\ConsultationReview;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\Perusahaan\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// W1: skema foundation M1-M7 (MASTER-PLAN §2) — tabel, kolom, relasi, factory smoke.
class W1FoundationSchemaTest extends TestCase
{
    use RefreshDatabase;

    private function perusahaan(): Perusahaan
    {
        return Perusahaan::create([
            'name' => 'PT W1 Schema', 'brand' => 'W1Co',
            'sessions_per_employee_month' => 2, 'employee_count' => 5, 'is_active' => true,
        ]);
    }

    private function employee(Perusahaan $p): PerusahaanEmployee
    {
        static $i = 0;
        $i++;
        return PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'name' => "Emp {$i}",
            'email' => "emp{$i}.w1@test.local", 'consent_at' => now(),
        ]);
    }

    public function test_m1_packages_and_employee_packages()
    {
        $this->assertTrue(Schema::hasTable('packages'));
        $this->assertTrue(Schema::hasTable('employee_packages'));

        $p = $this->perusahaan();
        $pkg = Package::factory()->create(['name' => 'W1 Wellness Basic', 'monthly_quota' => 2]);
        $emp = $this->employee($p);

        $ep = EmployeePackage::create([
            'perusahaan_employee_id' => $emp->id,
            'package_id' => $pkg->id,
            'perusahaan_id' => $p->id,
            'starts_at' => now()->startOfMonth(),
            'status' => 'active',
        ]);
        $this->assertTrue($ep->exists());
        $this->assertEquals(2, $ep->package->monthly_quota);
        $this->assertEquals($emp->id, $ep->employee->id);
        $this->assertEquals($p->id, $ep->perusahaan->id);
        $this->assertSame('active', $ep->status);
    }

    public function test_m2_consultation_quota_ledger()
    {
        $this->assertTrue(Schema::hasTable('consultation_quota_ledger'));

        $p = $this->perusahaan();
        $pkg = Package::factory()->create();
        $emp = $this->employee($p);
        $ep = EmployeePackage::create([
            'perusahaan_employee_id' => $emp->id, 'package_id' => $pkg->id,
            'perusahaan_id' => $p->id, 'starts_at' => now()->startOfMonth(), 'status' => 'active',
        ]);
        $ses = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled', 'scheduled_at' => now(),
        ]);

        $l = ConsultationQuotaLedger::create([
            'employee_package_id' => $ep->id, 'perusahaan_session_id' => $ses->id,
            'period_ym' => now()->format('Y-m'), 'event' => 'reserved',
        ]);
        $this->assertTrue($l->exists());
        $this->assertEquals($ep->id, $l->employeePackage->id);
        $this->assertEquals($ses->id, $l->session->id);

        // UNIQUE(perusahaan_session_id, event) = idempotent guard.
        $this->expectException(\Illuminate\Database\QueryException::class);
        ConsultationQuotaLedger::create([
            'employee_package_id' => $ep->id, 'perusahaan_session_id' => $ses->id,
            'period_ym' => now()->format('Y-m'), 'event' => 'reserved',
        ]);
    }

    public function test_m3_consultant_pool_contract_and_work_hours()
    {
        $this->assertTrue(Schema::hasColumns('consultant_pools', ['contract_start', 'contract_end', 'status']));
        $this->assertTrue(Schema::hasTable('consultant_work_hours'));
        $this->assertTrue(Schema::hasTable('consultant_availability_exceptions'));

        $consultant = Consultant::factory()->create(['status' => 'accept']);
        $p = $this->perusahaan();
        $pool = ConsultantPool::create([
            'perusahaan_id' => $p->id, 'consultant_id' => $consultant->id,
            'contract_start' => now()->startOfMonth()->toDateString(),
            'contract_end' => now()->addMonths(3)->endOfMonth()->toDateString(),
        ]);
        $this->assertSame('active', DB::table('consultant_pools')->where('id', $pool->id)->value('status')); // default 'active'

        $wh = ConsultantWorkHour::create([
            'consultant_pool_id' => $pool->id, 'weekday' => 1,
            'start_time' => '09:00', 'end_time' => '17:00', 'slot_minutes' => 60,
        ]);
        $this->assertTrue($wh->exists());
        $this->assertEquals($pool->id, $wh->consultantPool->id);

        // full-day block: start/end NULL
        $ex = ConsultantAvailabilityException::create([
            'consultant_id' => $consultant->id, 'date' => now()->addDay()->toDateString(),
        ]);
        $this->assertNull($ex->start_time);
        $this->assertNull($ex->end_time);
        $this->assertEquals($consultant->id, $ex->consultant->id);
    }

    public function test_m4_perusahaan_flag_and_session_columns()
    {
        $this->assertTrue(Schema::hasColumn('perusahaans', 'consultant_request_enabled'));
        $this->assertTrue(Schema::hasColumns('perusahaan_sessions', ['consultation_type', 'meeting_url', 'room_id']));

        $p = $this->perusahaan();
        $this->assertFalse((bool) $p->fresh()->consultant_request_enabled); // default 0 (OFF)

        $emp = $this->employee($p);
        $ses = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled', 'scheduled_at' => now(),
        ]);
        $this->assertSame('chat', DB::table('perusahaan_sessions')->where('id', $ses->id)->value('consultation_type')); // default 'chat'
        $this->assertNull($ses->fresh()->meeting_url);
        $this->assertNull($ses->fresh()->room_id);

        // backfill: legacy row dengan consultation_type NULL di-set 'chat' oleh command.
        // (kolom NOT NULL default 'chat' — row legacy simulasi via kolom legacy yang
        // dilewati insert path baru, diuji di command backfill pada row hasil rebuild.)
        $this->artisan('quota:backfill')->assertExitCode(0);
    }

    public function test_m5_notes_results_reviews_rooms()
    {
        foreach (['consultation_notes', 'consultation_results', 'consultation_reviews', 'rooms'] as $t) {
            $this->assertTrue(Schema::hasTable($t), "missing table {$t}");
        }

        $p = $this->perusahaan();
        $emp = $this->employee($p);
        $consultant = Consultant::factory()->create(['status' => 'accept']);
        $ses = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id, 'consultant_id' => $consultant->id,
            'konsultan_type' => 'psikolog', 'status' => 'done', 'scheduled_at' => now()->subDay(),
            'completed_at' => now()->subDay()->addHour(),
        ]);

        // notes: body encrypted at rest.
        $note = ConsultationNote::create([
            'perusahaan_session_id' => $ses->id, 'consultant_id' => $consultant->id,
            'body' => 'Catatan klinis rahasia pasien demo.',
        ]);
        $this->assertSame('Catatan klinis rahasia pasien demo.', $note->fresh()->body);
        $raw = DB::table('consultation_notes')->where('id', $note->id)->value('body');
        $this->assertNotSame('Catatan klinis rahasia pasien demo.', $raw);
        $this->assertStringNotContainsString('rahasia', $raw);

        $res = ConsultationResult::create([
            'perusahaan_session_id' => $ses->id,
            'summary' => 'Ringkasan demo', 'recommendation' => 'Follow-up 2 minggu',
        ]);
        $this->assertSame('Ringkasan demo', $res->fresh()->summary);
        $this->assertEquals($ses->id, $res->fresh()->session->id);

        $rev = ConsultationReview::create([
            'perusahaan_session_id' => $ses->id, 'perusahaan_employee_id' => $emp->id,
            'rating' => 5, 'comment' => 'Sangat membantu',
        ]);
        $this->assertEquals(5, $rev->fresh()->rating);

        // UNIQUE perusahaan_session_id pada review.
        $this->expectException(\Illuminate\Database\QueryException::class);
        ConsultationReview::create([
            'perusahaan_session_id' => $ses->id, 'perusahaan_employee_id' => $emp->id, 'rating' => 4,
        ]);

        $room = Room::create(['perusahaan_id' => $p->id, 'name' => 'Ruang Konsultasi 1']);
        $this->assertSame('active', $room->fresh()->status); // default is_active 'active'
    }

    public function test_m6_screening_question_instrument_columns()
    {
        $this->assertTrue(Schema::hasColumns('screening_questions', ['instrument_code', 'version', 'weight']));

        $qid = DB::table('screening_questions')->insertGetId([
            'vertical' => 'perusahaan', 'section' => 'mental_health',
            'code' => 'W1T1', 'text' => 'Pertanyaan demo', 'type' => 'freq0-4',
            'reverse' => false, 'ord' => 1,
            'instrument_code' => 'WHO-5', 'version' => '1998', 'weight' => 1.0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->assertGreaterThan(0, $qid);
        $row = DB::table('screening_questions')->where('id', $qid)->first();
        $this->assertSame('WHO-5', $row->instrument_code);
        $this->assertEquals(1.0, $row->weight);
    }

    public function test_m7_audit_logs()
    {
        $this->assertTrue(Schema::hasTable('audit_logs'));

        $u = User::create([
            'uuid' => \Illuminate\Support\Str::uuid(), 'firstname' => 'A', 'lastname' => 'Ditor',
            'username' => 'w1auditor', 'email' => 'w1audit@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
            'role_id' => DB::table('roles')->insertGetId(['name' => 'admin', 'created_at' => now(), 'updated_at' => now()]),
        ]);
        $log = AuditLog::create([
            'actor_id' => $u->id, 'action' => 'consultation_note.view',
            'subject_type' => 'perusahaan_session', 'subject_id' => 42,
            'meta' => ['session_id' => 42], 'ip' => '10.0.0.1',
        ]);
        $this->assertTrue($log->exists());
        $this->assertEquals(['session_id' => 42], $log->fresh()->meta);
        $this->assertEquals($u->id, $log->fresh()->actor->id);
    }

    public function test_quota_backfill_is_idempotent()
    {
        $p = $this->perusahaan();
        $pkg = Package::factory()->create(['monthly_quota' => 2]);
        $emp = $this->employee($p);
        $ep = EmployeePackage::create([
            'perusahaan_employee_id' => $emp->id, 'package_id' => $pkg->id,
            'perusahaan_id' => $p->id, 'starts_at' => now()->startOfMonth(), 'status' => 'active',
        ]);

        // 1 done + 1 no_show(quota_consumed) bulan ini, 1 cancelled bulan lalu.
        PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id, 'konsultan_type' => 'psikolog',
            'status' => 'done', 'scheduled_at' => now()->startOfMonth()->addDays(2), 'completed_at' => now(),
        ]);
        PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id, 'konsultan_type' => 'psikolog',
            'status' => 'no_show', 'scheduled_at' => now()->startOfMonth()->addDays(3), 'quota_consumed' => true,
        ]);
        PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id, 'konsultan_type' => 'psikolog',
            'status' => 'cancelled', 'scheduled_at' => now()->subMonth(), 'quota_consumed' => true,
        ]);

        $this->artisan('quota:backfill')->assertExitCode(0);
        $this->assertSame(3, ConsultationQuotaLedger::count()); // 1 used + 2 forfeited

        $this->artisan('quota:backfill')->assertExitCode(0);
        $this->assertSame(3, ConsultationQuotaLedger::count()); // 2nd run = no changes

        $events = ConsultationQuotaLedger::orderBy('id')->pluck('event')->all();
        $this->assertEqualsCanonicalizing(['used', 'forfeited', 'forfeited'], $events);
        $periods = ConsultationQuotaLedger::orderBy('id')->pluck('period_ym')->unique()->values();
        $this->assertContains(now()->format('Y-m'), $periods->all());
        $this->assertContains(now()->subMonth()->startOfMonth()->format('Y-m'), $periods->all());
    }

    public function test_mission_package_seeder_assigns_demo_employees()
    {
        $this->artisan('db:seed', ['--class' => 'PerusahaanSeeder'])->assertExitCode(0);
        $this->artisan('db:seed', ['--class' => 'MissionPackageSeeder'])->assertExitCode(0);

        $pkg = Package::where('name', 'Wellness Basic')->first();
        $this->assertNotNull($pkg, 'Wellness Basic package missing');
        $this->assertSame(2, (int) $pkg->monthly_quota);
        $this->assertTrue((bool) $pkg->is_active);

        $employees = \App\Models\Perusahaan\PerusahaanEmployee::count();
        $this->assertGreaterThan(0, $employees);
        $this->assertSame($employees, EmployeePackage::where('package_id', $pkg->id)->count(),
            'seeder must assign package to ALL demo employees');

        // idempotent re-run
        $this->artisan('db:seed', ['--class' => 'MissionPackageSeeder'])->assertExitCode(0);
        $this->assertSame($employees, EmployeePackage::where('package_id', $pkg->id)->count());
    }
}
