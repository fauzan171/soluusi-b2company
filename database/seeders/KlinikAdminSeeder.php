<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KlinikAdminSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $roleId = DB::table('roles')->where('name', 'klinik_admin')->value('id');
        if (!$roleId) return;
        User::firstOrCreate(
            ['email' => 'klinik@klinik-konsultan.local'],
            [
                'uuid' => Str::uuid(),
                'firstname' => 'Klinik',
                'lastname' => 'Demo',
                'username' => 'klinikdemo',
                'password' => bcrypt('password'),
                'is_active' => true,
                'role_id' => $roleId,
            ]
        );
    }
}
