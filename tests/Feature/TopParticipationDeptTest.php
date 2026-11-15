<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Services\DashboardMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression (reviewer C): topParticipationDept dulu SELALU null — zip pair
 * tidak punya key 'name'. Fix: pair[0] setelah sortByDesc pair[1].
 */
class TopParticipationDeptTest extends TestCase
{
    use RefreshDatabase;

    public function test_top_participation_dept_returns_highest_participation_label()
    {
        $p = Perusahaan::create([
            'name' => 'PT TopDept', 'brand' => 'TopCo',
            'sessions_per_employee_month' => 2, 'employee_count' => 20, 'is_active' => true,
        ]);
        $deptA = Departement::create(['name' => 'Engineering']);
        $deptB = Departement::create(['name' => 'Finance']);

        // Dept A: 6 karyawan, 6 done (100%). Dept B: 6 karyawan, 5 done (83%).
        // Kedua grup ≥5 contributor → lolos suppression.
        foreach (range(1, 6) as $i) {
            $emp = PerusahaanEmployee::create([
                'perusahaan_id' => $p->id, 'departement_id' => $deptA->id,
                'name' => 'Eng '.$i, 'email' => 'eng'.$i.'@test.local', 'consent_at' => now(),
            ]);
            PerusahaanSession::create([
                'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
                'konsultan_type' => 'psikolog', 'status' => 'done',
                'scheduled_at' => now()->subDays(2), 'completed_at' => now()->subDays(2),
            ]);
        }
        $empsB = collect(range(1, 6))->map(fn ($i) => PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'departement_id' => $deptB->id,
            'name' => 'Fin '.$i, 'email' => 'fin'.$i.'@test.local', 'consent_at' => now(),
        ]));
        foreach ($empsB->take(5) as $emp) {
            PerusahaanSession::create([
                'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
                'konsultan_type' => 'psikolog', 'status' => 'done',
                'scheduled_at' => now()->subDays(2), 'completed_at' => now()->subDays(2),
            ]);
        }

        $metrics = DashboardMetrics::build($p, 'perusahaan');

        $this->assertSame('Engineering', $metrics['topParticipationDept']);
    }
}
