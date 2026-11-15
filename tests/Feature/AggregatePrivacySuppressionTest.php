<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahStudent;
use App\Models\Sekolah\SekolahSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * P0.3 — Paritas suppression privasi (n≥5) untuk SEMUA output agregat:
 * report blade, CSV export, dashboard charts per-departemen/kelas.
 * Nilai tidak dihapus — diganti null / '—' sesuai pattern WHO-5 existing.
 */
class AggregatePrivacySuppressionTest extends TestCase
{
    use RefreshDatabase;

    private function makeHrTenant(): array
    {
        $hrRoleId = DB::table('roles')->insertGetId(['name' => 'hr_admin', 'created_at' => now(), 'updated_at' => now()]);
        $p = Perusahaan::create([
            'name' => 'PT Priv', 'brand' => 'PrivCo',
            'sessions_per_employee_month' => 2, 'employee_count' => 20, 'is_active' => true,
        ]);
        $hr = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'HR', 'lastname' => 'Test',
            'username' => 'hr_priv', 'email' => 'hr_priv@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $hrRoleId,
        ]);
        DB::table('perusahaan_admins')->insert([
            'perusahaan_id' => $p->id, 'user_id' => $hr->id, 'role' => 'hr_admin',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $deptA = Departement::create(['name' => 'Engineering']);
        $deptB = Departement::create(['name' => 'Finance']);
        return [$p, $hr, $deptA, $deptB];
    }

    private function addEmployee(Perusahaan $p, ?int $deptId, string $n): PerusahaanEmployee
    {
        return PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'departement_id' => $deptId,
            'name' => 'Emp '.$n, 'email' => 'emp'.$n.'@test.local', 'consent_at' => now(),
        ]);
    }

    private function doneSession(Perusahaan $p, PerusahaanEmployee $emp): void
    {
        PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'done',
            'scheduled_at' => now()->subDay(), 'completed_at' => now()->subDay(),
        ]);
    }

    public function test_report_suppresses_small_department_rows_but_keeps_large_ones()
    {
        [$p, $hr, $deptA, $deptB] = $this->makeHrTenant();

        // Engineering: 6 karyawan, 5 sesi done → tampil normal.
        foreach (range(1, 6) as $i) {
            $emp = $this->addEmployee($p, $deptA->id, 'A'.$i);
            if ($i <= 5) {
                $this->doneSession($p, $emp);
            }
        }
        // Finance: 3 karyawan, 2 sesi done → baris harus disuppress.
        foreach (range(1, 3) as $i) {
            $emp = $this->addEmployee($p, $deptB->id, 'B'.$i);
            if ($i <= 2) {
                $this->doneSession($p, $emp);
            }
        }

        $res = $this->actingAs($hr)->get(route('perusahaan.report'));
        $res->assertOk();

        $groups = collect($res->viewData('groups'));
        $eng = $groups->firstWhere('name', 'Engineering');
        $fin = $groups->firstWhere('name', 'Finance');

        $this->assertSame(5, $eng['participants'], 'departemen ≥5 tampil normal');
        $this->assertNull($fin['participants'], 'departemen <5: participants disuppress');
        $this->assertNull($fin['sessions'], 'departemen <5: sessions disuppress');
    }

    public function test_report_all_small_tenant_still_suppressed_as_before()
    {
        [$p, $hr, $deptA] = $this->makeHrTenant();
        foreach (range(1, 2) as $i) {
            $this->doneSession($p, $this->addEmployee($p, $deptA->id, 'S'.$i));
        }

        $res = $this->actingAs($hr)->get(route('perusahaan.report'));
        $res->assertOk();
        $this->assertTrue($res->viewData('suppressed'));
        $this->assertNull($res->viewData('done'));
        $this->assertTrue(collect($res->viewData('groups'))->isEmpty());
    }

    public function test_csv_export_suppresses_small_department_rows()
    {
        [$p, $hr, $deptA, $deptB] = $this->makeHrTenant();
        foreach (range(1, 6) as $i) {
            $emp = $this->addEmployee($p, $deptA->id, 'A'.$i);
            if ($i <= 5) {
                $this->doneSession($p, $emp);
            }
        }
        foreach (range(1, 3) as $i) {
            $emp = $this->addEmployee($p, $deptB->id, 'B'.$i);
            if ($i <= 2) {
                $this->doneSession($p, $emp);
            }
        }

        $res = $this->actingAs($hr)->get(route('perusahaan.dashboard.export'));
        $res->assertOk();
        $csv = $res->getContent();

        $this->assertMatchesRegularExpression('/Engineering,("?5"?)\R/', $csv);
        $this->assertMatchesRegularExpression('/Finance,("?(—|Tidak tersedia)"?)\R/u', $csv);
        $this->assertStringNotContainsString('Finance,2', $csv);
    }

    public function test_dashboard_chart_participation_suppressed_for_small_departments()
    {
        [$p, $hr, $deptA, $deptB] = $this->makeHrTenant();
        foreach (range(1, 6) as $i) {
            $emp = $this->addEmployee($p, $deptA->id, 'A'.$i);
            if ($i <= 5) {
                $this->doneSession($p, $emp);
            }
        }
        foreach (range(1, 3) as $i) {
            $emp = $this->addEmployee($p, $deptB->id, 'B'.$i);
            if ($i <= 2) {
                $this->doneSession($p, $emp);
            }
        }

        $res = $this->actingAs($hr)->get(route('perusahaan.dashboard'));
        $res->assertOk();

        $chartDept = $res->viewData('chartDept');
        $chartPart = $res->viewData('chartParticipation');
        $idxA = array_search('Engineering', $chartDept['labels']);
        $idxB = array_search('Finance', $chartDept['labels']);
        $this->assertNotFalse($idxA);
        $this->assertNotFalse($idxB);

        $this->assertSame(5, $chartDept['data'][$idxA], 'dept ≥5: jumlah sesi tampil');
        $this->assertNull($chartDept['data'][$idxB], 'dept <5: jumlah sesi disuppress');
        $this->assertNull($chartPart['data'][$idxB], 'dept <5: partisipasi disuppress');
    }

    public function test_sekolah_report_suppresses_small_kelas()
    {
        $bkRoleId = DB::table('roles')->insertGetId(['name' => 'bk', 'created_at' => now(), 'updated_at' => now()]);
        $sekolah = Sekolah::create([
            'name' => 'SMA Priv', 'brand' => 'PrivHigh',
            'sessions_per_student_month' => 2, 'student_count' => 20, 'is_active' => true,
        ]);
        $bk = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'BK', 'lastname' => 'Test',
            'username' => 'bk_priv', 'email' => 'bk_priv@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $bkRoleId,
        ]);
        DB::table('sekolah_admins')->insert([
            'sekolah_id' => $sekolah->id, 'user_id' => $bk->id, 'role' => 'bk',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $mk = function (string $kelas, string $n) use ($sekolah) {
            return SekolahStudent::create([
                'sekolah_id' => $sekolah->id, 'name' => 'Siswa '.$n,
                'kelas' => $kelas, 'parent_name' => 'Ortu', 'parent_consent_at' => now(),
            ]);
        };
        $sess = function ($student) use ($sekolah) {
            SekolahSession::create([
                'sekolah_id' => $sekolah->id, 'student_id' => $student->id, 'kelas' => $student->kelas,
                'konsultan_type' => 'psikolog', 'status' => 'done',
                'scheduled_at' => now()->subDay(), 'completed_at' => now()->subDay(),
            ]);
        };

        foreach (range(1, 6) as $i) {
            $s = $mk('XII IPA 1', 'A'.$i);
            if ($i <= 5) {
                $sess($s);
            }
        }
        foreach (range(1, 3) as $i) {
            $s = $mk('XII IPA 2', 'B'.$i);
            if ($i <= 2) {
                $sess($s);
            }
        }

        $res = $this->actingAs($bk)->get(route('sekolah.report'));
        $res->assertOk();

        $groups = collect($res->viewData('groups'));
        $ipa1 = $groups->firstWhere('name', 'XII IPA 1');
        $ipa2 = $groups->firstWhere('name', 'XII IPA 2');

        $this->assertSame(5, $ipa1['participants']);
        $this->assertNull($ipa2['participants'], 'kelas <5: participants disuppress');
    }
}
