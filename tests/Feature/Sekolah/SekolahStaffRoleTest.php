<?php

namespace Tests\Feature\Sekolah;

use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahAdmin;
use App\Models\Sekolah\SekolahStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// Staff sekolah (guru/karyawan) self-service: role baru `sekolah_staff`,
// portal sendiri `sekolah.staff`. Tetap di dalam tenant Sekolah yang sama.
class SekolahStaffRoleTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email, string $role): User
    {
        $roleId = DB::table('roles')->where('name', $role)->value('id')
            ?? DB::table('roles')->insertGetId(['name' => $role, 'created_at' => now(), 'updated_at' => now()]);

        return User::create([
            'uuid' => Str::uuid(), 'firstname' => 'U', 'lastname' => $role,
            'username' => Str::slug($email).'-'.Str::random(4), 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
    }

    private function sekolah(string $slug = 'a'): Sekolah
    {
        return Sekolah::create([
            'name' => "SMA {$slug}", 'brand' => "SMA {$slug}",
            'tingkat' => 'SMA', 'student_count' => 10,
            'sessions_per_student_month' => 2, 'is_active' => true,
        ]);
    }

    /** Staff = SekolahAdmin(role sekolah_staff) untuk tenant + SekolahStudent(type staff) sebagai person row. */
    private function staff(Sekolah $sekolah, string $email = 'staff@test.local'): array
    {
        $user = $this->user($email, 'sekolah_staff');
        SekolahAdmin::create([
            'sekolah_id' => $sekolah->id, 'user_id' => $user->id, 'role' => 'sekolah_staff',
        ]);
        $person = SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'user_id' => $user->id,
            'name' => 'Staf Test', 'email' => $email, 'type' => 'staff',
            'consent_at' => now(),
        ]);

        return [$user, $person];
    }

    public function test_sekolah_staff_role_is_seeded(): void
    {
        $this->seed();

        $this->assertDatabaseHas('roles', ['name' => 'sekolah_staff']);
    }

    public function test_sekolah_staff_user_can_open_staff_portal(): void
    {
        $sekolah = $this->sekolah();
        [$user] = $this->staff($sekolah);

        $this->actingAs($user)->get(route('sekolah.staff'))
            ->assertOk()
            ->assertViewIs('sekolah.staff');
    }

    public function test_student_cannot_open_staff_portal(): void
    {
        $sekolah = $this->sekolah();
        $user = $this->user('siswa@test.local', 'student');
        SekolahStudent::create([
            'sekolah_id' => $sekolah->id, 'user_id' => $user->id,
            'name' => 'Siswa Test', 'email' => 'siswa@test.local',
            'parent_consent_at' => now(),
        ]);

        $this->actingAs($user)->get(route('sekolah.staff'))->assertForbidden();
    }

    public function test_guest_redirected_to_login(): void
    {
        $this->get(route('sekolah.staff'))->assertRedirect(route('login'));
    }

    public function test_staff_login_redirects_to_staff_portal(): void
    {
        $sekolah = $this->sekolah();
        [$user] = $this->staff($sekolah);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('sekolah.staff'));

        $this->assertAuthenticatedAs($user);
    }
}