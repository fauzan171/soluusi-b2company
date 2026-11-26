<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * SSO dari dashboard konsultan B2C → portal konsultan B2B.
 * Token = base64url(json{cid,exp,nonce}) . '.' . hex(hmac_sha256(payload, secret)).
 */
class B2cSsoTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-sso-secret-0123456789abcdef';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.b2c_sso.secret' => self::SECRET]);
    }

    private function linkedConsultant(int $b2cId = 77, bool $active = true): User
    {
        $roleId = DB::table('roles')->where('name', 'consultant')->value('id')
            ?? DB::table('roles')->insertGetId(['name' => 'consultant']);
        $user = User::create([
            'uuid' => (string) Str::uuid(), 'username' => 'b2c-'.$b2cId,
            'email' => 'b2c-'.$b2cId.'@b2b.local', 'password' => Str::random(32),
            'firstname' => 'Fake Konsultan', 'lastname' => '', 'role_id' => $roleId,
            'is_active' => $active ? 1 : 0,
        ]);
        Consultant::create([
            'user_id' => $user->id, 'about' => '', 'uuid' => (string) Str::uuid(),
            'profit_sharing' => 0, 'status' => Consultant::ACCEPT,
            'is_active' => $active ? 1 : 0, 'b2c_consultant_id' => $b2cId,
        ]);

        return $user;
    }

    private function token(array $claims, string $secret = self::SECRET): string
    {
        $payload = rtrim(strtr(base64_encode(json_encode($claims)), '+/', '-_'), '=');

        return $payload.'.'.hash_hmac('sha256', $payload, $secret);
    }

    private function claims(int $cid = 77, int $ttl = 60): array
    {
        return ['cid' => $cid, 'exp' => time() + $ttl, 'nonce' => Str::random(24)];
    }

    public function test_valid_token_logs_consultant_in(): void
    {
        $user = $this->linkedConsultant();

        $this->get('/sso/b2c?token='.$this->token($this->claims()))
            ->assertRedirect(route('consultant.sessions'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_bad_signature_is_rejected(): void
    {
        $this->linkedConsultant();

        $this->get('/sso/b2c?token='.$this->token($this->claims(), 'wrong-secret'))->assertForbidden();
        $this->assertGuest();
    }

    public function test_expired_token_is_rejected(): void
    {
        $this->linkedConsultant();

        $this->get('/sso/b2c?token='.$this->token($this->claims(77, -5)))->assertForbidden();
        $this->assertGuest();
    }

    public function test_token_lifetime_over_five_minutes_is_rejected(): void
    {
        $this->linkedConsultant();

        $this->get('/sso/b2c?token='.$this->token($this->claims(77, 3600)))->assertForbidden();
        $this->assertGuest();
    }

    public function test_replayed_token_is_rejected(): void
    {
        $this->linkedConsultant();
        $token = $this->token($this->claims());

        $this->get('/sso/b2c?token='.$token)->assertRedirect();
        auth()->logout();
        $this->get('/sso/b2c?token='.$token)->assertForbidden();
        $this->assertGuest();
    }

    public function test_unknown_or_inactive_consultant_is_rejected(): void
    {
        $this->linkedConsultant(88, active: false);

        $this->get('/sso/b2c?token='.$this->token($this->claims(99)))->assertForbidden();
        $this->get('/sso/b2c?token='.$this->token($this->claims(88)))->assertForbidden();
        $this->assertGuest();
    }

    public function test_disabled_when_secret_missing(): void
    {
        config(['services.b2c_sso.secret' => null]);
        $this->linkedConsultant();

        $this->get('/sso/b2c?token='.$this->token($this->claims(), ''))->assertNotFound();
        $this->assertGuest();
    }

    public function test_malformed_token_is_rejected(): void
    {
        $this->get('/sso/b2c?token=garbage')->assertForbidden();
        $this->get('/sso/b2c')->assertForbidden();
        $this->assertGuest();
    }
}
