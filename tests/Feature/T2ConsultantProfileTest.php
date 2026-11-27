<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * T2 — profil konsultan penuh di dashboard HR & karyawan (pool-scoped):
 * photo (dengan guard scheme javascript:), headline, pendidikan,
 * sertifikasi, skills. Katalog HR default hanya konsultan pool perusahaan.
 */
class T2ConsultantProfileTest extends TestCase
{
    use RefreshDatabase;

    private Perusahaan $perusahaan;
    private User $hr;
    private User $employee;
    private Consultant $inPool;
    private Consultant $outPool;

    protected function setUp(): void
    {
        parent::setUp();

        $this->perusahaan = Perusahaan::create([
            'name' => 'PT T2', 'brand' => 'T2Co', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);

        $this->hr = $this->user('hr@t2.local', 'hr_admin');
        PerusahaanAdmin::create(['perusahaan_id' => $this->perusahaan->id, 'user_id' => $this->hr->id, 'name' => 'HR']);
        $this->employee = $this->user('emp@t2.local', 'employee');
        PerusahaanEmployee::create([
            'perusahaan_id' => $this->perusahaan->id, 'user_id' => $this->employee->id,
            'name' => 'Emp', 'email' => 'emp@t2.local', 'consent_at' => now(),
        ]);

        $this->inPool = $this->consultant('Sinta');
        ConsultantPool::create(['perusahaan_id' => $this->perusahaan->id, 'consultant_id' => $this->inPool->id]);
        // Konsultan accepted tapi tidak di pool perusahaan ini.
        $this->outPool = $this->consultant('Eksternal');
    }

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

    private function consultant(string $name): Consultant
    {
        $user = $this->user(Str::slug($name).'@t2.local', 'consultant');
        $user->update(['firstname' => $name, 'lastname' => '']);

        return Consultant::create([
            'user_id' => $user->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Psikolog klinis test', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
            'photo_url' => 'https://b2c.example/storage/avatars/t2.png',
            'headline' => 'Anak & remaja',
            'profile' => [
                'education' => [['institution' => 'Universitas Test', 'degree' => 'S2', 'department' => 'Psikologi Klinis', 'start' => '2010-01-01', 'end' => '2012-01-01']],
                'certificates' => [['name' => 'HIPKI', 'number' => '123-ABC', 'year' => 2015]],
                'skills' => ['Cognitive Behavioral Therapy'],
            ],
        ]);
    }

    // Employee list + detail

    public function test_employee_list_shows_photo_headline_education(): void
    {
        $this->actingAs($this->employee)
            ->get(route('perusahaan.consultants'))
            ->assertOk()
            ->assertSee('https://b2c.example/storage/avatars/t2.png')
            ->assertSee('Anak & remaja')
            ->assertSee('Universitas Test')
            ->assertSee('HIPKI')
            ->assertSee('Cognitive Behavioral Therapy');
    }

    public function test_employee_detail_shows_photo_headline_education(): void
    {
        $this->actingAs($this->employee)
            ->get(route('perusahaan.consultants.show', $this->inPool))
            ->assertOk()
            ->assertSee('https://b2c.example/storage/avatars/t2.png')
            ->assertSee('Anak & remaja')
            ->assertSee('Universitas Test')
            ->assertSee('HIPKI');
    }

    // HR pool + catalog + detail

    public function test_hr_pool_shows_photo_headline_education(): void
    {
        $this->actingAs($this->hr)
            ->get(route('perusahaan.consultants.pool'))
            ->assertOk()
            ->assertSee('https://b2c.example/storage/avatars/t2.png')
            ->assertSee('Anak & remaja')
            ->assertSee('Universitas Test')
            ->assertSee('HIPKI')
            ->assertSee('Cognitive Behavioral Therapy');
    }

    public function test_hr_catalog_defaults_to_pool_only(): void
    {
        $this->actingAs($this->hr)
            ->get(route('perusahaan.consultants.catalog'))
            ->assertOk()
            ->assertSee('Sinta')
            ->assertDontSee('Eksternal')
            ->assertSee('https://b2c.example/storage/avatars/t2.png')
            ->assertSee('Anak & remaja');
    }

    public function test_hr_catalog_scope_all_shows_non_pool_consultants(): void
    {
        $this->actingAs($this->hr)
            ->get(route('perusahaan.consultants.catalog', ['scope' => 'all']))
            ->assertOk()
            ->assertSee('Sinta')
            ->assertSee('Eksternal');
    }

    public function test_hr_detail_shows_full_profile(): void
    {
        $this->actingAs($this->hr)
            ->get(route('perusahaan.consultants.detail', $this->inPool))
            ->assertOk()
            ->assertSee('Anak & remaja')
            ->assertSee('Universitas Test')
            ->assertSee('HIPKI')
            ->assertSee('Cognitive Behavioral Therapy');
    }

    // XSS guard

    public function test_javascript_photo_url_is_not_rendered(): void
    {
        $this->inPool->update(['photo_url' => 'javascript:alert(1)']);

        // Employee list: tidak render attribute src berbahaya (fallback inisial).
        $this->actingAs($this->employee)
            ->get(route('perusahaan.consultants'))
            ->assertOk()
            ->assertDontSee('javascript:alert(1)');

        $this->actingAs($this->hr)
            ->get(route('perusahaan.consultants.catalog'))
            ->assertOk()
            ->assertDontSee('javascript:alert(1)');
    }
}
