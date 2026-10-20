<?php

namespace Database\Seeders;

use App\Models\Consultant;
use App\Models\ConsultantPackage;
use App\Models\Klinik\CheckoutLink;
use App\Models\Klinik\Klinik;
use App\Models\Klinik\KlinikAdmin;
use App\Models\Klinik\MarkupRule;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Data demo minimal untuk menelusuri flow klinik end-to-end di lokal:
 * klinik → admin → konsultan → paket → markup → jadwal → checkout link.
 *
 * Jalankan: php artisan db:seed --class=DemoKlinikFlowSeeder
 */
class DemoKlinikFlowSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $adminUser = User::where('email', 'klinik@klinik-konsultan.local')->firstOrFail();
        $regencyId = DB::table('regencies')->value('id');

        $klinik = Klinik::firstOrCreate(
            ['name' => 'Klinik Demo Sehat Jiwa'],
            [
                'uuid' => Str::uuid(),
                'brand' => 'Sehat Jiwa',
                'from_email' => 'no-reply@sehatjiwa.local',
                'is_active' => true,
                'approved_at' => now(),
                'mou_at' => now(),
                'regency_id' => $regencyId,
                'facility_type' => 'klinik',
                'address' => 'Jl. Demo No. 1',
                'terms_agreed_at' => now(),
            ]
        );

        KlinikAdmin::firstOrCreate(
            ['klinik_id' => $klinik->id, 'user_id' => $adminUser->id],
            ['role' => 'admin']
        );

        $consultantUser = User::firstOrCreate(
            ['email' => 'konsultan@klinik-konsultan.local'],
            [
                'uuid' => Str::uuid(),
                'firstname' => 'Rani',
                'lastname' => 'Pratama',
                'username' => 'konsultandemo',
                'password' => bcrypt('password'),
                'is_active' => true,
                'role_id' => DB::table('roles')->where('name', 'consultant')->value('id'),
                'regency_id' => $regencyId,
                'gender' => 'Perempuan',
            ]
        );

        $consultant = Consultant::firstOrCreate(
            ['user_id' => $consultantUser->id],
            [
                'uuid' => Str::uuid(),
                'status' => 'accept',
                'is_active' => true,
                'about' => 'Psikolog klinis demo.',
                'profit_sharing' => 60,
                'decision_at' => now(),
            ]
        );

        $package = ConsultantPackage::firstOrCreate(
            ['consultant_id' => $consultant->id, 'name' => 'Sesi Konsultasi 60 menit'],
            ['price' => 250000, 'duration' => 60, 'quota' => 1, 'mode' => 'online']
        );

        // Markup 20% di atas net 250k → pasien bayar 300k (cap §2.2: 15% untuk 300k, jadi 50k masih aman).
        MarkupRule::firstOrCreate(
            ['klinik_id' => $klinik->id, 'consultant_package_id' => $package->id],
            ['markup_amount' => 50000]
        );

        $schedule = Schedule::firstOrCreate(
            ['consultant_id' => $consultant->id, 'klinik_id' => $klinik->id, 'started_at' => now()->addDays(2)->setTime(10, 0)],
            [
                'ended_at' => now()->addDays(2)->setTime(11, 0),
                'mode' => 'online',
                'status' => 'confirmed',
                'created_by' => $adminUser->id,
            ]
        );

        CheckoutLink::firstOrCreate(
            ['token' => 'demo-klinik-token'],
            [
                'klinik_id' => $klinik->id,
                'consultant_id' => $consultant->id,
                'schedule_id' => $schedule->id,
                'consultant_package_id' => $package->id,
                'net_price' => 250000,
                'markup_amount' => 50000,
                'final_price' => 300000,
                'created_by' => $adminUser->id,
                'expires_at' => now()->addDays(7),
            ]
        );

        $this->command?->info('Demo klinik siap: /checkout/demo-klinik-token');
        $this->command?->info('Login klinik: klinik@klinik-konsultan.local / password');
    }
}
