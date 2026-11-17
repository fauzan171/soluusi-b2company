<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\Klinik\Klinik;
use App\Models\Klinik\KlinikAdmin;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahAdmin;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * A3 — Provisioning wizard 1 pintu: tenant 3 vertikal + akun admin tenant
 * dalam 1 transaksi DB. Superadmin-vetted → klinik/perusahaan langsung aktif;
 * sekolah pending aktivasi program (pattern A1). Pool konsultan opsional
 * hanya perusahaan (consultant_pools tak punya klinik_id — Out of Scope task.md).
 * Self-register /daftar-mitra TETAP jalan → approval queue, jalur terpisah.
 */
class ProvisioningController extends Controller
{
    public function create()
    {
        return view('admin.provision', [
            'consultants' => Consultant::with('user:id,firstname,lastname')
                ->where('status', Consultant::ACCEPT)
                ->get()
                ->sortBy(fn ($c) => $c->user?->fullname ?? '')
                ->values(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vertical' => ['required', 'in:klinik,perusahaan,sekolah'],
            'name' => ['required', 'string', 'max:100'],
            'brand' => ['required', 'string', 'max:100'],
            'tingkat' => ['nullable', 'string', 'max:20'],
            'pepm_rate' => ['nullable', 'integer', 'min:0'],
            'consultant_ids' => ['nullable', 'array'],
            'consultant_ids.*' => ['integer', 'exists:consultants,id'],
            'admin_name' => ['required', 'string', 'max:100'],
            'admin_email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8'],
        ]);
        $data['pepm_rate'] = $data['pepm_rate'] ?? 15000;
        $consultantIds = array_values(array_unique($data['consultant_ids'] ?? []));

        [$tenant, $adminUser] = DB::transaction(function () use ($data, $consultantIds) {
            [$firstname, $lastname] = array_pad(explode(' ', trim($data['admin_name']), 2), 2, '');

            $user = User::create([
                'username' => $data['admin_email'],
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
                'is_active' => true,
                'role_id' => $this->roleFor($data['vertical']),
            ]);

            $tenant = match ($data['vertical']) {
                'klinik' => $this->createKlinik($data, $user),
                'perusahaan' => $this->createPerusahaan($data, $user, $consultantIds),
                'sekolah' => $this->createSekolah($data, $user),
            };

            AdminAction::record(auth()->id(), "{$data['vertical']}.provision", $tenant, [
                'name' => $data['name'],
                'brand' => $data['brand'],
                'admin_user_id' => $user->id,
                'consultant_ids' => $consultantIds,
            ]);

            return [$tenant, $user];
        });

        return redirect()
            ->route('admin.provision')
            ->with('success', "Tenant \"{$tenant->brand}\" + akun admin {$adminUser->email} dibuat dalam 1 transaksi.");
    }

    private function roleFor(string $vertical): int
    {
        $roleName = match ($vertical) {
            'klinik' => 'klinik_admin',
            'perusahaan' => 'hr_admin',
            'sekolah' => 'bk',
        };
        return Role::where('name', $roleName)->firstOrFail()->id;
    }

    private function createKlinik(array $data, User $user): Klinik
    {
        $klinik = Klinik::create([
            'name' => $data['name'],
            'brand' => $data['brand'],
            'is_active' => true,
            'approved_at' => now(),
        ]);
        KlinikAdmin::create(['klinik_id' => $klinik->id, 'user_id' => $user->id, 'role' => 'admin']);
        return $klinik;
    }

    private function createPerusahaan(array $data, User $user, array $consultantIds): Perusahaan
    {
        $perusahaan = Perusahaan::create([
            'name' => $data['name'],
            'brand' => $data['brand'],
            'pepm_rate' => $data['pepm_rate'],
            'mou_at' => now(),
            'is_active' => true,
        ]);
        PerusahaanAdmin::create([
            'perusahaan_id' => $perusahaan->id,
            'user_id' => $user->id,
            'role' => 'hr_admin',
        ]);
        foreach ($consultantIds as $consultantId) {
            ConsultantPool::firstOrCreate([
                'consultant_id' => $consultantId,
                'perusahaan_id' => $perusahaan->id,
            ]);
        }
        // C3: tenant activated (mou_at) — evaluasi pervorma CAP (idempotent).
        \App\Services\PervormaService::evaluate('cap', $perusahaan);
        return $perusahaan;
    }

    private function createSekolah(array $data, User $user): Sekolah
    {
        $sekolah = Sekolah::create([
            'name' => $data['name'],
            'brand' => $data['brand'],
            'tingkat' => $data['tingkat'] ?? null,
            'is_active' => false, // pending aktivasi program — pattern A1 sekolah
        ]);
        SekolahAdmin::create(['sekolah_id' => $sekolah->id, 'user_id' => $user->id, 'role' => 'bk']);
        return $sekolah;
    }
}
