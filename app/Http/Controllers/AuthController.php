<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

/**
 * B2B auth (Dev Prep §4.3): SSO Google + password untuk admin klinik,
 * konsultan, associate, super admin. Customer = GUEST tanpa akun (Model C) —
 * tidak ada register/customer login di sini.
 */
class AuthController extends Controller
{
    public function index()
    {
        return view('auth.login', ['title' => 'Masuk — ' . config('whitelabel.default_brand')]);
    }

    public function postLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !$user->is_active || !Hash::check($request->password, (string) $user->password)) {
            return back()->with('error', 'Email atau password salah')->withInput();
        }
        if ($this->tenantSuspended($user)) {
            return back()->with('error', 'Akun organisasi Anda sedang disuspend. Hubungi Soluusi.')->withInput();
        }

        Auth::login($user);
        session()->regenerate();

        return $this->redirectByRole($user);
    }

    /** A2: user tenant (admin maupun end-user) tidak boleh login saat tenant-nya disuspend. */
    private function tenantSuspended(User $user): bool
    {
        $role = $user->role?->name;
        if ($role === 'admin') {
            return false;
        }
        return match ($role) {
            'klinik_admin' => ($user->klinik?->status ?? null) === 'suspended',
            // Konsultan tidak punya home-klinik — cek via schedules.klinik_id (klinik yang dia layani).
            'konsultan' => \App\Models\Schedule::whereIn('consultant_id', \App\Models\Consultant::where('user_id', $user->id)->pluck('id'))
                ->whereNotNull('klinik_id')
                ->join('kliniks', 'kliniks.id', '=', 'schedules.klinik_id')
                ->where('kliniks.status', 'suspended')->exists(),
            'hr_admin' => ($user->perusahaanAdmins->first()?->perusahaan?->status ?? null) === 'suspended',
            'employee' => \App\Models\Perusahaan\PerusahaanEmployee::where('user_id', $user->id)
                ->join('perusahaans', 'perusahaans.id', '=', 'perusahaan_employees.perusahaan_id')
                ->where('perusahaans.status', 'suspended')->exists(),
            'bk' => (bool) \App\Models\Sekolah\SekolahAdmin::where('user_id', $user->id)
                ->join('sekolahs', 'sekolahs.id', '=', 'sekolah_admins.sekolah_id')
                ->where('sekolahs.status', 'suspended')->exists(),
            // E.1: staf sekolah terikat tenant lewat sekolah_admins (role sekolah_staff).
            'sekolah_staff' => (bool) \App\Models\Sekolah\SekolahAdmin::where('user_id', $user->id)
                ->join('sekolahs', 'sekolahs.id', '=', 'sekolah_admins.sekolah_id')
                ->where('sekolahs.status', 'suspended')->exists(),
            'student' => \App\Models\Sekolah\SekolahStudent::where('user_id', $user->id)
                ->join('sekolahs', 'sekolahs.id', '=', 'sekolah_students.sekolah_id')
                ->where('sekolahs.status', 'suspended')->exists(),
            default => false,
        };
    }

    public function redirectToProvider(string $provider)
    {
        abort_unless($provider === 'google', 404);
        return Socialite::driver($provider)->redirect();
    }

    public function handleProvideCallback(string $provider)
    {
        abort_unless($provider === 'google', 404);

        $socialUser = Socialite::driver($provider)->user();
        abort_unless(($socialUser->user['email_verified'] ?? $socialUser->user['verified_email'] ?? false) === true,
            403, 'Email provider belum terverifikasi.');

        // SSO hanya untuk user yang SUDAH ada (admin klinik/konsultan/associate
        // diundang via ops). No self-serve signup (§15.6 DECIDED).
        $user = User::where('email', $socialUser->getEmail())->first();
        if (!$user || !$user->is_active) {
            return redirect()->route('loginIndex')->with('error', 'Akun belum terdaftar. Hubungi admin.');
        }

        // Skema: kolomnya provider_name (bukan 'provider') — dulu updateOrCreate
        // silent-drop karena key tidak cocok (mass-assignment guard).
        SocialAccount::updateOrCreate(
            ['provider_name' => $provider, 'provider_id' => $socialUser->getId()],
            ['user_id' => $user->id, 'provider_token' => $socialUser->token ?? null]
        );

        Auth::login($user);
        session()->regenerate();

        return $this->redirectByRole($user);
    }

    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->route('loginIndex');
    }

    public function redirectByRole(User $user)
    {
        // ONB.3: akun hasil import wajib ganti password dulu. Superadmin dikecualikan.
        if ($user->must_change_password && $user->role?->name !== 'admin') {
            return redirect()->route('password.change');
        }

        return match ($user->role?->name) {
            'admin' => redirect()->route('admin.dashboard'),
            'klinik_admin' => redirect()->route('klinik.dashboard'),
            'associate' => redirect()->route('associate.dashboard'),
            'consultant' => redirect()->route('consultant.sessions'),
            'hr_admin' => redirect()->route('perusahaan.dashboard'),
            'employee' => redirect()->route('perusahaan.employee'),
            'bk' => redirect()->route('sekolah.dashboard'),
            'sekolah_staff' => redirect()->route('sekolah.staff'),
            'student' => redirect()->route('sekolah.student'),
            default => redirect()->route('loginIndex')->with('error', 'Role tidak dikenal.'),
        };
    }
}
