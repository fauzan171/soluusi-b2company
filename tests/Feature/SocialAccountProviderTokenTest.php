<?php

namespace Tests\Feature;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

// provider_token tertulis oleh AuthController SSO callback; kolom sekarang
// ada di DB dan fillable, jadi persisten.
class SocialAccountProviderTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_token_persists_on_social_account(): void
    {
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'employee', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $user = User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Soc', 'lastname' => 'User',
            'username' => 'soc_user', 'email' => 'soc@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $roleId,
        ]);

        $account = SocialAccount::create([
            'user_id' => $user->id,
            'provider_name' => 'google',
            'provider_id' => 'goog-123',
            'provider_token' => 'fake-token-abc',
        ]);

        $this->assertSame('fake-token-abc', $account->fresh()->provider_token);
    }
}
