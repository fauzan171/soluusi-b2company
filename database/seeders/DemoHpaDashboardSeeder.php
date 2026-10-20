<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DemoHpaDashboardSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $now = now();
            $associateId = $this->ensureAssociate($now);
            $adminId = DB::table('users')->where('email', 'klinik@klinik-konsultan.local')->value('id');

            $packages = DB::table('consultant_packages')
                ->join('consultants', 'consultant_packages.consultant_id', '=', 'consultants.id')
                ->join('users', 'consultants.user_id', '=', 'users.id')
                ->where('users.email', 'like', 'demo.catalog.%@klinik-demo.local')
                ->select('consultant_packages.*')
                ->orderBy('consultant_packages.id')
                ->get();

            if ($packages->isEmpty()) {
                $this->call(DemoClinicCatalogSeeder::class);

                $packages = DB::table('consultant_packages')
                    ->join('consultants', 'consultant_packages.consultant_id', '=', 'consultants.id')
                    ->join('users', 'consultants.user_id', '=', 'users.id')
                    ->where('users.email', 'like', 'demo.catalog.%@klinik-demo.local')
                    ->select('consultant_packages.*')
                    ->orderBy('consultant_packages.id')
                    ->get();
            }

            if ($packages->isEmpty()) {
                $this->command?->warn('Paket konsultan demo belum tersedia. Jalankan DemoClinicCatalogSeeder dulu.');
                return;
            }

            $clinicPlans = [
                [
                    'name' => 'PT Klinik Demo Customer',
                    'brand' => 'Klinik Demo Customer',
                    'branch' => 'Jakarta Selatan',
                    'address' => 'Jl. Wijaya II No. 17, Jakarta Selatan',
                    'regency_id' => 3171,
                    'total_rate' => 1_000_000,
                    'mou_days' => 86,
                    'is_active' => true,
                    'done' => 42,
                    'scheduled' => 5,
                    'waiting' => 3,
                    'canceled' => 2,
                    'payouts' => [
                        1 => ['status' => 'paid', 'requested_days' => 68, 'paid_days' => 62],
                        2 => ['status' => 'due', 'requested_days' => 14, 'due_days' => 4],
                        3 => ['status' => 'due', 'requested_days' => null, 'due_days' => 8],
                    ],
                ],
                [
                    'name' => 'PT Harmoni Sehat Bersama',
                    'brand' => 'Klinik Harmoni Sehat',
                    'branch' => 'Depok Margonda',
                    'address' => 'Jl. Margonda Raya No. 228, Depok',
                    'regency_id' => null,
                    'total_rate' => 900_000,
                    'mou_days' => 52,
                    'is_active' => true,
                    'done' => 27,
                    'scheduled' => 4,
                    'waiting' => 2,
                    'canceled' => 1,
                    'payouts' => [
                        1 => ['status' => 'paid', 'requested_days' => 36, 'paid_days' => 30],
                        2 => ['status' => 'due', 'requested_days' => null, 'due_days' => 7],
                    ],
                ],
                [
                    'name' => 'PT Amani Tumbuh Kembang',
                    'brand' => 'Klinik Tumbuh Kembang Amani',
                    'branch' => 'BSD',
                    'address' => 'Ruko Foresta Business Loft, Tangerang Selatan',
                    'regency_id' => null,
                    'total_rate' => 1_200_000,
                    'mou_days' => 31,
                    'is_active' => true,
                    'done' => 14,
                    'scheduled' => 6,
                    'waiting' => 1,
                    'canceled' => 1,
                    'payouts' => [
                        1 => ['status' => 'due', 'requested_days' => 5, 'due_days' => 2],
                    ],
                ],
                [
                    'name' => 'PT Gizi Nusantara Medika',
                    'brand' => 'Klinik Gizi Nusantara',
                    'branch' => 'Bekasi Timur',
                    'address' => 'Jl. Chairil Anwar No. 44, Bekasi',
                    'regency_id' => null,
                    'total_rate' => 850_000,
                    'mou_days' => 19,
                    'is_active' => true,
                    'done' => 6,
                    'scheduled' => 3,
                    'waiting' => 1,
                    'canceled' => 0,
                    'payouts' => [
                        1 => ['status' => 'due', 'requested_days' => null, 'due_days' => 9],
                    ],
                ],
                [
                    'name' => 'PT Pulih Fisio Mandiri',
                    'brand' => 'Klinik Fisio Pulih',
                    'branch' => 'Bandung Dago',
                    'address' => 'Jl. Ir. H. Juanda No. 86, Bandung',
                    'regency_id' => null,
                    'total_rate' => 750_000,
                    'mou_days' => 8,
                    'is_active' => false,
                    'done' => 0,
                    'scheduled' => 0,
                    'waiting' => 0,
                    'canceled' => 0,
                    'payouts' => [],
                ],
            ];

            $clinicIds = [];
            foreach ($clinicPlans as $plan) {
                $clinicIds[] = $this->upsertClinic($plan, $associateId, $now);
            }

            $this->cleanupDemoRows($clinicIds);

            $names = [
                'Raka Pratama', 'Mira Salsabila', 'Dewi Lestari', 'Yoga Firmansyah',
                'Sinta Maharani', 'Naufal Hakim', 'Ayu Paramita', 'Bagas Wicaksono',
                'Rini Oktavia', 'Rizki Saputra', 'Melati Anggraini', 'Farhan Maulana',
            ];
            $complaints = [
                'Konsultasi lanjutan burnout kerja',
                'Keluhan cemas dan sulit tidur',
                'Monitoring diet diabetes',
                'Nyeri punggung bawah setelah olahraga',
                'Konseling anak terkait tekanan sekolah',
                'Evaluasi komunikasi keluarga',
            ];

            foreach ($clinicPlans as $clinicIndex => $plan) {
                $klinikId = DB::table('kliniks')->where('brand', $plan['brand'])->value('id');
                $mouAt = $now->copy()->subDays($plan['mou_days'])->startOfDay()->setTime(9, 0);
                $doneDates = $this->spreadDates($mouAt, $now->copy()->subDays(1), $plan['done']);
                $ordinal = 0;

                foreach ($doneDates as $doneAt) {
                    $ordinal++;
                    $package = $packages[($ordinal + $clinicIndex) % $packages->count()];
                    $this->createDemoSession(
                        $klinikId,
                        $package,
                        $doneAt,
                        'done',
                        'verified',
                        $names[$ordinal % count($names)],
                        $complaints[$ordinal % count($complaints)],
                        $adminId,
                        $this->stageForOrdinal($ordinal)
                    );
                }

                for ($i = 1; $i <= $plan['scheduled']; $i++) {
                    $package = $packages[($i + $clinicIndex) % $packages->count()];
                    $start = $now->copy()->addDays($i + $clinicIndex)->setTime(9 + ($i % 6), 0);
                    $this->createDemoSession($klinikId, $package, $start, 'scheduled', 'verified', $names[$i % count($names)], $complaints[$i % count($complaints)], $adminId);
                }

                for ($i = 1; $i <= $plan['waiting']; $i++) {
                    $package = $packages[($i + $clinicIndex + 2) % $packages->count()];
                    $start = $now->copy()->addDays($i + 8)->setTime(10 + ($i % 5), 30);
                    $this->createDemoSession($klinikId, $package, $start, 'waiting', 'waiting', $names[($i + 4) % count($names)], $complaints[($i + 2) % count($complaints)], $adminId);
                }

                for ($i = 1; $i <= $plan['canceled']; $i++) {
                    $package = $packages[($i + $clinicIndex + 4) % $packages->count()];
                    $start = $now->copy()->subDays($i * 4)->setTime(13 + ($i % 4), 0);
                    $this->createDemoSession($klinikId, $package, $start, 'canceled', 'canceled', $names[($i + 7) % count($names)], 'Customer membatalkan jadwal', $adminId);
                }

                $this->seedPervormas($klinikId, $associateId, $plan['total_rate'], $plan['payouts'], $now);
            }

            $this->command?->line('Demo HPA dashboard seeded: ' . count($clinicPlans) . ' klinik referral, sesi, checkout link, split, dan status pencairan.');
        });
    }

    private function ensureAssociate(Carbon $now): int
    {
        DB::table('roles')->updateOrInsert(
            ['name' => 'associate'],
            ['created_at' => $now, 'updated_at' => $now]
        );

        $roleId = DB::table('roles')->where('name', 'associate')->value('id');
        $existing = DB::table('users')->where('email', 'associate@klinik-konsultan.local')->first();

        if ($existing) {
            DB::table('users')->where('id', $existing->id)->update([
                'firstname' => 'HPA',
                'lastname' => 'Demo',
                'username' => 'hapdemo',
                'role_id' => $roleId,
                'is_active' => 1,
                'updated_at' => $now,
            ]);

            return (int) $existing->id;
        }

        return DB::table('users')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'firstname' => 'HPA',
            'lastname' => 'Demo',
            'username' => 'hapdemo',
            'email' => 'associate@klinik-konsultan.local',
            'password' => bcrypt('password'),
            'role_id' => $roleId,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function upsertClinic(array $plan, int $associateId, Carbon $now): int
    {
        $existing = DB::table('kliniks')->where('brand', $plan['brand'])->first();
        $payload = [
            'name' => $plan['name'],
            'brand' => $plan['brand'],
            'domain' => Str::slug($plan['brand']) . '.demo.local',
            'from_email' => 'care@' . Str::slug($plan['brand']) . '.demo.local',
            'associate_id' => $associateId,
            'total_rate' => $plan['total_rate'],
            'mou_at' => $now->copy()->subDays($plan['mou_days'])->setTime(9, 0),
            'is_active' => $plan['is_active'] ? 1 : 0,
            'regency_id' => $plan['regency_id'],
            'approved_at' => $plan['is_active'] ? $now->copy()->subDays(max(1, $plan['mou_days'] - 1)) : null,
            'terms_agreed_at' => $now->copy()->subDays($plan['mou_days']),
            'facility_type' => 'clinic',
            'branch_name' => $plan['branch'],
            'address' => $plan['address'],
            'service_interest' => 'Psikolog, gizi, fisioterapi',
            'estimated_monthly_sessions' => max(8, (int) round($plan['done'] / max(1, $plan['mou_days']) * 30)),
            'updated_at' => $now,
        ];

        if ($existing) {
            DB::table('kliniks')->where('id', $existing->id)->update($payload);
            return (int) $existing->id;
        }

        return DB::table('kliniks')->insertGetId($payload + [
            'uuid' => (string) Str::uuid(),
            'created_at' => $now->copy()->subDays($plan['mou_days']),
        ]);
    }

    private function cleanupDemoRows(array $clinicIds): void
    {
        $sessionIds = DB::table('consultation_sessions')
            ->where('order_number', 'like', 'DEMO-HPA-%')
            ->pluck('id');

        if ($sessionIds->isNotEmpty()) {
            DB::table('split_settlements')->whereIn('session_id', $sessionIds)->delete();
            DB::table('consultation_sessions')->whereIn('id', $sessionIds)->delete();
        }

        DB::table('checkout_links')
            ->whereIn('klinik_id', $clinicIds)
            ->where('token', 'like', 'demo-hpa-%')
            ->delete();

        DB::table('schedules')
            ->whereIn('klinik_id', $clinicIds)
            ->where('latest_updated_by', 'demo-hpa-dashboard')
            ->delete();
    }

    private function spreadDates(Carbon $start, Carbon $end, int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        $days = max(1, $start->diffInDays($end));
        $step = max(1, (int) floor($days / $count));
        $dates = [];

        for ($i = 0; $i < $count; $i++) {
            $dates[] = $start->copy()
                ->addDays(min($days, $i * $step + ($i % 3)))
                ->setTime(9 + ($i % 8), ($i * 15) % 60);
        }

        return $dates;
    }

    private function createDemoSession(
        int $klinikId,
        object $package,
        Carbon $start,
        string $status,
        string $paymentStatus,
        string $customerName,
        string $complaint,
        ?int $adminId,
        ?int $hapStage = null
    ): void {
        $now = now();
        $isDone = $status === 'done';
        $isPaid = in_array($paymentStatus, ['verified', 'waiting'], true);
        $net = (int) $package->price;
        $markup = max(20_000, min(45_000, (int) round($net * 0.1)));
        $final = $paymentStatus === 'canceled' ? 0 : $net + $markup;

        $scheduleId = DB::table('schedules')->insertGetId([
            'consultant_id' => $package->consultant_id,
            'klinik_id' => $klinikId,
            'created_by' => $adminId,
            'latest_updated_by' => 'demo-hpa-dashboard',
            'started_at' => $start,
            'ended_at' => $start->copy()->addMinutes((int) $package->duration),
            'mode' => $package->mode,
            'status' => 'confirmed',
            'location' => $package->mode === 'offline' ? 'Ruang konsultasi klinik' : null,
            'meeting_url' => $package->mode === 'online' ? 'https://meet.google.com/demo-hpa-' . Str::lower(Str::random(6)) : null,
            'notes' => 'Data dummy dashboard HPA',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $linkId = null;
        if ($isPaid) {
            $linkId = DB::table('checkout_links')->insertGetId([
                'token' => 'demo-hpa-' . Str::lower(Str::random(32)),
                'klinik_id' => $klinikId,
                'consultant_id' => $package->consultant_id,
                'schedule_id' => $scheduleId,
                'consultant_package_id' => $package->id,
                'net_price' => $net,
                'markup_amount' => $markup,
                'final_price' => $net + $markup,
                'created_by' => $adminId,
                'expires_at' => $start->copy()->addDays(7),
                'used_at' => $paymentStatus === 'verified' ? $start->copy()->subDays(2) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $sessionId = DB::table('consultation_sessions')->insertGetId([
            'schedule_id' => $scheduleId,
            'consultant_id' => $package->consultant_id,
            'consultant_package_id' => $package->id,
            'klinik_id' => $klinikId,
            'checkout_link_id' => $linkId,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'order_number' => 'DEMO-HPA-' . Str::upper(Str::random(10)),
            'payment' => (string) $final,
            'amount' => $final,
            'package_price' => $final,
            'voucher_amount' => 0,
            'order_at' => $start->copy()->subDays($isDone ? 3 : 1),
            'payment_at' => $paymentStatus === 'verified' ? $start->copy()->subDays($isDone ? 2 : 1) : null,
            'completed_at' => $isDone ? $start->copy()->addMinutes((int) $package->duration) : null,
            'konsultan_done_at' => $isDone ? $start->copy()->addMinutes((int) $package->duration) : null,
            'customer_confirmed_at' => $isDone ? $start->copy()->addMinutes((int) $package->duration + 12) : null,
            'completion_verified_at' => $isDone ? $start->copy()->addMinutes((int) $package->duration + 15) : null,
            'konsultan_checked_in_at' => $isDone || $status === 'scheduled' ? $start->copy()->subMinutes(10) : null,
            'customer_checked_in_at' => $isDone ? $start->copy()->subMinutes(3) : null,
            'cancel_reason' => $status === 'canceled' ? 'Customer membatalkan jadwal' : null,
            'hap_pervorma_stage' => $hapStage,
            'customer_name' => $customerName,
            'customer_email' => Str::slug($customerName) . '@example.test',
            'customer_phone' => '08' . random_int(1111111111, 9999999999),
            'customer_dob' => now()->subYears(24 + random_int(0, 20))->format('Y-m-d'),
            'customer_regency_id' => 3171,
            'customer_gender' => random_int(0, 1) ? 'male' : 'female',
            'complaint' => $complaint,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($paymentStatus === 'verified') {
            DB::table('split_settlements')->insert([
                'session_id' => $sessionId,
                'klinik_id' => $klinikId,
                'net_amount' => $net,
                'klinik_margin' => $markup,
                'status' => $isDone && $start->lt(now()->subDays(12)) ? 'settled' : 'pending',
                'settled_at' => $isDone && $start->lt(now()->subDays(12)) ? $start->copy()->addWeekdays(3) : null,
                'created_at' => $start,
                'updated_at' => $start,
            ]);
        }
    }

    private function seedPervormas(int $klinikId, int $associateId, int $rate, array $payouts, Carbon $now): void
    {
        $amounts = [
            1 => config('klinik.hap.stage1_flat', 250_000),
            2 => (int) round(($rate - config('klinik.hap.stage1_flat', 250_000)) / 3),
            3 => $rate - config('klinik.hap.stage1_flat', 250_000) - (int) round(($rate - config('klinik.hap.stage1_flat', 250_000)) / 3),
        ];

        foreach ($payouts as $stage => $state) {
            $payload = [
                'associate_id' => $associateId,
                'amount' => $amounts[$stage],
                'status' => $state['status'],
                'due_at' => isset($state['due_days']) ? $now->copy()->addDays($state['due_days']) : $now->copy()->subDays(1),
                'paid_at' => isset($state['paid_days']) ? $now->copy()->subDays($state['paid_days']) : null,
                'updated_at' => $now,
            ];

            if (Schema::hasColumn('hap_pervormas', 'requested_at')) {
                $payload['requested_at'] = isset($state['requested_days']) ? $now->copy()->subDays($state['requested_days']) : null;
                $payload['requested_by'] = isset($state['requested_days']) ? $associateId : null;
            }

            DB::table('hap_pervormas')->updateOrInsert(
                ['klinik_id' => $klinikId, 'stage' => $stage],
                $payload + ['created_at' => $now]
            );
        }
    }

    private function stageForOrdinal(int $ordinal): ?int
    {
        if ($ordinal <= config('klinik.hap.stage1_trigger', 5)) {
            return 1;
        }

        if ($ordinal <= config('klinik.hap.stage2_trigger', 25)) {
            return 2;
        }

        if ($ordinal <= config('klinik.hap.stage3_trigger', 40)) {
            return 3;
        }

        return null;
    }
}
