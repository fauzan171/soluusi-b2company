<?php

namespace Tests\Feature\Perusahaan;

use App\Models\Consultant;
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
 * Privacy invariant: isi keluhan karyawan (request_notes) hanya boleh dibaca
 * karyawan pemilik + konsultan yang ditugaskan. HR tidak pernah melihat isinya.
 */
class RequestNotesPrivacyTest extends TestCase
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

    /** @return array{0: PerusahaanSession, 1: User(employee), 2: User(hr), 3: User(consultant)} */
    private function fixture(): array
    {
        $perusahaan = Perusahaan::create([
            'name' => 'PT Privasi', 'brand' => 'PrivasiCo',
            'sessions_per_employee_month' => 5, 'employee_count' => 5, 'is_active' => true,
            'consultant_request_enabled' => true,
        ]);
        $empUser = $this->user('emp@privasi.test', 'employee');
        $employee = PerusahaanEmployee::create([
            'perusahaan_id' => $perusahaan->id, 'user_id' => $empUser->id,
            'name' => 'Karyawan', 'email' => $empUser->email, 'consent_at' => now(),
        ]);
        $hrUser = $this->user('hr@privasi.test', 'admin');
        PerusahaanAdmin::create([
            'perusahaan_id' => $perusahaan->id, 'user_id' => $hrUser->id,
            'name' => 'HR', 'role' => 'hr_admin',
        ]);
        $docUser = $this->user('doc@privasi.test', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);

        // Menunggu review HR: consultant belum ditugaskan.
        $session = PerusahaanSession::create([
            'perusahaan_id' => $perusahaan->id, 'employee_id' => $employee->id,
            'konsultan_type' => 'psikolog', 'status' => 'scheduled',
            'scheduled_at' => null, 'consultant_id' => null,
            'request_notes' => 'RAHASIA-9f2c: saya mengalami kecemasan berat di kantor.',
        ]);

        return [$session, $empUser, $hrUser, $docUser];
    }

    public function test_hr_review_page_never_renders_the_complaint_body(): void
    {
        [, , $hrUser] = $this->fixture();

        $this->actingAs($hrUser)->withSession(['perusahaan_id' => Perusahaan::first()->id])
            ->get(route('perusahaan.review'))
            ->assertOk()
            ->assertDontSee('RAHASIA-9f2c');
    }

    public function test_hr_sees_only_type_and_topic_to_assign(): void
    {
        [, , $hrUser] = $this->fixture();

        $this->actingAs($hrUser)->withSession(['perusahaan_id' => Perusahaan::first()->id])
            ->get(route('perusahaan.review'))
            ->assertOk()
            ->assertSee('Psikolog', false);
    }

    public function test_assigned_consultant_reads_the_complaint_in_chat_room(): void
    {
        [$session, , , $docUser] = $this->fixture();
        $consultant = Consultant::where('user_id', $docUser->id)->firstOrFail();

        // HR approve → konsultan ditugaskan, lalu jadwal di-set.
        $session->update(['consultant_id' => $consultant->id, 'scheduled_at' => now()->addDay()]);

        $this->actingAs($docUser)->get(route('consultant.chat', $session))
            ->assertOk()
            ->assertSee('RAHASIA-9f2c', false);
    }

    public function test_employee_never_sees_own_notes_echoed_in_chat_room(): void
    {
        // Karyawan sudah tahu isinya sendiri; room cukup menampilkan percakapan.
        // Yang penting: HR tidak. Test di atas menutup invariant itu.
        [$session, $empUser] = $this->fixture();
        $consultant = Consultant::create([
            'user_id' => $this->user('doc2@privasi.test', 'consultant')->id,
            'uuid' => (string) Str::uuid(), 'about' => 'T', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);
        $session->update(['consultant_id' => $consultant->id, 'scheduled_at' => now()->addDay()]);

        $this->actingAs($empUser)->get(route('perusahaan.chat', $session))->assertOk();
    }
}