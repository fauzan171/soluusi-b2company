<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UuidSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $consultants = \App\Models\Consultant::all();

        foreach ($consultants as $consultant) {
            $consultant->uuid = (string) \Illuminate\Support\Str::orderedUuid();
            $consultant->save();
        }

        $users = \App\Models\User::all();

        foreach ($users as $user) {
            $user->uuid = (string) \Illuminate\Support\Str::orderedUuid();
            $user->save();
        }
    }
}
