<?php

namespace Tests\Feature;

use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use App\Services\AiInsightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AiInsightRedactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_redact_strips_person_names_emails_and_phones()
    {
        $text = 'Karyawan Bayu Wirawan mengeluh stres. Hubungi bayu.w@example.com atau 0812-3456-7890. Rekan kerja: Siti Rahma.';
        $out = AiInsightService::redact($text);

        $this->assertStringNotContainsString('Bayu Wirawan', $out);
        $this->assertStringNotContainsString('Siti Rahma', $out);
        $this->assertStringNotContainsString('bayu.w@example.com', $out);
        $this->assertStringNotContainsString('0812-3456-7890', $out);
    }

    public function test_redact_keeps_non_name_text()
    {
        $text = 'topik: manajemen stres kerja';
        $this->assertSame($text, AiInsightService::redact($text));
    }

    public function test_build_context_does_not_leak_employee_names()
    {
        $roleId = DB::table('roles')->insertGetId(['name' => 'employee', 'created_at' => now(), 'updated_at' => now()]);
        $p = Perusahaan::create([
            'name' => 'PT Test', 'brand' => 'TestCo',
            'sessions_per_employee_month' => 2,
            'employee_count' => 5, 'is_active' => true,
        ]);
        $user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Bayu', 'lastname' => 'Wirawan',
            'username' => 'bayu_w', 'email' => 'bayu@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'Bayu Wirawan', 'email' => 'bayu@test.local', 'consent_at' => now(),
        ]);

        PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'konsultan_type' => 'psikolog', 'status' => 'done',
            'scheduled_at' => now()->subDays(3),
            'note' => 'Karyawan Bayu Wirawan melaporkan tekanan kerja dari manajer Siti Rahma. Kontak keluarga 0812-3456-7890.',
        ]);

        $context = AiInsightService::buildContext($p, 'perusahaan');

        $this->assertStringNotContainsString('Bayu Wirawan', $context);
        $this->assertStringNotContainsString('Siti Rahma', $context);
        $this->assertStringNotContainsString('0812-3456-7890', $context);
        $this->assertStringNotContainsString('bayu@test.local', $context);
    }
}
