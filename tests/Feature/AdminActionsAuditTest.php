<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\ConsultantPackage;
use App\Models\ConsultationSession;
use App\Models\Klinik\HapPervorma;
use App\Models\Klinik\Klinik;
use App\Models\Klinik\SplitSettlement;
use App\Models\ConsultantPool;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\Sekolah\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Enam aksi admin (pay pervorma, approve klinik, aktivasi perusahaan/sekolah,
 * settle split, verify sesi) + preview karyawan harus tercatat di admin_actions.
 * Tanpa ini tidak ada audit trail atas perubahan status penting.
 */
class AdminActionsAuditTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $roleModel = Role::firstOrCreate(['name' => 'admin']);

        return User::factory()->create(['role_id' => $roleModel->id]);
    }

    private function nonAdmin(): User
    {
        $roleModel = Role::firstOrCreate(['name' => 'klinik_admin']);

        return User::factory()->create(['role_id' => $roleModel->id]);
    }

    private function klinik(): Klinik
    {
        return Klinik::create(['name' => 'Klinik A', 'brand' => 'Klinik A', 'total_rate' => 1_000_000]);
    }

    private function makeSession(Klinik $klinik): ConsultationSession
    {
        $konsultan = Consultant::factory()->create(['status' => 'accept']);
        $pkg = ConsultantPackage::factory()->create(['consultant_id' => $konsultan->id, 'price' => 250_000]);
        $sched = Schedule::factory()->create(['consultant_id' => $konsultan->id]);

        return ConsultationSession::create([
            'schedule_id' => $sched->id,
            'consultant_id' => $konsultan->id,
            'consultant_package_id' => $pkg->id,
            'klinik_id' => $klinik->id,
            'order_number' => 'AUD-1',
            'payment' => '0',
            'amount' => 300_000,
            'order_at' => now(),
            'payment_status' => 'settlement',
            'status' => 'ongoing',
            'konsultan_done_at' => now(),
        ]);
    }

    public function test_pay_pervorma_mencatat_admin_action()
    {
        $klinik = $this->klinik();
        $pervorma = HapPervorma::create([
            'klinik_id' => $klinik->id,
            'stage' => 1,
            'amount' => 250_000,
            'status' => 'due',
            'due_at' => now()->addWeek(),
        ]);

        $this->post(route('admin.pervormas.pay', ['vertical' => 'hap', 'pervorma' => $pervorma->id]))->assertRedirect(route('loginIndex'));
        $this->actingAs($this->nonAdmin())->post(route('admin.pervormas.pay', ['vertical' => 'hap', 'pervorma' => $pervorma->id]))->assertForbidden();

        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.pervormas.pay', ['vertical' => 'hap', 'pervorma' => $pervorma->id]))
            ->assertRedirect()->assertSessionHas('success');

        $this->assertSame('paid', $pervorma->fresh()->status);
        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $admin->id,
            'action' => 'pervorma.pay',
            'target_id' => $pervorma->id,
        ]);
    }

    public function test_approve_klinik_mencatat_admin_action()
    {
        $klinik = $this->klinik();

        $this->actingAs($this->nonAdmin())->post(route('admin.kliniks.approve', $klinik))->assertForbidden();

        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.kliniks.approve', $klinik))
            ->assertRedirect()->assertSessionHas('success');

        $klinik->refresh();
        $this->assertTrue($klinik->is_active);
        $this->assertNotNull($klinik->approved_at);
        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $admin->id,
            'action' => 'klinik.approve',
            'target_id' => $klinik->id,
        ]);
    }

    public function test_activate_perusahaan_mencatat_admin_action()
    {
        $perusahaan = Perusahaan::create([
            'name' => 'PT A', 'brand' => 'BrandA', 'is_active' => false,
        ]);

        $this->actingAs($this->nonAdmin())->post(route('admin.perusahaans.activate', $perusahaan))->assertForbidden();

        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.perusahaans.activate', $perusahaan))
            ->assertRedirect()->assertSessionHas('success');

        $this->assertTrue($perusahaan->fresh()->is_active);
        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $admin->id,
            'action' => 'perusahaan.activate',
            'target_id' => $perusahaan->id,
        ]);
    }

    public function test_activate_sekolah_mencatat_admin_action()
    {
        $sekolah = Sekolah::create([
            'name' => 'SMA A', 'brand' => 'SMAA', 'is_active' => false,
        ]);

        $this->actingAs($this->nonAdmin())->post(route('admin.sekolahs.activate', $sekolah))->assertForbidden();

        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.sekolahs.activate', $sekolah))
            ->assertRedirect()->assertSessionHas('success');

        $this->assertTrue($sekolah->fresh()->is_active);
        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $admin->id,
            'action' => 'sekolah.activate',
            'target_id' => $sekolah->id,
        ]);
    }

    public function test_settle_split_mencatat_admin_action()
    {
        $klinik = $this->klinik();
        $session = $this->makeSession($klinik);
        $split = SplitSettlement::create([
            'session_id' => $session->id,
            'klinik_id' => $klinik->id,
            'net_amount' => 250_000,
            'klinik_margin' => 50_000,
            'status' => 'pending',
        ]);

        $this->actingAs($this->nonAdmin())->post(route('admin.splits.settle', $split))->assertForbidden();

        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.splits.settle', $split))
            ->assertRedirect()->assertSessionHas('success');

        $split->refresh();
        $this->assertSame('settled', $split->status);
        $this->assertNotNull($split->settled_at);
        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $admin->id,
            'action' => 'split.settle',
            'target_id' => $split->id,
        ]);
    }

    public function test_verify_session_mencatat_admin_action()
    {
        $klinik = $this->klinik();
        $session = $this->makeSession($klinik);

        $this->actingAs($this->nonAdmin())->post(route('admin.sessions.verify', $session))->assertForbidden();

        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.sessions.verify', $session))
            ->assertRedirect()->assertSessionHas('success');

        $session->refresh();
        $this->assertNotNull($session->customer_confirmed_at);
        $this->assertFalse($session->needs_ops_review);
        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $admin->id,
            'action' => 'session.verify',
            'target_id' => $session->id,
        ]);
    }

    /** [$perusahaan, $hrUser, $session, $consultant] — pending HR review. */
    private function hrReviewFixture(): array
    {
        $perusahaan = Perusahaan::create([
            'name' => 'PT Audit', 'brand' => 'AuditCo', 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true,
        ]);
        $hrRole = Role::firstOrCreate(['name' => 'hr_admin']);
        $hrUser = User::factory()->create(['role_id' => $hrRole->id]);
        PerusahaanAdmin::create(['perusahaan_id' => $perusahaan->id, 'user_id' => $hrUser->id, 'name' => 'HR']);

        $empRole = Role::firstOrCreate(['name' => 'employee']);
        $empUser = User::factory()->create(['role_id' => $empRole->id]);
        $employee = PerusahaanEmployee::create([
            'perusahaan_id' => $perusahaan->id, 'user_id' => $empUser->id,
            'name' => 'Emp', 'email' => 'emp-audit@test.local', 'consent_at' => now(),
        ]);

        $consRole = Role::firstOrCreate(['name' => 'consultant']);
        $consUser = User::factory()->create(['role_id' => $consRole->id]);
        $consultant = Consultant::factory()->create([
            'user_id' => $consUser->id, 'status' => 'accept',
        ]);
        ConsultantPool::create(['perusahaan_id' => $perusahaan->id, 'consultant_id' => $consultant->id]);

        $session = PerusahaanSession::create([
            'perusahaan_id' => $perusahaan->id, 'employee_id' => $employee->id,
            'consultant_id' => null, 'konsultan_type' => 'psikolog',
            'status' => 'scheduled', 'scheduled_at' => null,
        ]);

        return [$perusahaan, $hrUser, $session, $consultant];
    }

    public function test_hr_approve_mencatat_admin_action()
    {
        [$perusahaan, $hrUser, $session, $consultant] = $this->hrReviewFixture();

        $this->actingAs($hrUser)->post(route('perusahaan.review.approve', $session), [
            'consultant_id' => $consultant->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $hrUser->id,
            'action' => 'hr_review.approve',
            'target_id' => $session->id,
        ]);
        $row = \App\Models\AdminAction::where('action', 'hr_review.approve')->latest('id')->first();
        $this->assertSame($consultant->id, $row->payload_json['consultant_id'] ?? null);
    }

    public function test_hr_reject_mencatat_admin_action()
    {
        [$perusahaan, $hrUser, $session, $consultant] = $this->hrReviewFixture();

        $this->actingAs($hrUser)->post(route('perusahaan.review.reject', $session), [
            'reason' => 'Kuota habis.',
        ])->assertRedirect();

        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $hrUser->id,
            'action' => 'hr_review.reject',
            'target_id' => $session->id,
        ]);
        $row = \App\Models\AdminAction::where('action', 'hr_review.reject')->latest('id')->first();
        $this->assertSame('Kuota habis.', $row->payload_json['reason'] ?? null);
    }

    public function test_superadmin_preview_employee_mencatat_admin_action()
    {
        $perusahaan = Perusahaan::create([
            'name' => 'PT B', 'brand' => 'BrandB', 'is_active' => true,
        ]);
        $empUser = User::factory()->create([
            'email' => 'emp@test.local',
            'role_id' => Role::firstOrCreate(['name' => 'employee'])->id,
        ]);
        $employee = PerusahaanEmployee::create([
            'perusahaan_id' => $perusahaan->id, 'user_id' => $empUser->id,
            'name' => 'Emp', 'email' => 'emp@test.local', 'consent_at' => now(),
        ]);

        $admin = $this->admin();
        $this->actingAs($admin)
            ->get('/perusahaan/employee?employee_id='.$employee->id)
            ->assertOk();

        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $admin->id,
            'action' => 'employee.preview',
            'target_id' => $employee->id,
        ]);
    }
}
