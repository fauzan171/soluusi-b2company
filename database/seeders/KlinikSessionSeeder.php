<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Dummy sesi untuk development: dashboard klinik perlu data 12 minggu terakhir
 * (chart "Sesi selesai per minggu" + breakdown status + margin ledger).
 * Spread: 2-6 sesi selesai/minggu + beberapa scheduled/canceled.
 * Jalankan: php artisan db:seed --class=KlinikSessionSeeder
 */
class KlinikSessionSeeder extends Seeder
{
    public function run(): void
    {
        $klinikId = DB::table('kliniks')->where('name', 'PT Klinik Prima Sehat')->value('id');
        if (!$klinikId) {
            $this->command->warn('Jalankan KlinikDemoSeeder dulu.');
            return;
        }

        $marker = DB::table('consultation_sessions')->where('order_number', 'like', 'DEMO%')->exists();
        if ($marker) {
            $this->command->line('KlinikSessionSeeder already ran, skipping...');
            return;
        }

        $now = now();
        $packages = DB::table('consultant_packages')
            ->whereIn('consultant_id', fn ($q) => $q->select('id')->from('consultants')
                ->whereIn('user_id', fn ($q2) => $q2->select('id')->from('users')->where('email', 'like', '%soluusi-demo.local')))
            ->get();

        if ($packages->isEmpty()) {
            $this->command->warn('Belum ada paket konsultan demo. Jalankan KlinikDemoSeeder dulu.');
            return;
        }

        $names = ['Rina Hartati', 'Joko Susilo', 'Ani Yudhoyono', 'Bambang Pamungkas', 'Siti Rahma', 'Dedi Kurniawan', 'Fitri Handayani', 'Agus Salim', 'Lina Marlina', 'Tono Wibowo'];
        $count = 0;

        // 1. Sesi selesai (verified) — spread 12 minggu, 2-6 per minggu
        foreach (range(0, 11) as $week) {
            $weekStart = $now->copy()->subWeeks(11 - $week)->startOfWeek();
            $sessionCount = rand(2, 6);
            foreach (range(1, $sessionCount) as $i) {
                $package = $packages->random();
                $net = (int) $package->price;
                $markup = (int) (DB::table('markup_rules')
                    ->where('klinik_id', $klinikId)
                    ->where('consultant_package_id', $package->id)
                    ->value('markup_amount') ?? 50000);
                $final = $net + $markup;
                $verifiedAt = $weekStart->copy()->addDays(rand(0, 5))->setTime(rand(9, 17), rand(0, 59));

                // jadwal selesai (sesi butuh FK schedule) — buat schedule lampau dummy
                $scheduleId = DB::table('schedules')->insertGetId([
                    'consultant_id' => $package->consultant_id,
                    'klinik_id' => $klinikId,
                    'latest_updated_by' => 'seeder-session',
                    'started_at' => $verifiedAt->copy()->subMinutes($package->duration),
                    'ended_at' => $verifiedAt,
                    'mode' => $package->mode,
                    'status' => 'confirmed',
                    'meeting_url' => $package->mode === 'online' ? 'https://meet.google.com/demo-' . Str::random(6) : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $sessionId = DB::table('consultation_sessions')->insertGetId([
                    'schedule_id' => $scheduleId,
                    'consultant_id' => $package->consultant_id,
                    'consultant_package_id' => $package->id,
                    'klinik_id' => $klinikId,
                    'status' => 'done',
                    'payment_status' => 'verified',
                    'order_number' => 'DEMO-' . Str::upper(Str::random(10)),
                    'payment' => $final,
                    'amount' => $final,
                    'package_price' => $final,
                    'voucher_amount' => 0,
                    'order_at' => $verifiedAt->copy()->subDays(rand(1, 3)),
                    'payment_at' => $verifiedAt->copy()->subDays(rand(1, 3)),
                    'konsultan_done_at' => $verifiedAt->copy()->subMinutes(30),
                    'customer_confirmed_at' => $verifiedAt->copy()->subMinutes(10),
                    'completion_verified_at' => $verifiedAt,
                    'completed_at' => $verifiedAt,
                    'customer_name' => $names[array_rand($names)],
                    'customer_email' => 'pasien-demo-' . rand(1, 999) . '@example.com',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // split ledger — 70% settled, sisanya pending
                DB::table('split_settlements')->insert([
                    'session_id' => $sessionId,
                    'klinik_id' => $klinikId,
                    'net_amount' => $net,
                    'klinik_margin' => $markup,
                    'status' => $week < 9 ? 'settled' : 'pending',
                    'settled_at' => $week < 9 ? $verifiedAt->copy()->addWeekdays(3) : null,
                    'created_at' => $verifiedAt,
                    'updated_at' => $verifiedAt,
                ]);
                $count++;
            }
        }

        // 2. Sesi scheduled (upcoming) — sudah dibayar, belum jalan
        foreach (range(1, 4) as $i) {
            $package = $packages->random();
            $net = (int) $package->price;
            $markup = 50000;
            $final = $net + $markup;
            $scheduleAt = $now->copy()->addDays(rand(1, 6))->setTime(rand(9, 16), 0);

            $scheduleId = DB::table('schedules')->insertGetId([
                'consultant_id' => $package->consultant_id,
                'klinik_id' => $klinikId,
                'latest_updated_by' => 'seeder-session',
                'started_at' => $scheduleAt,
                'ended_at' => $scheduleAt->copy()->addMinutes($package->duration),
                'mode' => $package->mode,
                'status' => 'confirmed',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $sessionId = DB::table('consultation_sessions')->insertGetId([
                'schedule_id' => $scheduleId,
                'consultant_id' => $package->consultant_id,
                'consultant_package_id' => $package->id,
                'klinik_id' => $klinikId,
                'status' => 'scheduled',
                'payment_status' => 'verified',
                'order_number' => 'DEMO-' . Str::upper(Str::random(10)),
                'payment' => $final,
                'amount' => $final,
                'package_price' => $final,
                'voucher_amount' => 0,
                'order_at' => $now->copy()->subDays(rand(0, 2)),
                'payment_at' => $now->copy()->subDays(rand(0, 2)),
                'customer_name' => $names[array_rand($names)],
                'customer_email' => 'pasien-demo-' . rand(1, 999) . '@example.com',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('split_settlements')->insert([
                'session_id' => $sessionId,
                'klinik_id' => $klinikId,
                'net_amount' => $net,
                'klinik_margin' => $markup,
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $count++;
        }

        // 3. Canceled untuk variasi status chart
        foreach (['canceled' => 3] as $status => $qty) {
            foreach (range(1, $qty) as $i) {
                $package = $packages->random();
                $past = $now->copy()->subDays(rand(3, 30))->setTime(rand(9, 16), 0);
                $scheduleId = DB::table('schedules')->insertGetId([
                    'consultant_id' => $package->consultant_id,
                    'klinik_id' => $klinikId,
                    'latest_updated_by' => 'seeder-session',
                    'started_at' => $past,
                    'ended_at' => $past->copy()->addMinutes($package->duration),
                    'mode' => $package->mode,
                    'status' => 'confirmed',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('consultation_sessions')->insert([
                    'schedule_id' => $scheduleId,
                    'consultant_id' => $package->consultant_id,
                    'consultant_package_id' => $package->id,
                    'klinik_id' => $klinikId,
                    'status' => $status,
                    'payment_status' => 'canceled',
                    'order_number' => 'DEMO-' . Str::upper(Str::random(10)),
                    'payment' => 0,
                    'amount' => 0,
                    'package_price' => $package->price,
                    'voucher_amount' => 0,
                    'order_at' => $past->copy()->subDays(2),
                    'payment_at' => null,
                    'no_show_at' => null,
                    'cancel_reason' => 'Customer membatalkan sesi',
                    'customer_name' => $names[array_rand($names)],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $count++;
            }
        }

        $this->command->line("  $count sesi dummy seeded (12 minggu: 2-6 selesai/minggu + scheduled + canceled)");
    }
}
