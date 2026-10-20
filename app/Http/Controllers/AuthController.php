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

        Auth::login($user);
        session()->regenerate();

        return $this->redirectByRole($user);
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

        SocialAccount::updateOrCreate(
            ['provider' => $provider, 'provider_id' => $socialUser->getId()],
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

    protected function redirectByRole(User $user)
    {
        return match ($user->role?->name) {
            'admin' => redirect()->route('admin.dashboard'),
            'klinik_admin' => redirect()->route('klinik.dashboard'),
            'associate' => redirect()->route('associate.dashboard'),
            'consultant' => redirect()->route('consultant.sessions'),
            'hr_admin' => redirect()->route('perusahaan.dashboard'),
            'employee' => redirect()->route('perusahaan.employee'),
            'bk' => redirect()->route('sekolah.dashboard'),
            'student' => redirect()->route('sekolah.student'),
            default => redirect()->route('loginIndex')->with('error', 'Role tidak dikenal.'),
        };
    }
}
