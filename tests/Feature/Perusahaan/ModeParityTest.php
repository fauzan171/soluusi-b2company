<?php

namespace Tests\Feature\Perusahaan;

use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * G.3/G.4/G.6: pilihan mode konsultasi tampil di form, tersimpan, dan dibaca
 * balik oleh HR + konsultan. Body keluhan TIDAK boleh ikut tampil ke HR.
 */
class ModeParityTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email, string $role): User
    {
        $roleId = DB::table('roles')->where('name', $role)->value('id')
            ?? DB::table('roles')->insertGetId(['name' => $role, 'created_at' => now(), 'updated_at' => now()]);

        return User::create([
            'uuid' => Str::uuid(), 'firstname' => 'U', 'lastname' => $role,
            'username' => Str::slug($email), 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
    }

    /** [$perusahaan, $empUser, $emp, $hrUser, $consultant, $docUser] */
    private function fixture(): array
    {
        $p = Perusahaan::create([
            'name' => 'PT Test', 'brand' => 'TestCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $empUser = $this->user('emp@test.local', 'employee');
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $empUser->id,
            'name' => 'Emp', 'email' => 'emp@test.local', 'consent_at' => now(),
        ]);
        $hrUser = $this->user('hr@test.local', 'hr_admin');
        PerusahaanAdmin::create(['perusahaan_id' => $p->id, 'user_id' => $hrUser->id, 'name' => 'HR']);
        $docUser = $this->user('doc@test.local', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        ConsultantPool::create(['perusahaan_id' => $p->id, 'consultant_id' => $consultant->id]);

        return [$p, $empUser, $emp, $hrUser, $consultant, $docUser];
    }

    public function test_employee_form_renders_mode_radio(): void
    {
        [$p, $empUser] = $this->fixture();

        $this->actingAs($empUser)->get(route('perusahaan.employee'))
            ->assertOk()
            ->assertSee('name="mode"', false)
            ->assertSee('Tatap muka 1-on-1')
            ->assertSee('value="offline"', false);
    }

    public function test_hr_review_shows_mode_badge_but_never_complaint_body(): void
    {
        [$p, $empUser, $emp, $hrUser, $consultant] = $this->fixture();
        PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => null, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => null,
            'mode' => 'offline', 'request_notes' => 'RAHASIA-JANGAN-SAMPAI-HR',
        ]);

        $this->actingAs($hrUser)->get(route('perusahaan.review'))
            ->assertOk()
            ->assertSee('Tatap muka')
            ->assertDontSee('RAHASIA-JANGAN-SAMPAI-HR');
    }

    public function test_consultant_sessions_shows_mode_badge(): void
    {
        [$p, $empUser, $emp, $hrUser, $consultant, $docUser] = $this->fixture();
        PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => now()->addDay(), 'mode' => 'offline',
        ]);

        $this->actingAs($docUser)->get(route('consultant.sessions'))
            ->assertOk()
            ->assertSee('Tatap muka');
    }

    public function test_propose_slots_page_only_for_owning_consultant(): void
    {
        [$p, $empUser, $emp, $hrUser, $consultant, $docUser] = $this->fixture();
        $session = PerusahaanSession::create([
            'perusahaan_id' => $p->id, 'employee_id' => $emp->id,
            'consultant_id' => $consultant->id, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => null,
        ]);

        $this->actingAs($docUser)->get(route('consultant.sessions.propose-slots.form', $session))
            ->assertOk();

        $other = $this->user('doc2@test.local', 'consultant');
        Consultant::create([
            'user_id' => $other->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Other', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);

        $this->actingAs($other)->get(route('consultant.sessions.propose-slots.form', $session))
            ->assertForbidden();
    }
}