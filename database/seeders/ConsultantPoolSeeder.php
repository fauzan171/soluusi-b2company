<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Pool konsultan yang Soluusi assign ke tenant (sekolah/perusahaan).
 * Sekaligus seed konsultan "Konselor Karir" (belum ada di data demo lama).
 * Idempoten — aman dijalankan berulang.
 */
class ConsultantPoolSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // 1. Kategori + konsultan Karir (belum ada di data demo).
        $karirCatId = DB::table('categories')->where('name', 'Konselor Karir')->value('id')
            ?? DB::table('categories')->insertGetId([
                'code_category' => 'karir_b2b', 'name' => 'Konselor Karir',
                'created_at' => $now, 'updated_at' => $now,
            ]);

        $karirUserId = DB::table('users')->where('email', 'rina.hartono@seosoluusi.com')->value('id');
        if (!$karirUserId) {
            $karirRoleId = DB::table('roles')->where('name', 'consultant')->value('id');
            $karirUserId = DB::table('users')->insertGetId([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'username' => 'rina-hartono-karir',
                'email' => 'rina.hartono@seosoluusi.com',
                'password' => bcrypt('password'),
                'firstname' => 'Rina',
                'lastname' => 'Hartono, M.Psi., CHRP',
                'role_id' => $karirRoleId,
                'is_active' => 1,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $karirConsultantId = DB::table('consultants')->where('user_id', $karirUserId)->value('id');
        if (!$karirConsultantId) {
            $karirConsultantId = DB::table('consultants')->insertGetId([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'user_id' => $karirUserId,
                'about' => 'Konselor Karir berpengalaman untuk karyawan & siswa.',
                'status' => 'accept',
                'is_active' => 1,
                'profit_sharing' => 95,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $hasPivot = DB::table('consultant_categories')
            ->where('consultant_id', $karirConsultantId)->where('category_id', $karirCatId)->exists();
        if (!$hasPivot) {
            DB::table('consultant_categories')->insert([
                'consultant_id' => $karirConsultantId, 'category_id' => $karirCatId,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // 1b. Konsultan dummy tambahan supaya pilihan lebih banyak (per tipe).
        $dummyRoleId = DB::table('roles')->where('name', 'consultant')->value('id');
        $dummies = [
            ['Dewi Anggraini, M.Psi.', 'Psikolog Anak & Remaja', 'Psikolog'],
            ['Sinta Maharani, M.Psi.', 'Psikolog Anak & Remaja', 'Psikolog'],
            ['Yoga Prasetya, M.Psi.', 'Psikolog Klinis', 'Psikolog'],
            ['Bunga Kirana, M.Psi.', 'Konselor Karir', 'Karir'],
            ['Deni Saputra, S.Psi.', 'Konselor Karir', 'Karir'],
            ['Lina Halim, S.Gz', 'Ahli Gizi', 'Gizi'],
            ['Rudi Hartawan, S.Ft.', 'Fisioterapis', 'Fisioterapi'],
        ];
        $dummyIds = [];
        foreach ($dummies as $i => [$name, $catName, $type]) {
            $email = 'dummy.' . \Illuminate\Support\Str::slug($name, '.') . '@seosoluusi.com';
            $userId = DB::table('users')->where('email', $email)->value('id');
            if (!$userId) {
                $userId = DB::table('users')->insertGetId([
                    'uuid' => \Illuminate\Support\Str::uuid(),
                    'username' => \Illuminate\Support\Str::slug($name) . '-dummy',
                    'email' => $email,
                    'password' => bcrypt('password'),
                    'firstname' => explode(' ', $name)[0],
                    'lastname' => implode(' ', array_slice(explode(' ', $name), 1)),
                    'role_id' => $dummyRoleId,
                    'is_active' => 1,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            $cid = DB::table('consultants')->where('user_id', $userId)->value('id');
            if (!$cid) {
                $cid = DB::table('consultants')->insertGetId([
                    'uuid' => \Illuminate\Support\Str::uuid(),
                    'user_id' => $userId,
                    'about' => "$catName berpengalaman untuk program B2B Soluusi.",
                    'status' => 'accept',
                    'is_active' => 1,
                    'profit_sharing' => 95,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            $catId = DB::table('categories')->where('name', $catName)->value('id')
                ?? DB::table('categories')->insertGetId([
                    'code_category' => 'dummy_' . \Illuminate\Support\Str::slug($catName, '_'),
                    'name' => $catName, 'created_at' => $now, 'updated_at' => $now,
                ]);
            $hasPivot = DB::table('consultant_categories')
                ->where('consultant_id', $cid)->where('category_id', $catId)->exists();
            if (!$hasPivot) {
                DB::table('consultant_categories')->insert([
                    'consultant_id' => $cid, 'category_id' => $catId,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            $dummyIds[$type][] = ['id' => $cid, 'cat' => $catName];
        }

        // 2. Pool per tenant (consultant_id sesuai data demo ConsultantSeeder).
        $pools = [
            // SMA Cendekia (sekolah 1): psikolog anak & remaja x2 + karir + dummy
            ['c' => 3, 's' => 1], ['c' => 7, 's' => 1], ['c' => $karirConsultantId, 's' => 1],
            ...array_map(fn ($d) => ['c' => $d['id'], 's' => 1], $dummyIds['Psikolog'] ?? []),
            ...array_map(fn ($d) => ['c' => $d['id'], 's' => 1], $dummyIds['Karir'] ?? []),
            // SMP Harapan (sekolah 2): psikolog anak & remaja + karir + dummy
            ['c' => 3, 's' => 2], ['c' => $karirConsultantId, 's' => 2],
            ...array_map(fn ($d) => ['c' => $d['id'], 's' => 2], $dummyIds['Psikolog'] ?? []),
            ...array_map(fn ($d) => ['c' => $d['id'], 's' => 2], $dummyIds['Karir'] ?? []),
            // Solusi Digital (perusahaan 1): semua tipe + dummy
            ['c' => 2, 'p' => 1], ['c' => 5, 'p' => 1], ['c' => 6, 'p' => 1], ['c' => $karirConsultantId, 'p' => 1],
            ...array_map(fn ($d) => ['c' => $d['id'], 'p' => 1], array_merge(...array_values($dummyIds))),
            // Maju Bersama (perusahaan 2): psikolog + karir + dummy
            ['c' => 2, 'p' => 2], ['c' => $karirConsultantId, 'p' => 2],
            ...array_map(fn ($d) => ['c' => $d['id'], 'p' => 2], $dummyIds['Psikolog'] ?? []),
            ...array_map(fn ($d) => ['c' => $d['id'], 'p' => 2], $dummyIds['Karir'] ?? []),
        ];

        foreach ($pools as $row) {
            DB::table('consultant_pools')->updateOrInsert([
                'consultant_id' => $row['c'],
                'sekolah_id' => $row['s'] ?? null,
                'perusahaan_id' => $row['p'] ?? null,
            ], ['created_at' => $now, 'updated_at' => $now]);
        }

        $this->command->line('  Seeded consultant pools (2 sekolah + 2 perusahaan) + Konselor Karir');
    }
}
