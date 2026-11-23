<?php

namespace App\Http\Controllers;

use App\Models\Klinik\Klinik;
use App\Models\Klinik\KlinikAdmin;
use App\Models\Regency;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Registrasi partner (klinik) — publik. Akun langsung dibuat tapi klinik
 * non-aktif sampai ops approve (GTM tetap associate-led, §15.6: ini lead
 * capture ber-akun, bukan self-serve go-live).
 */
class PartnerRegisterController extends Controller
{
    public function create()
    {
        return view('auth.register-partner', [
            'regencies' => Regency::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'org_name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'facility_type' => 'required|in:klinik_pratama,klinik_utama,rumah_sakit,laboratorium,klinik_tumbuh_kembang,klinik_kecantikan,klinik_korporat,lainnya',
            'branch_name' => 'nullable|string|max:255',
            'regency_id' => 'required|exists:regencies,id',
            'address' => 'nullable|string|max:1000',
            'service_interest' => 'required|in:psikolog,nutrisi,fisioterapi,mhcu,edukasi,paket_custom',
            'estimated_monthly_sessions' => 'nullable|integer|min:0|max:10000',
            'pic_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'required|string|max:30',
            'password' => 'required|string|min:8|confirmed',
            'terms' => 'accepted',
        ], [
            'terms.accepted' => 'Anda wajib membaca dan menyetujui S&K Mitra Klinik untuk melanjutkan pendaftaran.',
        ]);

        $roleId = DB::table('roles')->where('name', 'klinik_admin')->value('id');

        DB::transaction(function () use ($data, $roleId) {
            [$first, $last] = array_pad(explode(' ', trim($data['pic_name']), 2), 2, '');

            $user = User::create([
                'firstname' => $first,
                'lastname' => $last,
                'username' => $data['email'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
                'phone_number' => $data['phone'],
                'role_id' => $roleId,
                'is_active' => true,
            ]);

            $klinik = Klinik::create([
                'name' => $data['org_name'],
                'brand' => $data['brand'] ?: $data['org_name'],
                'facility_type' => $data['facility_type'],
                'branch_name' => $data['branch_name'] ?? null,
                'address' => $data['address'] ?? null,
                'regency_id' => $data['regency_id'],
                'service_interest' => $data['service_interest'],
                'estimated_monthly_sessions' => $data['estimated_monthly_sessions'] ?? null,
                'is_active' => false, // menunggu approval ops
                'terms_agreed_at' => now(),
                'terms_version' => config('terms.klinik_version'),
            ]);

            KlinikAdmin::create([
                'klinik_id' => $klinik->id,
                'user_id' => $user->id,
                'role' => 'admin',
            ]);
        });

        return redirect()->route('loginIndex')->with('success', 'Pendaftaran diterima! Akun kamu menunggu verifikasi tim kami (maks 1×24 jam kerja). Silakan cek email/WhatsApp.');
    }
}
