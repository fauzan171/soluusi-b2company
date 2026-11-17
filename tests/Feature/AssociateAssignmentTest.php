<?php

namespace Tests\Feature;

use App\Models\AdminAction;
use App\Models\Klinik\Klinik;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Role;
use App\Models\Sekolah\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * C4: assign associate ke tenant 3 vertikal — sukses + audit, non-associate → 422,
 * re-assign mencatat previous_associate_id, unassign (kosong) melepas.
 */
class AssociateAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        return User::factory()->create(['role_id' => $role->id]);
    }

    private function associate(): User
    {
        $role = Role::firstOrCreate(['name' => 'associate']);
        return User::factory()->create(['role_id' => $role->id]);
    }

    private function nonAssociate(): User
    {
        $role = Role::firstOrCreate(['name' => 'employee']);
        return User::factory()->create(['role_id' => $role->id]);
    }

    public static function verticalProvider(): array
    {
        return [
            'klinik' => ['kliniks', fn () => Klinik::create(['name' => 'Klinik Uji', 'brand' => 'KU'])],
            'perusahaan' => ['perusahaans', fn () => Perusahaan::create(['name' => 'PT Uji', 'brand' => 'PU', 'pepm_rate' => 15000])],
            'sekolah' => ['sekolahs', fn () => Sekolah::create(['name' => 'SMA Uji', 'brand' => 'SU'])],
        ];
    }

    /** @dataProvider verticalProvider */
    public function test_assign_associate_sukses_dan_teraudit(string $slug, callable $make)
    {
        $tenant = $make();
        $associate = $this->associate();

        $this->actingAs($this->admin())
            ->post("/admin/tenants/{$slug}/{$tenant->id}/associate", ['associate_id' => $associate->id])
            ->assertRedirect();

        $this->assertSame($associate->id, $tenant->refresh()->associate_id);
        $this->assertDatabaseHas('admin_actions', [
            'target_id' => $tenant->id,
        ]);
        $action = AdminAction::where('target_id', $tenant->id)->latest('id')->first();
        $this->assertStringContainsString('assign-associate', $action->action);
        $this->assertSame($associate->id, $action->payload_json['associate_id']);
        $this->assertNull($action->payload_json['previous_associate_id']);
    }

    /** @dataProvider verticalProvider */
    public function test_user_non_associate_ditolak_422(string $slug, callable $make)
    {
        $tenant = $make();
        $bukan = $this->nonAssociate();

        $this->actingAs($this->admin())
            ->post("/admin/tenants/{$slug}/{$tenant->id}/associate", ['associate_id' => $bukan->id])
            ->assertStatus(422);

        $this->assertNull($tenant->refresh()->associate_id);
        $this->assertDatabaseMissing('admin_actions', ['target_id' => $tenant->id]);
    }

    public function test_reassign_mencatat_previous_dan_unassign_melepas()
    {
        $k = Klinik::create(['name' => 'Klinik Uji', 'brand' => 'KU']);
        $a1 = $this->associate();
        $a2 = $this->associate();
        $admin = $this->admin();

        $this->actingAs($admin)->post("/admin/tenants/kliniks/{$k->id}/associate", ['associate_id' => $a1->id]);
        $this->actingAs($admin)->post("/admin/tenants/kliniks/{$k->id}/associate", ['associate_id' => $a2->id]);
        $this->assertSame($a2->id, $k->refresh()->associate_id);

        $action = AdminAction::where('target_id', $k->id)->latest('id')->first();
        $this->assertSame($a1->id, $action->payload_json['previous_associate_id']);

        // unassign
        $this->actingAs($admin)->post("/admin/tenants/kliniks/{$k->id}/associate", ['associate_id' => '']);
        $this->assertNull($k->refresh()->associate_id);
        $action = AdminAction::where('target_id', $k->id)->latest('id')->first();
        $this->assertNull($action->payload_json['associate_id']);
        $this->assertSame($a2->id, $action->payload_json['previous_associate_id']);
    }

    public function test_associate_sendiri_tidak_bisa_assign()
    {
        $k = Klinik::create(['name' => 'Klinik Uji', 'brand' => 'KU']);
        $a = $this->associate();

        $this->actingAs($a)
            ->post("/admin/tenants/kliniks/{$k->id}/associate", ['associate_id' => $a->id])
            ->assertStatus(403);
    }
}
