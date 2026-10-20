<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $now = now();

        // Seed roles (idempotent). B2B adds klinik_admin + associate (HAP) per PRD §4.2,
        // hr_admin + employee untuk vertikal Perusahaan (CAP),
        // bk + student untuk vertikal Sekolah.
        foreach (['admin', 'consultant', 'customer', 'klinik_admin', 'associate', 'hr_admin', 'employee', 'bk', 'student'] as $name) {
            DB::table('roles')->updateOrInsert(['name' => $name], ['created_at' => $now, 'updated_at' => $now]);
        }

        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');

        // Neutral admin — white-label: no Soluusi brand leak (PRD §15.4).
        User::firstOrCreate(
            ['email' => 'admin@klinik-konsultan.local'],
            [
                'uuid' => Str::uuid(),
                'firstname' => 'Super',
                'lastname' => 'Admin',
                'username' => 'superadmin',
                'password' => bcrypt('password'),
                'is_active' => true,
                'role_id' => $adminRoleId,
            ]
        );

        $this->call([
            KlinikAdminSeeder::class, // ponytail: add clinic admin seed

            PaymentSeeder::class,
            SekolahSeeder::class, // vertikal Sekolah (BK + siswa demo)
        ]);
    }
}
