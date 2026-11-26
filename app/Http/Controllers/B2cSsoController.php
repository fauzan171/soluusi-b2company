<?php

namespace App\Http\Controllers;

use App\Models\Consultant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * SSO konsultan dari dashboard B2C (soluusi.com) ke portal konsultan B2B.
 *
 * Token: base64url(json{cid, exp, nonce}) . '.' . hex(hmac_sha256(payload, B2C_SSO_SECRET)).
 * cid = consultants.id di DB B2C (dipetakan via consultants.b2c_consultant_id).
 * Aturan: signature valid, exp di masa depan & maks 5 menit ke depan, nonce sekali pakai.
 */
class B2cSsoController extends Controller
{
    private const MAX_TTL = 300;

    public function __invoke(Request $request)
    {
        $secret = (string) config('services.b2c_sso.secret');
        abort_if($secret === '', 404);

        $parts = explode('.', (string) $request->query('token', ''), 2);
        abort_unless(count($parts) === 2, 403);
        [$payload, $sig] = $parts;
        abort_unless(hash_equals(hash_hmac('sha256', $payload, $secret), $sig), 403);

        $claims = json_decode((string) base64_decode(strtr($payload, '-_', '+/'), true), true);
        $cid = (int) ($claims['cid'] ?? 0);
        $exp = (int) ($claims['exp'] ?? 0);
        $nonce = (string) ($claims['nonce'] ?? '');
        $now = time();
        abort_unless($cid > 0 && strlen($nonce) >= 16 && $exp > $now && $exp <= $now + self::MAX_TTL, 403);

        // Nonce sekali pakai: add() atomik, gagal kalau sudah ada (replay).
        abort_unless(Cache::add('b2c-sso-nonce:'.hash('sha256', $nonce), 1, self::MAX_TTL + 60), 403);

        $consultant = Consultant::with('user')
            ->where('b2c_consultant_id', $cid)->where('is_active', 1)->first();
        $user = $consultant?->user;
        abort_unless($user && $user->is_active, 403);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('consultant.sessions');
    }
}
