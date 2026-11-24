<?php

namespace Tests\Feature\Perusahaan;

use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// Audit UX perusahaan item 4: modal "Ubah jadwal" sebelumnya menampilkan SEMUA
// slot tenant — termasuk slot milik konsultan lain. Reschedule harus hanya
// menawarkan slot milik konsultan sesi yang sama (consultation_type ikut
// dipertahankan oleh BookingService::reschedule; slot konsultan sama sudah
// terbukti mendukung tipe tersebut dari sesi awalnya).
class RescheduleModalSlotsTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployee(): array
    {
        $roleId = DB::table('roles')->insertGetId(['name' => 'employee', 'created_at' => now(), 'updated_at' => now()]);
        $p = Perusahaan::create([
            'name' => 'PT Modal', 'brand' => 'MD',
            'sessions_per_employee_month' => 4, 'employee_count' => 5, 'is_active' => true,
        ]);
        $user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Emp', 'lastname' => 'M',
            'username' => 'emp_md', 'email' => 'emp@md.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
        $emp = PerusahaanEmployee::create([
            'perusahaan_id' => $p->id, 'user_id' => $user->id,
            'name' => 'Emp M', 'email' => 'emp@md.local', 'consent_at' => now(),
        ]);

        return [$p, $emp, $user];
    }

    public function test_reschedule_modal_only_offers_slots_of_sessions_consultant(): void
    {
        [$p, $emp, $user] = $this->makeEmployee();
        // Slot milik konsultan A (sesi dibooking di sini) + konsultan B (harus
        // TIDAK muncul di opsi reschedule).
        $slotA = $this->programSlot('perusahaan', $p->id);
        $otherCand = $this->programSlot('perusahaan', $p->id);

        $this->actingAs($user)->post(route('perusahaan.booking.store'), [
            'schedule_id' => $slotA->id, 'konsultan_type' => 'psikolog',
        ])->assertRedirect(route('perusahaan.employee'));

        $session = $emp->sessions()->where('schedule_id', $slotA->id)->firstOrFail();

        // Slot cadangan konsultan A (selain slot yang sudah di-claim sesi aktif) —
        // inilah satu-satunya opsi reschedule yang sah.
        $slotA2 = $slotA->replicate();
        $slotA2->started_at = now()->addDays(6);
        $slotA2->ended_at = $slotA2->started_at->copy()->addHour();
        $slotA2->save();

        $html = $this->actingAs($user)->get(route('perusahaan.employee'))
            ->assertOk()
            ->getContent();

        // Slots for the modal are shipped as JSON keyed by session id (Alpine
        // renders the options client-side). Consultant A's free slot must be
        // present; B's slot must not ship at all. Assert on unicode-safe
        // substrings (JSON escapes "·" as ·).
        $labelA = $slotA2->started_at->translatedFormat('d M Y H.i');
        $nameA = $slotA2->consultant->user->firstname;
        $nameB = $otherCand->consultant->user->firstname;
        $this->assertStringContainsString($labelA, $html);
        $this->assertStringContainsString($nameA, $html);
        $this->assertStringNotContainsString($nameB, $html);
    }
}
