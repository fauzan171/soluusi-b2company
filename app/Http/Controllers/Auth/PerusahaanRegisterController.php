<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * wv3: Registrasi perusahaan — publik "Daftar Sekarang" (lead-capture).
 * Mirror PartnerRegisterController: akun hr_admin langsung dibuat, perusahaan
 * inactive/pending menunggu ops approve (PRODUKT-DOC §6). No auto-login.
 */
class PerusahaanRegisterController extends Controller
{
    private const BANDS = [
        '50-150' => '50–150 karyawan',
        '151-400' => '151–400 karyawan',
        '401-600' => '401–600 karyawan',
        '601-800' => '601–800 karyawan',
        '801-1000' => '801–1000 karyawan',
        '1000+' => 'Lebih dari 1000 karyawan',
    ];

    public function create()
    {
        return view('auth.register-perusahaan', [
            'bands' => self::BANDS,
            'packages' => [
                'discovery' => 'Discovery (Gratis — 14 hari)',
                'foundation' => 'Foundation (90 hari)',
                'growth' => 'Growth (180 hari)',
                'impact' => 'Impact (365 hari)',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'org_name' => 'required|string|max:255',
            // QA1-LOW: pesan generik — form publik tidak membocorkan bahwa
            // email sudah terdaftar (enumeration), tapi pendaftar sah tetap
            // paham harus login/pakai email lain.
            'hr_email' => 'required|email|max:255|unique:users,email',
            'hr_name' => 'required|string|max:255',
            'employee_band' => 'required|in:' . implode(',', array_keys(self::BANDS)),
            'phone' => 'required|string|max:30',
            'package_code' => 'required|in:discovery,foundation,growth,impact',
            'terms' => 'accepted',
        ], [
            'hr_email.unique' => 'Email ini tidak bisa dipakai untuk pendaftaran. Silakan gunakan email lain.',
            'terms.accepted' => 'Anda wajib membaca dan menyetujui S&K Perusahaan untuk melanjutkan pendaftaran.',
        ]);

        $roleId = DB::table('roles')->where('name', 'hr_admin')->value('id');

        DB::transaction(function () use ($data, $roleId) {
            [$first, $last] = array_pad(explode(' ', trim($data['hr_name']), 2), 2, '');

            $user = User::create([
                'firstname' => $first,
                'lastname' => $last,
                'username' => $data['hr_email'],
                'email' => $data['hr_email'],
                'password' => bcrypt('perusahaan-pending-' . bin2hex(random_bytes(8))), // no auto-login: HR set password saat aktivasi
                'phone_number' => $data['phone'],
                'role_id' => $roleId,
                'is_active' => false, // menunggu approval ops
            ]);

            $perusahaan = Perusahaan::create([
                'name' => $data['org_name'],
                'brand' => $data['org_name'],
                'employee_count' => $this->bandFloor($data['employee_band']),
                'is_active' => false, // pending approval ops
                'status' => 'pending',
                'terms_agreed_at' => now(),
            ]);

            PerusahaanAdmin::create([
                'perusahaan_id' => $perusahaan->id,
                'user_id' => $user->id,
                'role' => 'hr_admin',
            ]);
        });

        return redirect()->route('loginIndex')->with('success', 'Pendaftaran diterima! Tim kami akan menghubungi Anda untuk verifikasi dan aktivasi program.');
    }

    private function bandFloor(string $band): int
    {
        if ($band === '1000+') {
            return 1000;
        }

        return (int) explode('-', $band)[0];
    }
}
