<?php

namespace App\Services;

use App\Models\Departement;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Sekolah\SekolahStudent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * ONB.2 — import roster massal dari CSV.
 *
 * Satu service dua vertikal. Email belum bisa dikirim, jadi akun dibuat
 * langsung dari isi file; kolom `password` opsional (kosong → acak) dan
 * pemiliknya wajib menggantinya saat login pertama.
 *
 * Preview tidak menyentuh DB sama sekali. Commit berjalan per baris dalam
 * transaksi baris itu sendiri, supaya satu baris gagal tidak menggagalkan
 * 199 baris lain tapi juga tidak meninggalkan user setengah jadi.
 */
class RosterCsvImport
{
    public const MAX_BYTES = 5 * 1024 * 1024;
    public const MAX_ROWS = 2000;

    public const HEADERS = [
        'perusahaan' => ['name', 'email', 'password', 'divisi'],
        'sekolah' => ['name', 'email', 'password', 'kelas'],
    ];

    /**
     * ONB.6 — sekolah boleh mencampur siswa dan staf dalam satu file lewat
     * kolom `tipe` opsional. Tanpa kolom itu semua baris jadi siswa, jadi
     * file lama tetap diterima apa adanya.
     */
    public const OPTIONAL_HEADERS = [
        'sekolah' => ['tipe'],
    ];

    private const ROLE = [
        'perusahaan' => 'employee',
        'sekolah' => 'student',
        'sekolah_staff' => 'sekolah_staff',
    ];

    /** Baca file CSV → array baris mentah (tanpa validasi bisnis). */
    public function read(string $path): array
    {
        $handle = fopen($path, 'r');
        abort_unless($handle !== false, 422, 'File CSV tidak bisa dibaca.');

        $rows = [];
        $line = 0;
        while (($cells = fgetcsv($handle)) !== false) {
            $line++;
            if ($cells === [null] || $cells === []) {
                continue; // baris kosong
            }
            $rows[] = ['line' => $line, 'cells' => array_map(fn ($c) => trim((string) $c), $cells)];
        }
        fclose($handle);

        return $rows;
    }

    /** Header file harus persis sama dengan yang diharapkan vertikal ini. */
    public function assertHeader(array $rows, string $vertical): void
    {
        $expected = self::HEADERS[$vertical] ?? null;
        abort_unless($expected, 422, 'Vertikal tidak dikenal.');

        $first = $rows[0]['cells'] ?? [];
        // Excel "CSV UTF-8" menulis BOM di depan sel pertama; tanpa strip,
        // file yang benar ditolak 422 dan HR buntu.
        if (isset($first[0])) {
            $first[0] = ltrim($first[0], "\u{FEFF}");
        }

        // ONB.6: kolom opsional (`tipe` untuk sekolah) boleh muncul setelah
        // kolom wajib, urutan tetap. Ukuran slice mengikuti jumlah wajib.
        abort_unless(array_slice($first, 0, count($expected)) === $expected, 422,
            'Header CSV harus: ' . implode(',', $expected));
    }

    /**
     * Validasi per baris tanpa menulis apa pun.
     *
     * @return array{rows: array<int, array>, summary: array{valid:int, duplicate:int, error:int}}
     */
    public function preview(array $rows, string $vertical, int $tenantId): array
    {
        $header = self::HEADERS[$vertical];
        $seen = [];
        $out = [];
        $summary = ['valid' => 0, 'duplicate' => 0, 'error' => 0];
        $optHeader = self::OPTIONAL_HEADERS[$vertical] ?? [];

        foreach (array_slice($rows, 1) as $row) {
            $cells = $row['cells'];
            $data = [
                'line' => $row['line'],
                'name' => $cells[0] ?? '',
                'email' => Str::lower($cells[1] ?? ''),
                'password' => $cells[2] ?? '',
                'division' => $cells[3] ?? '',
            ];
            // ONB.6: baris sekolah boleh menyisipkan `tipe` di kolom ke-5.
            $tipe = in_array('tipe', $optHeader, true) ? Str::lower($cells[4] ?? '') : '';
            [$status, $error] = $this->classify($data, $vertical, $tenantId, $seen, $tipe);

            $summary[$status]++;
            $out[] = $data + [
                'status' => $status,
                'error' => $error,
                'is_staff' => $tipe === 'staff',
            ];
        }

        return ['rows' => $out, 'summary' => $summary, 'header' => $header];
    }

    /**
     * Buat akun + row roster untuk setiap baris valid.
     *
     * @return array{created:int, duplicate:int, error:int}
     */
    public function commit(array $rows, string $vertical, int $tenantId): array
    {
        // Role harus ada sebelum satu baris pun dibuat: kegagalan konfigurasi
        // tidak boleh jadi "0 dibuat, semua error" yang tidak kelihatan.
        // Role `sekolah_staff` di-resolve lazy: file siswa-saja tidak boleh
        // gagal hanya karena role staf belum ada di instalasi ini.
        $roleIds = [];
        $roleId = function (bool $isStaff) use (&$roleIds, $vertical): int {
            $role = $isStaff && $vertical === 'sekolah'
                ? self::ROLE['sekolah_staff']
                : self::ROLE[$vertical];
            if (!isset($roleIds[$role])) {
                $id = DB::table('roles')->where('name', $role)->value('id');
                abort_unless($id, 422, 'Role ' . $role . ' belum ada di sistem.');
                $roleIds[$role] = (int) $id;
            }

            return $roleIds[$role];
        };

        $preview = $this->preview($rows, $vertical, $tenantId);
        $result = ['created' => 0, 'duplicate' => 0, 'error' => 0];

        foreach ($preview['rows'] as $row) {
            if ($row['status'] === 'duplicate') {
                $result['duplicate']++;
                continue;
            }
            if ($row['status'] === 'error') {
                $result['error']++;
                continue;
            }

            // Transaksi per baris: satu baris gagal tidak merusak baris lain,
            // tapi juga tidak meninggalkan user tanpa row roster.
            try {
                DB::transaction(fn () => $this->createRow($row, $vertical, $tenantId, $roleId((bool) $row['is_staff'])));
                $result['created']++;
            } catch (\Throwable $e) {
                // Pesan QueryException MySQL menyertakan email mentah
                // ("Duplicate entry 'andi@corp.local'"). Jangan teruskan detail
                // driver ke log; nomor baris cukup untuk diagnosis.
                report(new \RuntimeException(
                    'roster import: baris ' . ($row['line'] ?? '?') . ' gagal dibuat',
                    0,
                    $e
                ));
                $result['error']++;
            }
        }

        return $result;
    }

    private function classify(array $data, string $vertical, int $tenantId, array &$seen, string $tipe = ''): array
    {
        if ($data['name'] === '' || mb_strlen($data['name']) > 255) {
            return ['error', 'Nama wajib diisi (maks 255 karakter).'];
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($data['email']) > 255) {
            return ['error', 'Email tidak valid.'];
        }
        if (!empty($data['password']) && mb_strlen($data['password']) < 8) {
            return ['error', 'Password minimal 8 karakter.'];
        }
        // ONB.6: nilai `tipe` selain siswa/staff menolak baris itu saja.
        if ($tipe !== '' && !in_array($tipe, ['siswa', 'staff'], true)) {
            return ['error', 'Tipe harus "siswa" atau "staff".'];
        }
        if (isset($seen[$data['email']])) {
            return ['duplicate', 'Email duplikat di dalam file.'];
        }

        $seen[$data['email']] = true;

        if (User::where('email', $data['email'])->exists()) {
            return ['duplicate', 'Email sudah terdaftar.'];
        }
        if ($this->rosterQuery($vertical, $tenantId)->where('email', $data['email'])->exists()) {
            return ['duplicate', 'Email sudah ada di roster.'];
        }
        // Kelas sekolah string bebas — tidak ada tabel master untuk divalidasi.
        // Hanya perusahaan yang punya himpunan divisi terbatas.
        if ($vertical === 'perusahaan' && $data['division'] !== ''
            && $this->divisionId($vertical, $tenantId, $data['division'], false) === null) {
            return ['error', 'Divisi/kelas tidak dikenal untuk tenant ini.'];
        }

        return ['valid', null];
    }

    private function createRow(array $row, string $vertical, int $tenantId, int $roleId): void
    {
        [$firstname, $lastname] = $this->splitName($row['name']);

        $user = User::create([
            'username' => $this->uniqueUsername($row['email']),
            'email' => $row['email'],
            'password' => Hash::make($row['password'] !== '' ? $row['password'] : Str::random(12)),
            'firstname' => $firstname,
            'lastname' => $lastname,
            'role_id' => $roleId,
            'is_active' => true,
            'must_change_password' => true,
            'must_confirm_profile' => true,
        ]);

        $attributes = [
            'user_id' => $user->id,
            'name' => $row['name'],
            'email' => $row['email'],
            $vertical === 'perusahaan' ? 'perusahaan_id' : 'sekolah_id' => $tenantId,
        ];

        if ($vertical === 'perusahaan') {
            $attributes['departement_id'] = $this->divisionId($vertical, $tenantId, $row['division']);
        } else {
            $attributes['kelas'] = $row['division'] !== '' ? $row['division'] : null;
            $attributes['type'] = $row['is_staff']
                ? SekolahStudent::TYPE_STAFF
                : SekolahStudent::TYPE_STUDENT;
        }

        $this->rosterModel($vertical)::create($attributes);

        // ONB.6: staf butuh membership sekolah_admins supaya SekolahContext
        // bisa me-resolve tenant-nya; row roster saja tidak cukup.
        if ($vertical === 'sekolah' && $row['is_staff']) {
            \App\Models\Sekolah\SekolahAdmin::create([
                'sekolah_id' => $tenantId,
                'user_id' => $user->id,
                'role' => 'sekolah_staff',
            ]);
        }
    }

    private function rosterQuery(string $vertical, int $tenantId)
    {
        return $vertical === 'perusahaan'
            ? PerusahaanEmployee::where('perusahaan_id', $tenantId)
            : SekolahStudent::where('sekolah_id', $tenantId);
    }

    /** @return class-string<\Illuminate\Database\Eloquent\Model> */
    private function rosterModel(string $vertical): string
    {
        return $vertical === 'perusahaan' ? PerusahaanEmployee::class : SekolahStudent::class;
    }

    private function divisionId(string $vertical, int $tenantId, string $name, bool $create = false): ?int
    {
        if ($vertical !== 'perusahaan') {
            return null; // kelas tetap string
        }
        if ($name === '') {
            return null;
        }

        $dept = Departement::where('name', $name)
            ->where(fn ($q) => $q->where('perusahaan_id', $tenantId)->orWhereNull('perusahaan_id'))
            ->first();

        return $dept?->id;
    }

    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [$name];

        return [$parts[0], $parts[1] ?? ''];
    }

    private function uniqueUsername(string $email): string
    {
        $base = Str::slug(Str::before($email, '@'), '.') ?: 'user';
        $username = $base;
        $i = 1;
        while (User::where('username', $username)->exists()) {
            $username = $base . '.' . $i++;
        }

        return $username;
    }
}