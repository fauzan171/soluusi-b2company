<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Services\DashboardMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T7.1 — DashboardMetrics harus < 10 query untuk tenant HR biasa.
 * Fix: eager load departement (menghilangkan N+1 di dept chart).
 */
class DashboardMetricsQueryCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_metrics_stays_under_query_budget()
    {
        $p = Perusahaan::create([
            'name' => 'PT N1', 'brand' => 'N1Co',
            'sessions_per_employee_month' => 2, 'employee_count' => 30, 'is_active' => true,
        ]);
        $depts = collect(range(1, 5))->map(fn ($i) => Departement::create(['name' => 'Dept '.$i]));
        foreach ($depts as $d) {
            foreach (range(1, 6) as $j) {
                $emp = PerusahaanEmployee::create([
                    'perusahaan_id' => $p->id, 'departement_id' => $d->id,
                    'name' => 'Emp '.$d->id.'-'.$j, 'email' => 'e'.$d->id.'_'.$j.'@test.local',
                    'consent_at' => now(),
                ]);
                PerusahaanSession::create([
                    'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
                    'konsultan_type' => 'psikolog', 'status' => 'done',
                    'scheduled_at' => now()->subDays(2), 'completed_at' => now()->subDays(2),
                ]);
            }
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $metrics = DashboardMetrics::build($p, 'perusahaan');
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertNotEmpty($metrics['chartDept']['labels']);
        $count = count($queries);
        $this->assertLessThan(10, $count, "DashboardMetrics menjalankan {$count} query, budget <10.\n".
            collect($queries)->map(fn ($q) => $q['query'])->implode("\n"));
    }
}
