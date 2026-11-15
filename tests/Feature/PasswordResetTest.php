<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email = 'reset@test.local'): User
    {
        $roleId = DB::table('roles')->where('name', 'employee')->value('id')
            ?? DB::table('roles')->insertGetId(['name' => 'employee', 'created_at' => now(), 'updated_at' => now()]);

        return User::create([
            'uuid' => Str::uuid(), 'firstname' => 'Reset', 'lastname' => 'User',
            'username' => Str::slug($email), 'email' => $email,
            'password' => bcrypt('old-password'), 'is_active' => true, 'role_id' => $roleId,
        ]);
    }

    public function test_forgot_password_page_renders()
    {
        $this->get(route('password.request'))->assertOk();
    }

    public function test_reset_link_is_sent_for_known_email()
    {
        Notification::fake();
        $user = $this->user();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
        $this->assertDatabaseHas('password_resets', ['email' => $user->email]);
    }

    public function test_reset_link_request_requires_valid_email()
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'bukan-email'])
            ->assertSessionHasErrors('email');

        $this->post(route('password.email'), ['email' => 'ghost@test.local'])
            ->assertSessionHasErrors('email');
    }

    public function test_user_can_reset_password_with_valid_token()
    {
        Notification::fake();
        $user = $this->user();

        $this->post(route('password.email'), ['email' => $user->email]);

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk();

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('loginIndex'));

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        // Token terpakai → dihapus.
        $this->assertDatabaseMissing('password_resets', ['email' => $user->email]);
    }

    public function test_reset_fails_with_invalid_token()
    {
        $user = $this->user();

        $this->post(route('password.update'), [
            'token' => 'token-ngasal',
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }
}
