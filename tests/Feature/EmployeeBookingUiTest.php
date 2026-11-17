<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// Employee booking UI redesign: hero, consultant grid, GET-param filter, sticky aside.
class EmployeeBookingUiTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployee(int $quota = 2): array
    {
        $roleId = DB::table('roles')->insertGetId(['name' => 'employee', 'created_at' => now(), 'updated_at' => now()]);
        $p = Perusahaan::create([
            'name' => 'PT Test', 'brand' => 'TestCo',
            'sessions_per_employee_month' => $quota,
            'employee_count' => 5, 'is_active' => true,
        ]);
        $user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'Test',
            'username' => 'emp_test', 'email' => 'emp@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'Emp Test', 'email' => 'emp@test.local', 'consent_at' => now(),
        ]);

        return [$p, $emp, $user];
    }

    private function makeConsultant(string $firstname, string $lastname, string $categoryName = 'Psikolog'): Consultant
    {
        $user = User::factory()->create(['firstname' => $firstname, 'lastname' => $lastname]);
        $consultant = Consultant::factory()->create(['user_id' => $user->id, 'status' => 'accept', 'is_active' => true]);
        $categoryId = DB::table('categories')->insertGetId(['name' => $categoryName, 'code_category' => Str::random(12), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('consultant_categories')->insert(['consultant_id' => $consultant->id, 'category_id' => $categoryId, 'created_at' => now(), 'updated_at' => now()]);

        return $consultant;
    }

    private function makeSlot(Consultant $consultant, int $tenantId, string $date = '+3 days'): Schedule
    {
        return Schedule::factory()->create([
            'consultant_id' => $consultant->id,
            'perusahaan_id' => $tenantId,
            'klinik_id' => null,
            'started_at' => now()->modify($date),
            'ended_at' => now()->modify($date)->addHour(),
            'status' => 'confirmed',
        ]);
    }

    public function test_booking_form_renders_consultant_grid_and_all_slots_without_params()
    {
        [$p, $emp, $user] = $this->makeEmployee();
        $c1 = $this->makeConsultant('Alpha', 'One', 'Psikolog');
        $c2 = $this->makeConsultant('Beta', 'Two', 'Gizi');
        ConsultantPool::create(['perusahaan_id' => $p->id, 'consultant_id' => $c1->id]);
        ConsultantPool::create(['perusahaan_id' => $p->id, 'consultant_id' => $c2->id]);
        $slot1 = $this->makeSlot($c1, $p->id);
        $slot2 = $this->makeSlot($c2, $p->id);

        $response = $this->actingAs($user)->get(route('perusahaan.booking'));

        $response->assertOk();
        $response->assertSee('Alpha One');
        $response->assertSee('Beta Two');
        $response->assertSee('name="schedule_id"', false);
        // Aside shows all slots when no consultant selected
        $response->assertSee('slot-' . $slot1->started_at->format('Y-m-d'));
        $response->assertSee('slot-' . $slot2->started_at->format('Y-m-d'));
    }

    public function test_booking_form_filters_slots_by_consultant_id_and_sets_hidden_type()
    {
        [$p, $emp, $user] = $this->makeEmployee();
        $c1 = $this->makeConsultant('Alpha', 'One', 'Psikolog');
        $c2 = $this->makeConsultant('Beta', 'Two', 'Gizi');
        ConsultantPool::create(['perusahaan_id' => $p->id, 'consultant_id' => $c1->id]);
        ConsultantPool::create(['perusahaan_id' => $p->id, 'consultant_id' => $c2->id]);
        $slot1 = $this->makeSlot($c1, $p->id);
        $slot2 = $this->makeSlot($c2, $p->id);

        $response = $this->actingAs($user)->get(route('perusahaan.booking', ['consultant_id' => $c1->id]));

        $response->assertOk();
        $response->assertSee('Alpha One');
        $response->assertSee('name="schedule_id"', false);
        $response->assertSee('value="' . $slot1->id . '"', false);
        $response->assertDontSee('value="' . $slot2->id . '"', false);
        $response->assertSee('name="konsultan_type"', false);
        $response->assertSee('value="psikolog"', false);
    }

    public function test_booking_form_rejects_invalid_consultant_id()
    {
        [$p, $emp, $user] = $this->makeEmployee();

        $response = $this->actingAs($user)->get(route('perusahaan.booking', ['consultant_id' => 999999]));

        $response->assertNotFound();
    }

    public function test_booking_form_filters_consultant_grid_by_q()
    {
        [$p, $emp, $user] = $this->makeEmployee();
        $c1 = $this->makeConsultant('Alpha', 'One', 'Psikolog');
        $c2 = $this->makeConsultant('Beta', 'Two', 'Gizi');
        ConsultantPool::create(['perusahaan_id' => $p->id, 'consultant_id' => $c1->id]);
        ConsultantPool::create(['perusahaan_id' => $p->id, 'consultant_id' => $c2->id]);
        $this->makeSlot($c1, $p->id);
        $this->makeSlot($c2, $p->id);

        $response = $this->actingAs($user)->get(route('perusahaan.booking', ['q' => 'Alpha']));

        $response->assertOk();
        $response->assertSee('Alpha One');
        $response->assertDontSee('Beta Two');
    }

    public function test_booking_store_still_works_after_ui_redesign()
    {
        [$p, $emp, $user] = $this->makeEmployee(2);
        $c = $this->makeConsultant('Alpha', 'One', 'Psikolog');
        ConsultantPool::create(['perusahaan_id' => $p->id, 'consultant_id' => $c->id]);
        $slot = $this->makeSlot($c, $p->id);

        $response = $this->actingAs($user)->post(route('perusahaan.booking.store'), [
            'schedule_id' => $slot->id,
            'konsultan_type' => 'psikolog',
        ]);

        $response->assertRedirect(route('perusahaan.employee'));
        $this->assertEquals(1, $emp->sessions()->count());
        $this->assertEquals('psikolog', $emp->sessions()->first()->konsultan_type);
    }
}
