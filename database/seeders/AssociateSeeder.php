<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssociateSeeder extends Seeder
{
    public function run(): void
    {
        $roleId = DB::table('roles')->where('name', 'associate')->value('id');
        if (!$roleId) return;

        $user = \App\Models\User::firstOrCreate(
            ['email' => 'associate@klinik-konsultan.local'],
            [
                'uuid' => Str::uuid(),
                'firstname' => 'HAP',
                'lastname' => 'Demo',
                'username' => 'hapdemo',
                'password' => bcrypt('password'),
                'is_active' => true,
                'role_id' => $roleId,
            ]
        );

        // Link to first existing klinik (if any) so dashboard has data
        $klinik = DB::table('kliniks')->whereNull('associate_id')->first();
        if ($klinik) {
            DB::table('kliniks')->where('id', $klinik->id)->update(['associate_id' => $user->id]);
        }
    }
}
