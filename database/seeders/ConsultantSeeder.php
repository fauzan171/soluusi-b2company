<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConsultantSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('consultants')->count() > 0) {
            $this->command->line('Consultants already exist, skipping...');
            return;
        }

        $now = now();
        $consultantRoleId = DB::table('roles')->where('name', 'consultant')->value('id');

        // 1. Categories (code_category dipakai di mapCategory & filter homepage)
        $cats = [
            ['code_category' => 'mentalhealth', 'name' => 'Kesehatan Mental'],
            ['code_category' => 'karir', 'name' => 'Karir & Profesional'],
            ['code_category' => 'pajak', 'name' => 'Pajak'],
            ['code_category' => 'hukum', 'name' => 'Hukum & Bisnis'],
            ['code_category' => 'KesehatanFisikTerapi', 'name' => 'Kesehatan Fisik'],
        ];
        $catIds = [];
        foreach ($cats as $c) {
            $catIds[$c['code_category']] = DB::table('categories')->insertGetId(
                $c + ['created_at' => $now, 'updated_at' => $now]
            );
        }

        // 2. Ambil avatar files existing di storage/app/public/avatars
        $avatarDir = storage_path('app/public/avatars');
        $avatars = is_dir($avatarDir)
            ? array_values(array_diff(scandir($avatarDir), ['.', '..']))
            : [];
        $avatarIdx = 0;
        $pickAvatar = function () use (&$avatars, &$avatarIdx) {
            if (empty($avatars)) return null;
            $f = $avatars[$avatarIdx % count($avatars)];
            $avatarIdx++;
            return $f;
        };

        // 3. Data konsultan (nama, kategori, specialty, kota)
        $rows = [
            ['Dr. Sari Wijaya, M.Psi', 'mentalhealth', 'Psikolog Klinis', 'Jakarta'],
            ['Andi Pratama, S.T., MBA', 'karir', 'Career Coach', 'Surabaya'],
            ['Budi Santoso, S.E., M.Tax', 'pajak', 'Konsultan Pajak', 'Bandung'],
            ['Rina Marlina, S.H., M.Kn', 'hukum', 'Konsultan Hukum Bisnis', 'Yogyakarta'],
            ['dr. Dewi Lestari, Sp.GK', 'KesehatanFisikTerapi', 'Ahli Gizi Klinis', 'Medan'],
            ['Hendra Gunawan, M.Psi, Psikolog', 'mentalhealth', 'Konselor Keluarga', 'Jakarta'],
            ['Maya Anggraini, ACC', 'karir', 'Executive Coach', 'Semarang'],
            ['Reza Fahlevi, S.H.', 'hukum', 'Pendiri UMKM & Legalitas', 'Bali'],
        ];

        foreach ($rows as $r) {
            [$name, $catCode, $specialty, $city] = $r;
            $username = Str::slug($name) . rand(10, 99);
            $email = $username . '@seosoluusi.com';

            // user
            $userId = DB::table('users')->insertGetId([
                'uuid' => Str::uuid(),
                'username' => $username,
                'email' => $email,
                'password' => bcrypt('password'),
                'firstname' => explode(' ', $name)[0],
                'lastname' => implode(' ', array_slice(explode(' ', $name), 1)),
                'role_id' => $consultantRoleId,
                'is_active' => 1,
                'avatar' => $pickAvatar(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // consultant
            $consultantId = DB::table('consultants')->insertGetId([
                'uuid' => Str::uuid(),
                'user_id' => $userId,
                'name' => $name,
                'slug' => Str::slug($name) . '-' . Str::random(4),
                'email' => $email,
                'category' => $catCode,
                'specialty' => $specialty,
                'city' => $city,
                'about' => $specialty . ' berpengalaman, siap membantu kebutuhan Anda secara profesional.',
                'status' => 'accept',
                'is_active' => 1,
                'can_write_blog' => 1,
                'blog_count' => 0,
                'profit_sharing' => 95,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // pivot category
            DB::table('consultant_categories')->insert([
                'consultant_id' => $consultantId,
                'category_id' => $catIds[$catCode],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command->line('  Seeded ' . count($rows) . ' consultants + ' . count($cats) . ' categories');
    }
}
