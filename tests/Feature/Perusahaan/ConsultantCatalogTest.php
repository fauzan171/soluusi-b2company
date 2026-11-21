<?php

namespace Tests\Feature\Perusahaan;

use App\Models\Consultant;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Katalog + detail + request konsultan HR, dan halaman kesejahteraan detail.
 */
class ConsultantCatalogTest extends TestCase
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

    /** [$perusahaan, $hrUser, $consultant] */
    private function fixture(): array
    {
        $p = Perusahaan::create([
            'name' => 'PT Test', 'brand' => 'TestCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $hrUser = $this->user('hr@test.local', 'hr_admin');
        PerusahaanAdmin::create(['perusahaan_id' => $p->id, 'user_id' => $hrUser->id, 'name' => 'HR']);
        $docUser = $this->user('doc@test.local', 'consultant');
        $consultant = Consultant::create([
            'user_id' => $docUser->id, 'uuid' => (string) Str::uuid(),
            'about' => 'Psikolog test konselor katalog', 'profit_sharing' => 0,
            'status' => Consultant::ACCEPT, 'is_active' => true,
        ]);

        return [$p, $hrUser, $consultant];
    }

    public function test_katalog_dan_detail_bisa_diakses_hr(): void
    {
        [$p, $hrUser, $consultant] = $this->fixture();

        $this->actingAs($hrUser)->get(route('perusahaan.consultants.catalog'))->assertOk();
        $this->actingAs($hrUser)->get(route('perusahaan.consultants.catalog', ['q' => 'test']))->assertOk();
        $this->actingAs($hrUser)
            ->get(route('perusahaan.consultants.detail', $consultant))
            ->assertOk()
            ->assertSee('Psikolog test konselor katalog');
    }

    public function test_katalog_tertolak_untuk_employee(): void
    {
        $this->fixture();
        $empUser = $this->user('emp2@test.local', 'employee');

        $this->actingAs($empUser)->get(route('perusahaan.consultants.catalog'))->assertForbidden();
    }

    public function test_request_konsultan_tersimpan_pending(): void
    {
        [$p, $hrUser, $consultant] = $this->fixture();

        $this->actingAs($hrUser)
            ->post(route('perusahaan.consultants.request', $consultant), ['note' => 'Tim shift malam'])
            ->assertRedirect();

        $this->assertDatabaseHas('consultant_requests', [
            'perusahaan_id' => $p->id,
            'consultant_id' => $consultant->id,
            'status' => 'pending',
            'note' => 'Tim shift malam',
        ]);
    }

    public function test_kesejahteraan_page_render(): void
    {
        [, $hrUser] = $this->fixture();

        $this->actingAs($hrUser)
            ->get(route('perusahaan.wellbeing'))
            ->assertOk()
            ->assertSee('Kesejahteraan agregat');
    }

    public function test_dashboard_card_link_ke_kesejahteraan_dan_katalog(): void
    {
        [, $hrUser] = $this->fixture();

        $res = $this->actingAs($hrUser)->get(route('perusahaan.dashboard'))->assertOk();
        $res->assertSee(route('perusahaan.wellbeing'));
        $res->assertSee(route('perusahaan.consultants.catalog'));
    }
}
