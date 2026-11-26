<?php

namespace Tests\Feature\Perusahaan;

use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// Audit UX perusahaan item 11: alasan pembatalan opsional di portal perusahaan
// (dulu required — karyawan tidak selalu punya alasan yang mau ditulis).
// Portal sekolah TIDAK diubah (SekolahStudentController tetap required).
class CancelReasonOptionalTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployee(): array
    {
        $roleId = DB::table('roles')->insertGetId(['name' => 'employee', 'created_at' => now(), 'updated_at' => now()]);
        $p = Perusahaan::create([
            'name' => 'PT Cancel', 'brand' => 'CX',
            'sessions_per_employee_month' => 4, 'employee_count' => 5, 'is_active' => true,
        ]);
        $user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'C',
            'username' => 'emp_cx', 'email' => 'emp@cx.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'Emp C', 'email' => 'emp@cx.local', 'consent_at' => now(),
        ]);

        return [$p, $emp, $user];
    }

    public function test_cancel_without_reason_succeeds_on_perusahaan_portal(): void
    {
        [$p, $emp, $user] = $this->makeEmployee();
        $slot = $this->programSlot('perusahaan', $p->id);

        $this->actingAs($user)->post(route('perusahaan.booking.store'), [
            'schedule_id' => $slot->id, 'konsultan_type' => 'psikolog',
        ])->assertRedirect(route('perusahaan.employee'));

        $session = $emp->sessions()->where('schedule_id', $slot->id)->firstOrFail();

        // Tanpa field reason sama sekali — harus sukses, bukan 422.
        $this->actingAs($user)
            ->post(route('perusahaan.booking.cancel', $session))
            ->assertSessionHas('success');

        $this->assertSame('cancelled', $session->fresh()->status);
    }
}
