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
        // bk + student + sekolah_staff untuk vertikal Sekolah.
        foreach (['admin', 'consultant', 'customer', 'klinik_admin', 'associate', 'hr_admin', 'employee', 'bk', 'student', 'sekolah_staff'] as $name) {
            DB::table('roles')->updateOrInsert(['name' => $name], ['created_at' => $now, 'updated_at' => $now]);
        }

        // wv4: internal roles B2B (doc 6 peran) — rows only. admin tetap gate
        // tunggal middleware; granular gating menyusul saat ops split-role.
        foreach (['account_manager', 'operations', 'expert_coordinator', 'finance'] as $name) {
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
            // G5: demo seeders (KlinikAdminSeeder, SekolahSeeder) tidak lagi
            // di-chain — db:seed di production harus idempotent, tanpa data demo.
            PaymentSeeder::class,
        ]);
    }
}
