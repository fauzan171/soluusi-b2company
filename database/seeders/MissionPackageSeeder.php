<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\Perusahaan\EmployeePackage;
use App\Models\Perusahaan\PerusahaanEmployee;
use Illuminate\Database\Seeder;

/**
 * Mission M1 demo: package "Wellness Basic" (quota 2/bulan) di-assign ke semua
 * employee demo. Idempotent (firstOrCreate per employee).
 */
class MissionPackageSeeder extends Seeder
{
    public function run(): void
    {
        $package = Package::firstOrCreate(
            ['name' => 'Wellness Basic'],
            ['monthly_quota' => 2, 'is_active' => true],
        );

        PerusahaanEmployee::query()
            ->whereDoesntHave('employeePackages', fn ($q) => $q->where('package_id', $package->id))
            ->each(function (PerusahaanEmployee $emp) use ($package) {
                EmployeePackage::firstOrCreate(
                    [
                        'perusahaan_employee_id' => $emp->id,
                        'package_id' => $package->id,
                    ],
                    [
                        'perusahaan_id' => $emp->perusahaan_id,
                        'starts_at' => now()->startOfMonth()->toDateString(),
                        'status' => 'active',
                    ],
                );
            });
    }
}
