<?php

namespace Tests\Feature;

use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahAdmin;
use App\Models\Sekolah\SekolahStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * ONB.2 (varian sekolah) — BK mengunggah CSV siswa/staf, akun dibuat otomatis.
 * Satu file boleh mencampur siswa dan staf lewat kolom `tipe` opsional.
 */
class SekolahRosterImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['bk', 'student', 'sekolah_staff'] as $name) {
            DB::table('roles')->insertOrIgnore(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function user(string $email, string $role): User
    {
        return User::create([
            'uuid' => Str::uuid(), 'firstname' => 'U', 'lastname' => $role,
            'username' => Str::slug($email) . '.' . uniqid(), 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true,
            'role_id' => DB::table('roles')->where('name', $role)->value('id'),
        ]);
    }

    private function sekolah(string $name = 'SMA Import'): Sekolah
    {
        return Sekolah::create([
            'name' => $name, 'brand' => $name, 'tingkat' => 'SMA',
            'student_count' => 10, 'sessions_per_student_month' => 2, 'is_active' => true,
        ]);
    }

    private function bk(Sekolah $s): User
    {
        $user = $this->user('bk.' . $s->id . '.' . uniqid() . '@import.local', 'bk');
        SekolahAdmin::create(['sekolah_id' => $s->id, 'user_id' => $user->id, 'role' => 'bk']);

        return $user;
    }

    private function upload(string $csv): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('roster.csv', $csv);
    }

    public function test_bk_previews_and_commits_students(): void
    {
        $sekolah = $this->sekolah();
        $bk = $this->bk($sekolah);
        $csv = "name,email,password,kelas\n"
            . "Siswa Satu,siswa1@sekolah.local,,X IPA 1\n"
            . "Siswa Dua,siswa2@sekolah.local,Rahasia123,X IPA 2\n";

        $this->actingAs($bk)
            ->post(route('sekolah.students.import.preview'), ['file' => $this->upload($csv)])
            ->assertOk()
            ->assertSee('Siswa Satu')
            ->assertSee('2');

        $this->actingAs($bk)
            ->post(route('sekolah.students.import.commit'))
            ->assertRedirect(route('sekolah.students'));

        $this->assertSame(2, SekolahStudent::where('sekolah_id', $sekolah->id)->count());

        $siswa = User::where('email', 'siswa1@sekolah.local')->firstOrFail();
        $this->assertSame('student', $siswa->role->name);
        $this->assertTrue($siswa->must_change_password);
        $this->assertTrue($siswa->must_confirm_profile);

        $row = SekolahStudent::where('email', 'siswa1@sekolah.local')->firstOrFail();
        $this->assertSame(SekolahStudent::TYPE_STUDENT, $row->type);
        $this->assertSame('X IPA 1', $row->kelas);
    }

    public function test_staff_row_creates_staff_user_and_membership(): void
    {
        $sekolah = $this->sekolah();
        $bk = $this->bk($sekolah);
        $csv = "name,email,password,kelas,tipe\n"
            . "Guru Satu,guru1@sekolah.local,,,staff\n";

        $this->actingAs($bk)
            ->post(route('sekolah.students.import.preview'), ['file' => $this->upload($csv)])
            ->assertOk();

        $this->actingAs($bk)
            ->post(route('sekolah.students.import.commit'))
            ->assertRedirect(route('sekolah.students'));

        $user = User::where('email', 'guru1@sekolah.local')->firstOrFail();
        $this->assertSame('sekolah_staff', $user->role->name);

        $row = SekolahStudent::where('email', 'guru1@sekolah.local')->firstOrFail();
        $this->assertSame(SekolahStudent::TYPE_STAFF, $row->type);

        // SekolahContext resolve tenant dari sekolah_admins, bukan dari row staf.
        $this->assertTrue(
            SekolahAdmin::where('sekolah_id', $sekolah->id)
                ->where('user_id', $user->id)
                ->where('role', 'sekolah_staff')
                ->exists()
        );
    }

    public function test_csv_without_tipe_column_creates_only_students(): void
    {
        $sekolah = $this->sekolah();
        $bk = $this->bk($sekolah);
        $csv = "name,email,password,kelas\n"
            . "Siswa Satu,siswa1@sekolah.local,,X IPA 1\n";

        $this->actingAs($bk)
            ->post(route('sekolah.students.import.preview'), ['file' => $this->upload($csv)])
            ->assertOk();

        $this->actingAs($bk)->post(route('sekolah.students.import.commit'));

        $this->assertSame(0, SekolahStudent::where('type', SekolahStudent::TYPE_STAFF)->count());
        $this->assertSame('student', User::where('email', 'siswa1@sekolah.local')->firstOrFail()->role->name);
    }

    public function test_unknown_tipe_value_is_rejected_per_row(): void
    {
        $sekolah = $this->sekolah();
        $bk = $this->bk($sekolah);
        $csv = "name,email,password,kelas,tipe\n"
            . "Aneh,aneh@sekolah.local,,X,kepsek\n";

        $response = $this->actingAs($bk)
            ->post(route('sekolah.students.import.preview'), ['file' => $this->upload($csv)])
            ->assertOk();

        $this->assertSame(1, $response->viewData('preview')['summary']['error']);
        $this->assertSame(0, $response->viewData('preview')['summary']['valid']);
    }

    public function test_bk_cannot_import_into_another_school(): void
    {
        $mine = $this->sekolah('SMA Saya');
        $other = $this->sekolah('SMA Lain');
        $bk = $this->bk($mine);

        $csv = "name,email,password,kelas\nSiswa,siswa@sekolah.local,,X\n";

        $this->actingAs($bk)
            ->post(route('sekolah.students.import.preview'), [
                'file' => $this->upload($csv),
                'sekolah_id' => $other->id,
            ])
            ->assertOk();

        $this->actingAs($bk)->post(route('sekolah.students.import.commit'));

        $this->assertSame(1, SekolahStudent::where('sekolah_id', $mine->id)->count());
        $this->assertSame(0, SekolahStudent::where('sekolah_id', $other->id)->count());
    }

    public function test_student_role_cannot_reach_the_import_routes(): void
    {
        $sekolah = $this->sekolah();

        $this->actingAs($this->user('siswa@sekolah.local', 'student'))
            ->get(route('sekolah.students.import'))
            ->assertForbidden();

        $this->actingAs($this->user('siswa2@sekolah.local', 'student'))
            ->post(route('sekolah.students.import.preview'), [
                'file' => $this->upload("name,email,password,kelas\nA,a@b.local,,X\n"),
            ])
            ->assertForbidden();
    }

    public function test_commit_records_an_audit_row_without_pii(): void
    {
        $sekolah = $this->sekolah();
        $bk = $this->bk($sekolah);
        $csv = "name,email,password,kelas\nSiswa Satu,siswa1@sekolah.local,,X IPA 1\n";

        $this->actingAs($bk)->post(route('sekolah.students.import.preview'), ['file' => $this->upload($csv)]);
        $this->actingAs($bk)->post(route('sekolah.students.import.commit'));

        $action = DB::table('admin_actions')->where('action', 'roster.import')->latest('id')->first();
        $this->assertNotNull($action);

        $payload = json_decode($action->payload_json, true);
        $this->assertSame(1, $payload['created']);
        $this->assertStringNotContainsString('siswa1@sekolah.local', $action->payload_json);
    }
}