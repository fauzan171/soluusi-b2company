<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * ONB.1 — departemen milik satu perusahaan.
 * Tanpa scoping ini dua perusahaan berbeda berbagi divisi bernama "Finance",
 * sehingga "pilih divisi" tidak bisa dijamin benar.
 */
class DepartementTenantTest extends TestCase
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

    private function perusahaan(string $name): Perusahaan
    {
        return Perusahaan::create([
            'name' => $name, 'brand' => $name, 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true, 'status' => 'active',
        ]);
    }

    private function hr(Perusahaan $p): User
    {
        $user = $this->user('hr-' . $p->id . '@uji.local', 'hr_admin');
        PerusahaanAdmin::create(['perusahaan_id' => $p->id, 'user_id' => $user->id]);

        return $user;
    }

    public function test_division_of_other_tenant_is_not_in_dropdown(): void
    {
        $a = $this->perusahaan('PT A');
        $b = $this->perusahaan('PT B');
        Departement::create(['name' => 'Finance', 'perusahaan_id' => $a->id]);
        Departement::create(['name' => 'Legal B', 'perusahaan_id' => $b->id]);

        $this->actingAs($this->hr($a))
            ->get(route('perusahaan.employees'))
            ->assertOk()
            ->assertSee('Finance')
            ->assertDontSee('Legal B');
    }

    public function test_posting_division_from_another_tenant_is_rejected(): void
    {
        $a = $this->perusahaan('PT A');
        $b = $this->perusahaan('PT B');
        $foreign = Departement::create(['name' => 'Finance B', 'perusahaan_id' => $b->id]);

        $this->actingAs($this->hr($a))
            ->post(route('perusahaan.employees.store'), [
                'name' => 'Karyawan A',
                'email' => 'karyawan.a@uji.local',
                'departement_id' => $foreign->id,
            ])
            ->assertStatus(422);

        $this->assertSame(0, PerusahaanEmployee::where('email', 'karyawan.a@uji.local')->count());
    }

    public function test_posting_own_division_is_saved(): void
    {
        $a = $this->perusahaan('PT A');
        $own = Departement::create(['name' => 'Engineering A', 'perusahaan_id' => $a->id]);

        $this->actingAs($this->hr($a))
            ->post(route('perusahaan.employees.store'), [
                'name' => 'Karyawan A',
                'email' => 'karyawan.a@uji.local',
                'departement_id' => $own->id,
            ])
            ->assertRedirect();

        $employee = PerusahaanEmployee::where('email', 'karyawan.a@uji.local')->firstOrFail();
        $this->assertSame($own->id, $employee->departement_id);
    }

    public function test_posting_without_division_still_works(): void
    {
        $a = $this->perusahaan('PT A');

        $this->actingAs($this->hr($a))
            ->post(route('perusahaan.employees.store'), [
                'name' => 'Karyawan Tanpa Divisi',
                'email' => 'tanpa.divisi@uji.local',
            ])
            ->assertRedirect();

        $this->assertNull(
            PerusahaanEmployee::where('email', 'tanpa.divisi@uji.local')->firstOrFail()->departement_id
        );
    }

    public function test_for_perusahaan_returns_own_plus_legacy_rows_sorted(): void
    {
        $a = $this->perusahaan('PT A');
        $b = $this->perusahaan('PT B');
        Departement::create(['name' => 'Zeta', 'perusahaan_id' => $a->id]);
        Departement::create(['name' => 'Alpha', 'perusahaan_id' => $a->id]);
        Departement::create(['name' => 'Mid', 'perusahaan_id' => $b->id]);
        Departement::create(['name' => 'Legacy', 'perusahaan_id' => null]);

        $names = Departement::forPerusahaan($a->id)->pluck('name')->all();

        $this->assertSame(['Alpha', 'Legacy', 'Zeta'], $names);
    }

    public function test_seeder_is_idempotent_per_tenant(): void
    {
        $this->seed(\Database\Seeders\PerusahaanSeeder::class);
        $first = Departement::count();
        $this->seed(\Database\Seeders\PerusahaanSeeder::class);

        $this->assertSame($first, Departement::count());
        $this->assertGreaterThan(0, $first);
    }

    public function test_seeder_scopes_divisions_to_each_company(): void
    {
        $this->seed(\Database\Seeders\PerusahaanSeeder::class);

        $companies = Perusahaan::all();
        $this->assertGreaterThanOrEqual(2, $companies->count());

        foreach ($companies as $p) {
            $this->assertGreaterThan(0, Departement::where('perusahaan_id', $p->id)->count());
        }
        // Tidak ada divisi yang dipakai bersama dua perusahaan.
        $this->assertSame(0, Departement::whereNull('perusahaan_id')->count());
    }
}