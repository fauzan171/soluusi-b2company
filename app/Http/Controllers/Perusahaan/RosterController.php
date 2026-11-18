<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Support\PerusahaanContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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

        return view('perusahaan.roster', [
            'perusahaan' => $perusahaan,
            'employees' => $employees,
            'quota' => $perusahaan->sessions_per_employee_month,
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
            abort_unless(\App\Models\Departement::whereKey($data['departement_id'])->where('perusahaan_id', $perusahaan->id)->exists(), 422, 'Departemen bukan milik perusahaan.');
        }
        // Email unik lintas roster: duplikat membuat akun/membership ambigu saat
        // self-register (satu email = satu user). Termasuk tenant lain.
        if (PerusahaanEmployee::withoutGlobalScopes()->where('email', $data['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Email ini sudah terdaftar di roster karyawan.',
            ]);
        }

        DB::transaction(function () use ($perusahaan, $data) {
            $employee = $perusahaan->employees()->create($data);
            \App\Services\Perusahaan\EmployeeProvisioner::ensure($employee);
        });

        return back()->with('success', 'Karyawan ditambahkan. Catat consent lalu kirim undangan.');
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

        // ponytail: email undangan via Mail::raw (MAIL_MAILER=log di dev).
        // Upgrade: templated mailable saat dibutuhkan.
        $registerUrl = \App\Services\RosterInvitation::issue('karyawan', $emp);
        Mail::raw(
            "Halo {$emp->name},\n\n"
            . "Perusahaan {$perusahaan->brand} mengundang kamu mengikuti program wellbeing karyawan. "
            . "Belum punya akun? Daftar di {$registerUrl} menggunakan email ini.\n"
            . "Sudah punya akun? Login ke portal untuk melihat kuota, mengisi asesmen, dan booking sesi konsultasi.\n\n"
            . "Semua sesi bersifat rahasia — HR hanya melihat data agregat.\n\n"
            . "Salam,\n{$perusahaan->brand}",
            fn ($m) => $m->to($emp->email)
                ->subject("Undangan program wellbeing {$perusahaan->brand}")
        );

        return back()->with('success', "Undangan terkirim ke {$emp->email}.");
    }
}
