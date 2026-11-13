<?php

namespace App\Http\Controllers;

use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanAdmin;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Provisioning tenant perusahaan B2B oleh super admin: buat perusahaan +
 * akun HR pertama sekaligus. Karyawan tidak dibuat di sini — HR mengundang
 * via roster (SelfRegisterController), sesuai alur onboarding karyawan.
 */
class AdminPerusahaanController extends Controller
{
    public function index()
    {
        return view('admin.perusahaans', [
            'perusahaans' => Perusahaan::withCount('employees')->with('admins.user')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'brand' => ['required', 'string', 'max:100'],
            'pepm_rate' => ['nullable', 'integer', 'min:0'],
            'sessions_per_employee_month' => ['nullable', 'integer', 'min:1', 'max:30'],
            'hr_name' => ['required', 'string', 'max:100'],
            'hr_email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'hr_password' => ['required', 'string', 'min:8'],
        ]);
        $data['pepm_rate'] = $data['pepm_rate'] ?? 15000;
        $data['sessions_per_employee_month'] = $data['sessions_per_employee_month'] ?? 2;

        [$perusahaan, $hr] = DB::transaction(function () use ($data) {
            $perusahaan = Perusahaan::create([
                'name' => $data['name'],
                'brand' => $data['brand'],
                'pepm_rate' => $data['pepm_rate'],
                'sessions_per_employee_month' => $data['sessions_per_employee_month'],
                'mou_at' => now(),
                'is_active' => true,
            ]);
            $hr = User::create([
                'uuid' => Str::uuid(),
                'username' => 'hr_' . Str::random(20),
                'firstname' => strtok($data['hr_name'], ' '),
                'lastname' => trim(strstr($data['hr_name'], ' ') ?: ''),
                'email' => $data['hr_email'],
                'password' => Hash::make($data['hr_password']),
                'is_active' => true,
                'role_id' => Role::where('name', 'hr_admin')->firstOrFail()->id,
            ]);
            PerusahaanAdmin::create([
                'perusahaan_id' => $perusahaan->id,
                'user_id' => $hr->id,
                'role' => 'hr_admin',
            ]);
            return [$perusahaan, $hr];
        });

        return back()->with('success',
            "Perusahaan \"{$perusahaan->brand}\" aktif. HR {$hr->email} bisa login dan mengundang karyawan via roster."
        );
    }
}
