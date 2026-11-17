<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahAdmin;
use App\Models\Sekolah\SekolahSession;
use App\Models\Sekolah\SekolahStudent;
use App\Models\User;
use App\Policies\SekolahSessionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SekolahSessionPolicyTest extends TestCase
{
    use RefreshDatabase;

    private SekolahSessionPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new SekolahSessionPolicy;
    }

    private function roleId(string $name): int
    {
        return DB::table('roles')->where('name', $name)->value('id')
            ?? DB::table('roles')->insertGetId(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function makeUser(string $role, string $email): User
    {
        return User::factory()->create(['role_id' => $this->roleId($role), 'email' => $email]);
    }

    /** Bikin 2 sekolah + aktor lengkap per sekolah: bk, staff, student+user, consultant. */
    private function fixtures(): array
    {
        $sekolahA = Sekolah::create(['name' => 'SMA A', 'brand' => 'A']);
        $sekolahB = Sekolah::create(['name' => 'SMA B', 'brand' => 'B']);

        $bkA = $this->makeUser('bk', 'bk-a@test.local');
        SekolahAdmin::create(['sekolah_id' => $sekolahA->id, 'user_id' => $bkA->id, 'role' => 'bk']);
        $bkB = $this->makeUser('bk', 'bk-b@test.local');
        SekolahAdmin::create(['sekolah_id' => $sekolahB->id, 'user_id' => $bkB->id, 'role' => 'bk']);

        $staffA = $this->makeUser('sekolah_staff', 'staff-a@test.local');
        SekolahAdmin::create(['sekolah_id' => $sekolahA->id, 'user_id' => $staffA->id, 'role' => 'sekolah_staff']);

        $studentUserA = $this->makeUser('student', 'stu-a@test.local');
        $studentA = SekolahStudent::create([
            'sekolah_id' => $sekolahA->id, 'user_id' => $studentUserA->id,
            'name' => 'Siswa A', 'email' => 'stu-a@test.local',
        ]);
        $studentUserOther = $this->makeUser('student', 'stu-a2@test.local');
        $studentOther = SekolahStudent::create([
            'sekolah_id' => $sekolahA->id, 'user_id' => $studentUserOther->id,
            'name' => 'Siswa A2', 'email' => 'stu-a2@test.local',
        ]);

        $consultantUserA = $this->makeUser('consultant', 'cons-a@test.local');
        $consultantA = Consultant::factory()->create(['user_id' => $consultantUserA->id, 'status' => 'accept']);
        $consultantUserOther = $this->makeUser('consultant', 'cons-b@test.local');
        $consultantOther = Consultant::factory()->create(['user_id' => $consultantUserOther->id, 'status' => 'accept']);

        $session = SekolahSession::create([
            'sekolah_id' => $sekolahA->id,
            'student_id' => $studentA->id,
            'consultant_id' => $consultantA->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);

        return compact(
            'sekolahA', 'sekolahB', 'bkA', 'bkB', 'staffA',
            'studentUserA', 'studentA', 'studentUserOther', 'studentOther',
            'consultantUserA', 'consultantA', 'consultantUserOther', 'consultantOther',
            'session'
        );
    }

    // ---------- view ----------
    public function test_view_bk_same_tenant_allowed(): void
    {
        $f = $this->fixtures();
        $this->assertTrue($this->policy->view($f['bkA'], $f['session']));
    }

    public function test_view_bk_cross_tenant_denied(): void
    {
        $f = $this->fixtures();
        $this->assertFalse($this->policy->view($f['bkB'], $f['session']));
    }

    public function test_view_student_owner_allowed(): void
    {
        $f = $this->fixtures();
        $this->assertTrue($this->policy->view($f['studentUserA'], $f['session']));
    }

    public function test_view_student_other_denied(): void
    {
        $f = $this->fixtures();
        $this->assertFalse($this->policy->view($f['studentUserOther'], $f['session']));
    }

    public function test_view_consultant_assigned_allowed(): void
    {
        $f = $this->fixtures();
        $this->assertTrue($this->policy->view($f['consultantUserA'], $f['session']));
    }

    public function test_view_consultant_other_denied(): void
    {
        $f = $this->fixtures();
        $this->assertFalse($this->policy->view($f['consultantUserOther'], $f['session']));
    }

    public function test_view_staff_same_tenant_allowed(): void
    {
        $f = $this->fixtures();
        $this->assertTrue($this->policy->view($f['staffA'], $f['session']));
    }

    // ---------- update ----------
    public function test_update_bk_same_tenant_allowed(): void
    {
        $f = $this->fixtures();
        $this->assertTrue($this->policy->update($f['bkA'], $f['session']));
    }

    public function test_update_bk_cross_tenant_denied(): void
    {
        $f = $this->fixtures();
        $this->assertFalse($this->policy->update($f['bkB'], $f['session']));
    }

    public function test_update_student_denied(): void
    {
        $f = $this->fixtures();
        $this->assertFalse($this->policy->update($f['studentUserA'], $f['session']));
    }

    public function test_update_consultant_assigned_allowed(): void
    {
        $f = $this->fixtures();
        $this->assertTrue($this->policy->update($f['consultantUserA'], $f['session']));
    }

    // ---------- cancel ----------
    public function test_cancel_student_owner_allowed(): void
    {
        $f = $this->fixtures();
        $this->assertTrue($this->policy->cancel($f['studentUserA'], $f['session']));
    }

    public function test_cancel_bk_same_tenant_allowed(): void
    {
        $f = $this->fixtures();
        $this->assertTrue($this->policy->cancel($f['bkA'], $f['session']));
    }

    public function test_cancel_consultant_denied(): void
    {
        $f = $this->fixtures();
        $this->assertFalse($this->policy->cancel($f['consultantUserA'], $f['session']));
    }

    // ---------- reschedule ----------
    public function test_reschedule_student_owner_allowed(): void
    {
        $f = $this->fixtures();
        $this->assertTrue($this->policy->reschedule($f['studentUserA'], $f['session']));
    }

    public function test_reschedule_bk_same_tenant_allowed(): void
    {
        $f = $this->fixtures();
        $this->assertTrue($this->policy->reschedule($f['bkA'], $f['session']));
    }

    public function test_reschedule_bk_cross_tenant_denied(): void
    {
        $f = $this->fixtures();
        $this->assertFalse($this->policy->reschedule($f['bkB'], $f['session']));
    }

    // ---------- chat ----------
    public function test_chat_student_owner_allowed_when_scheduled(): void
    {
        $f = $this->fixtures();
        $this->assertTrue($this->policy->chat($f['studentUserA'], $f['session']));
    }

    public function test_chat_consultant_assigned_allowed(): void
    {
        $f = $this->fixtures();
        $this->assertTrue($this->policy->chat($f['consultantUserA'], $f['session']));
    }

    public function test_chat_bk_denied(): void
    {
        $f = $this->fixtures();
        $this->assertFalse($this->policy->chat($f['bkA'], $f['session']));
    }

    public function test_chat_denied_when_not_scheduled(): void
    {
        $f = $this->fixtures();
        $f['session']->status = 'done';
        $this->assertFalse($this->policy->chat($f['studentUserA'], $f['session']));
    }

    // ---------- rate ----------
    public function test_rate_student_owner_allowed_when_done(): void
    {
        $f = $this->fixtures();
        $f['session']->status = 'done';
        $this->assertTrue($this->policy->rate($f['studentUserA'], $f['session']));
    }

    public function test_rate_student_denied_when_not_done(): void
    {
        $f = $this->fixtures();
        $this->assertFalse($this->policy->rate($f['studentUserA'], $f['session']));
    }

    public function test_rate_bk_denied(): void
    {
        $f = $this->fixtures();
        $f['session']->status = 'done';
        $this->assertFalse($this->policy->rate($f['bkA'], $f['session']));
    }

    // ---------- bkReview ----------
    public function test_bk_review_bk_same_tenant_allowed(): void
    {
        $f = $this->fixtures();
        $this->assertTrue($this->policy->bkReview($f['bkA'], $f['session']));
    }

    public function test_bk_review_bk_cross_tenant_denied(): void
    {
        $f = $this->fixtures();
        $this->assertFalse($this->policy->bkReview($f['bkB'], $f['session']));
    }

    public function test_bk_review_student_denied(): void
    {
        $f = $this->fixtures();
        $this->assertFalse($this->policy->bkReview($f['studentUserA'], $f['session']));
    }

    public function test_bk_review_sekolah_staff_denied(): void
    {
        $f = $this->fixtures();
        $this->assertFalse($this->policy->bkReview($f['staffA'], $f['session']));
    }

    public function test_bk_review_superadmin_allowed(): void
    {
        $f = $this->fixtures();
        $admin = $this->makeUser('admin', 'admin@test.local');
        $this->assertTrue($this->policy->bkReview($admin, $f['session']));
    }

    // ---------- requestFor ----------
    public function test_request_for_bk_same_tenant_allowed(): void
    {
        $f = $this->fixtures();
        $this->assertTrue($this->policy->requestFor($f['bkA'], $f['session']));
    }

    public function test_request_for_bk_cross_tenant_denied(): void
    {
        $f = $this->fixtures();
        $this->assertFalse($this->policy->requestFor($f['bkB'], $f['session']));
    }

    public function test_request_for_student_denied(): void
    {
        $f = $this->fixtures();
        $this->assertFalse($this->policy->requestFor($f['studentUserA'], $f['session']));
    }
}
