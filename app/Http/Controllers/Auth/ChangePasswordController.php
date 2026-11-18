<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * ONB.3 — ganti password paksa untuk akun hasil import CSV.
 * Password awal datang dari kolom file, jadi tidak pernah dianggap rahasia
 * sampai pemiliknya menggantinya di sini.
 */
class ChangePasswordController extends Controller
{
    public function show()
    {
        return view('auth.change-password', [
            'title' => 'Ganti Password',
        ]);
    }

    public function store(Request $request, \App\Http\Controllers\AuthController $auth)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => Hash::make($request->string('password')),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();

        return $auth->redirectByRole($user);
    }
}