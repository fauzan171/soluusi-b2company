<?php

namespace Tests\Feature;

use App\Models\AdminAction;
use App\Models\Departement;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * ONB.2 — import CSV massal untuk vertikal perusahaan.
 * Email belum bisa dikirim, jadi HR mengunggah satu file dan akun dibuat
 * langsung dari isinya.
 */
class RosterCsvImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Role dibuat DatabaseSeeder di runtime; test membuatnya sendiri.
        foreach (['hr_admin', 'employee', 'student'] as $name) {
            DB::table('roles')->insertOrIgnore(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
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

    private function perusahaan(string $name = 'PT Import'): Perusahaan
    {
        return Perusahaan::create([
            'name' => $name, 'brand' => $name, 'sessions_per_employee_month' => 2,
            'employee_count' => 3, 'is_active' => true, 'status' => 'active',
        ]);
    }

    private function hr(Perusahaan $p): User
    {
        $user = $this->user('hr-' . $p->id . '@import.local', 'hr_admin');
        PerusahaanAdmin::create(['perusahaan_id' => $p->id, 'user_id' => $user->id]);

        return $user;
    }

    private function csv(string $content, string $name = 'roster.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $content);
    }

    private function validCsv(): UploadedFile
    {
        return $this->csv(
            "name,email,password,divisi\n"
            . "Andi Pratama,andi@import.local,Rahasia123,Engineering\n"
            . "Budi Santoso,budi@import.local,,Marketing\n"
            . "Citra Dewi,citra@import.local,Rahasia456,\n"
        );
    }

    public function test_preview_lists_rows_without_writing_to_database(): void
    {
        $perusahaan = $this->perusahaan();
        Departement::create(['name' => 'Engineering', 'perusahaan_id' => $perusahaan->id]);
        Departement::create(['name' => 'Marketing', 'perusahaan_id' => $perusahaan->id]);
        $hr = $this->hr($perusahaan);

        $before = User::count();

        $this->actingAs($hr)
            ->post(route('perusahaan.employees.import.preview'), ['file' => $this->validCsv()])
            ->assertOk()
            ->assertSee('Andi Pratama')
            ->assertSee('Budi Santoso')
            ->assertSee('Citra Dewi');

        $this->assertSame($before, User::count());
        $this->assertSame(0, PerusahaanEmployee::count());
    }

    public function test_commit_creates_users_and_roster_rows(): void
    {
        $perusahaan = $this->perusahaan();
        $eng = Departement::create(['name' => 'Engineering', 'perusahaan_id' => $perusahaan->id]);
        $mkt = Departement::create(['name' => 'Marketing', 'perusahaan_id' => $perusahaan->id]);
        $hr = $this->hr($perusahaan);

        $preview = $this->actingAs($hr)->post(route('perusahaan.employees.import.preview'), ['file' => $this->validCsv()]);
        $preview->assertOk();

        $this->actingAs($hr)
            ->post(route('perusahaan.employees.import.commit'))
            ->assertRedirect(route('perusahaan.employees'));

        $this->assertSame(3, PerusahaanEmployee::where('perusahaan_id', $perusahaan->id)->count());

        $andi = User::where('email', 'andi@import.local')->firstOrFail();
        $this->assertTrue($andi->must_change_password);
        $this->assertTrue($andi->must_confirm_profile);
        $this->assertSame('employee', $andi->role->name);

        $row = PerusahaanEmployee::where('email', 'andi@import.local')->firstOrFail();
        $this->assertSame($andi->id, $row->user_id);
        $this->assertSame($eng->id, $row->departement_id);
        $this->assertNull($row->consent_at);
        $this->assertSame($mkt->id, PerusahaanEmployee::where('email', 'budi@import.local')->value('departement_id'));
        $this->assertNull(PerusahaanEmployee::where('email', 'citra@import.local')->value('departement_id'));
    }

    public function test_row_without_password_gets_random_password(): void
    {
        $perusahaan = $this->perusahaan();
        Departement::create(['name' => 'Marketing', 'perusahaan_id' => $perusahaan->id]);
        $hr = $this->hr($perusahaan);

        $this->actingAs($hr)->post(route('perusahaan.employees.import.preview'), ['file' => $this->validCsv()]);
        $this->actingAs($hr)->post(route('perusahaan.employees.import.commit'));

        $budi = User::where('email', 'budi@import.local')->firstOrFail();
        $this->assertNotEmpty($budi->password);
        $this->assertTrue($budi->must_change_password);
    }

    public function test_duplicate_email_inside_file_is_skipped(): void
    {
        $perusahaan = $this->perusahaan();
        $hr = $this->hr($perusahaan);
        $csv = $this->csv(
            "name,email,password,divisi\n"
            . "Andi Pratama,andi@import.local,Rahasia123,\n"
            . "Andi Lagi,andi@import.local,Rahasia999,\n"
        );

        $this->actingAs($hr)->post(route('perusahaan.employees.import.preview'), ['file' => $csv])->assertOk();
        $this->actingAs($hr)->post(route('perusahaan.employees.import.commit'));

        $this->assertSame(1, User::where('email', 'andi@import.local')->count());
        $this->assertSame(1, PerusahaanEmployee::where('email', 'andi@import.local')->count());
    }

    public function test_existing_user_is_not_overwritten(): void
    {
        $perusahaan = $this->perusahaan();
        $existing = $this->user('andi@import.local', 'employee');
        $before = $existing->password;
        $hr = $this->hr($perusahaan);

        $this->actingAs($hr)->post(route('perusahaan.employees.import.preview'), ['file' => $this->validCsv()]);
        $this->actingAs($hr)->post(route('perusahaan.employees.import.commit'));

        $this->assertSame($before, $existing->fresh()->password);
        $this->assertSame(0, PerusahaanEmployee::where('email', 'andi@import.local')->count());
    }

    public function test_wrong_header_is_rejected_without_writing(): void
    {
        $perusahaan = $this->perusahaan();
        $hr = $this->hr($perusahaan);
        $csv = $this->csv("nama,mail,sandi\nAndi,and@import.local,x\n");

        $this->actingAs($hr)
            ->post(route('perusahaan.employees.import.preview'), ['file' => $csv])
            ->assertStatus(422);

        $this->assertSame(0, Departement::count());
        $this->assertSame(0, User::where('email', 'and@import.local')->count());
    }

    public function test_row_with_foreign_division_is_marked_error(): void
    {
        $perusahaan = $this->perusahaan();
        $other = $this->perusahaan('PT Lain');
        Departement::create(['name' => 'Legal Lain', 'perusahaan_id' => $other->id]);
        $hr = $this->hr($perusahaan);
        $csv = $this->csv("name,email,password,divisi\nAndi,andi@import.local,x,Legal Lain\n");

        $this->actingAs($hr)->post(route('perusahaan.employees.import.preview'), ['file' => $csv])->assertOk();
        $this->actingAs($hr)->post(route('perusahaan.employees.import.commit'))->assertRedirect();

        // Baris error tidak membuat akun.
        $this->assertSame(0, User::where('email', 'andi@import.local')->count());
    }

    public function test_hr_cannot_import_into_another_company(): void
    {
        $a = $this->perusahaan('PT A');
        $b = $this->perusahaan('PT B');
        Departement::create(['name' => 'Engineering', 'perusahaan_id' => $a->id]);
        Departement::create(['name' => 'Marketing', 'perusahaan_id' => $a->id]);
        $hrA = $this->hr($a);

        $this->actingAs($hrA)
            ->post(route('perusahaan.employees.import.preview'), [
                'file' => $this->validCsv(),
                'perusahaan_id' => $b->id,
            ])
            ->assertOk();

        // Target tetap tenant ter-resolve, bukan B.
        $this->actingAs($hrA)->post(route('perusahaan.employees.import.commit'), ['perusahaan_id' => $b->id]);

        $this->assertSame(3, PerusahaanEmployee::where('perusahaan_id', $a->id)->count());
        $this->assertSame(0, PerusahaanEmployee::where('perusahaan_id', $b->id)->count());
    }

    public function test_oversized_file_is_rejected(): void
    {
        $perusahaan = $this->perusahaan();
        $hr = $this->hr($perusahaan);
        $csv = $this->csv("name,email,password,divisi\n" . str_repeat("Andi,and@import.local,x,\n", 2001));

        $this->actingAs($hr)
            ->post(route('perusahaan.employees.import.preview'), ['file' => $csv])
            ->assertStatus(422);
    }

    public function test_non_csv_extension_is_rejected(): void
    {
        $perusahaan = $this->perusahaan();
        $hr = $this->hr($perusahaan);
        $file = $this->csv("name,email,password,divisi\n", 'roster.xlsx');

        $this->actingAs($hr)
            ->post(route('perusahaan.employees.import.preview'), ['file' => $file])
            ->assertStatus(422);
    }

    public function test_audit_log_contains_counts_only(): void
    {
        $perusahaan = $this->perusahaan();
        Departement::create(['name' => 'Engineering', 'perusahaan_id' => $perusahaan->id]);
        Departement::create(['name' => 'Marketing', 'perusahaan_id' => $perusahaan->id]);
        $hr = $this->hr($perusahaan);

        $this->actingAs($hr)->post(route('perusahaan.employees.import.preview'), ['file' => $this->validCsv()]);
        $this->actingAs($hr)->post(route('perusahaan.employees.import.commit'));

        $audit = AdminAction::where('action', 'roster.import')->firstOrFail();
        $payload = json_encode($audit->payload_json);

        $this->assertSame(3, $audit->payload_json['created']);
        $this->assertStringNotContainsString('andi@import.local', $payload);
        $this->assertStringNotContainsString('Andi Pratama', $payload);
    }

    public function test_employee_cannot_reach_import_routes(): void
    {
        $perusahaan = $this->perusahaan();
        $employee = $this->user('karyawan@import.local', 'employee');
        PerusahaanEmployee::create([
            'perusahaan_id' => $perusahaan->id, 'user_id' => $employee->id,
            'name' => 'Karyawan', 'email' => $employee->email, 'consent_at' => now(),
        ]);

        $this->actingAs($employee)->get(route('perusahaan.employees.import'))->assertForbidden();
    }

    public function test_import_page_renders(): void
    {
        $perusahaan = $this->perusahaan();
        $this->actingAs($this->hr($perusahaan))
            ->get(route('perusahaan.employees.import'))
            ->assertOk()
            ->assertSee('CSV');
    }

    // --- Vertikal sekolah: service sudah dua-vertikal, controller menyusul ---
    // Test langsung ke service (bukan HTTP) karena rute BK menunggu
    // goal/sekolah-flow-parity merge. Ini menjaga klaim "service siap" jujur.

    private function csvPath(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'roster') . '.csv';
        file_put_contents($path, $content);

        return $path;
    }

    public function test_sekolah_vertical_creates_students_with_kelas(): void
    {
        $sekolah = \App\Models\Sekolah\Sekolah::create([
            'name' => 'SMA Impor', 'brand' => 'SMA Impor',
            'sessions_per_student_month' => 2, 'student_count' => 3,
            'is_active' => true, 'status' => 'active',
        ]);

        $service = new \App\Services\RosterCsvImport();
        $rows = $service->read($this->csvPath(
            "name,email,password,kelas\n"
            . "Siswa Satu,siswa1@sekolah.local,,X IPA 1\n"
            . "Siswa Dua,siswa2@sekolah.local,Rahasia123,X IPA 2\n"
        ));
        $service->assertHeader($rows, 'sekolah');

        $result = $service->commit($rows, 'sekolah', $sekolah->id);

        $this->assertSame(2, $result['created']);
        $this->assertSame(0, $result['error']);

        $siswa = \App\Models\User::where('email', 'siswa1@sekolah.local')->firstOrFail();
        $this->assertSame('student', $siswa->role->name);
        $this->assertTrue($siswa->must_change_password);
        $this->assertTrue($siswa->must_confirm_profile);

        $row = \App\Models\Sekolah\SekolahStudent::where('email', 'siswa1@sekolah.local')->firstOrFail();
        $this->assertSame($sekolah->id, $row->sekolah_id);
        $this->assertSame($siswa->id, $row->user_id);
        $this->assertSame('X IPA 1', $row->kelas);
    }

    public function test_sekolah_header_requires_kelas_column(): void
    {
        $service = new \App\Services\RosterCsvImport();
        $rows = $service->read($this->csvPath("name,email,password,divisi\nSiswa,s@sekolah.local,x,X\n"));

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $service->assertHeader($rows, 'sekolah');
    }
}