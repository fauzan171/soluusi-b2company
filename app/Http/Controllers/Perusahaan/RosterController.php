<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Support\PerusahaanContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Manajemen karyawan program (roster + invite).
 * SATU-SATUNYA halaman HR yang menampilkan data individual — untuk pengelolaan
 * program (consent, akun). Konten sesi TIDAK pernah tampil di sini.
 */
class RosterController extends Controller
{
    public function index()
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan, 403, 'Akun tidak terhubung ke perusahaan manapun.');

        $employees = $perusahaan->employees()->with('departement')->orderBy('name')->get();

        // W2: label package + sisa kuota bulan ini (count only — privacy:
        // konten sesi individual tidak pernah tampil di halaman HR).
        $quotaInfo = [];
        foreach ($employees as $emp) {
            $quotaInfo[$emp->id] = [
                'package' => \App\Services\QuotaService::packageLabel($emp)['name'],
                'remaining' => \App\Services\QuotaService::balanceFor($emp),
            ];
        }

        return view('perusahaan.roster', [
            'perusahaan' => $perusahaan,
            'employees' => $employees,
            'departements' => \App\Models\Departement::forPerusahaan($perusahaan->id),
            'quota' => $perusahaan->sessions_per_employee_month,
            'quotaInfo' => $quotaInfo,
        ]);
    }

    public function store(Request $r)
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan && $perusahaan->is_active, 403);
        $data = $r->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'departement_id' => ['nullable', 'integer'],
        ]);
        if (!empty($data['departement_id'])) {
            // ONB.1: guard lama query kolom `perusahaan_id` yang dulu tidak ada → selalu 422.
            abort_unless(
                \App\Models\Departement::forPerusahaan($perusahaan->id)->contains('id', (int) $data['departement_id']),
                422,
                'Departemen bukan milik perusahaan.'
            );
        }
        // Email unik lintas roster: duplikat membuat akun/membership ambigu saat
        // self-register (satu email = satu user). Termasuk tenant lain.
        if (PerusahaanEmployee::withoutGlobalScopes()->where('email', $data['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Email ini sudah terdaftar di roster karyawan.',
            ]);
        }

        DB::transaction(function () use ($perusahaan, $data, &$employee) {
            $employee = $perusahaan->employees()->create($data);
            \App\Services\Perusahaan\EmployeeProvisioner::ensure($employee);
        });

        // Audit UX: provisioning + undangan email dalam SATU langkah — karyawan
        // tidak menunggu aksi HR kedua tanpa tahu.
        if ($employee?->email) {
            $this->sendInvitation($perusahaan, $employee->fresh());
        }

        return back()->with('success', "Karyawan ditambahkan. Undangan terkirim ke {$data['email']}.");
    }

    public function consent(Request $r, PerusahaanEmployee $employee)
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan && $perusahaan->is_active && $employee->perusahaan_id === $perusahaan->id, 403);
        \App\Services\ProgramConsent::record($r, 'karyawan', $employee, $perusahaan->id);
        return back()->with('success', 'Consent tercatat dengan provenance.');
    }

    public function invite(Request $r)
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan, 403, 'Akun tidak terhubung ke perusahaan manapun.');

        $r->validate(['employee_id' => ['required', 'exists:perusahaan_employees,id']]);
        $emp = PerusahaanEmployee::findOrFail($r->employee_id);
        // Guard: HR hanya bisa invite karyawan perusahaannya sendiri.
        abort_unless($emp->perusahaan_id === $perusahaan->id, 403);
        abort_unless($emp->email, 422, 'Karyawan belum punya email.');

        // Audit UX: "Kirim ulang undangan" sah sampai karyawan mengganti password
        // (provisioning memakai password acak + must_change_password). Cek SEBELUM
        // mengirim agar karyawan aktif tidak menerima email undangan lagi.
        if ($emp->user && ! $emp->user->must_change_password) {
            return back()->with('error', "Karyawan {$emp->name} sudah aktif (password sementara pernah dikirim). Undangan tidak dikirim ulang.");
        }

        $this->sendInvitation($perusahaan, $emp);

        return back()->with('success', "Undangan terkirim ke {$emp->email}.");
    }

    /** Undangan email — dipakai store (auto) dan invite (kirim ulang). */
    private function sendInvitation(Perusahaan $perusahaan, PerusahaanEmployee $emp): void
    {
        $registerUrl = \App\Services\RosterInvitation::issue('karyawan', $emp);
        \Illuminate\Support\Facades\Mail::to($emp->email)->send(
            new \App\Mail\Perusahaan\RosterInvitationMail($perusahaan, $emp, $registerUrl)
        );
    }
}
