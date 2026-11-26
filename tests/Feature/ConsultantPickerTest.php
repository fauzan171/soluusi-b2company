<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConsultantPickerTest extends TestCase
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

    private function fixture(bool $consented = true): array
    {
        $p = Perusahaan::create([
            'name' => 'PT Test', 'brand' => 'TestCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $user = $this->user('emp@test.local', 'employee');
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'Emp', 'email' => 'emp@test.local', 'consent_at' => $consented ? now() : null,
        ]);
        $docUser = $this->user('doc@test.local', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        ConsultantPool::create([
            'perusahaan_id' => $p->id,
            'consultant_id' => $consultant->id,
        ]);

        return [$p, $user, $emp, $docUser, $consultant];
    }

    public function test_picker_page_returns_200(): void
    {
        [$p, $user, $emp, $docUser, $consultant] = $this->fixture();

        $response = $this->actingAs($user)->get(route('perusahaan.consultants'));

        $response->assertOk();
        $response->assertSee('Pilih Konsultan');
    }

    public function test_start_chat_redirects_to_booking_and_creates_no_session(): void
    {
        [$p, $user, $emp, $docUser, $consultant] = $this->fixture();

        $response = $this->actingAs($user)
            ->post(route('perusahaan.consultants.start-chat', $consultant));

        $response->assertRedirect(route('perusahaan.booking', ['consultant_id' => $consultant->id]));
        $this->assertDatabaseCount('perusahaan_sessions', 0);
    }

    public function test_directory_booking_links_preselect_consultant(): void
    {
        [$p, $user, $emp, $docUser, $consultant] = $this->fixture();
        $url = route('perusahaan.booking', ['consultant_id' => $consultant->id]);

        $this->actingAs($user)->get(route('perusahaan.consultants'))
            ->assertOk()->assertSee($url, false);
    }

    public function test_duplicate_active_session_redirects_to_existing(): void
    {
        [$p, $user, $emp, $docUser, $consultant] = $this->fixture();

        $existing = PerusahaanSession::create([
            'perusahaan_id' => $p->id,
            'employee_id' => $emp->id,
            'consultant_id' => $consultant->id,
            'status' => 'scheduled',
            'scheduled_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('perusahaan.consultants.start-chat', $consultant));

        $response->assertRedirect(route('perusahaan.chat', $existing));
        $this->assertDatabaseCount('perusahaan_sessions', 1);
    }

    public function test_unconsented_employee_redirected(): void
    {
        [$p, $user, $emp, $docUser, $consultant] = $this->fixture(consented: false);

        $response = $this->actingAs($user)
            ->post(route('perusahaan.consultants.start-chat', $consultant));

        $response->assertRedirect(route('perusahaan.employee'));
        $this->assertDatabaseMissing('perusahaan_sessions', ['employee_id' => $emp->id]);
    }

    public function test_consultant_without_active_user_hidden(): void
    {
        [$p, $user, $emp, $docUser, $consultant] = $this->fixture();
        $docUser->update(['is_active' => false]);

        $response = $this->actingAs($user)->get(route('perusahaan.consultants'));

        $response->assertOk();
        $response->assertSee('Tidak ada konsultan');
    }
}
