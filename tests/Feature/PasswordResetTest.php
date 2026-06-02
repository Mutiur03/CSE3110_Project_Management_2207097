<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_can_be_rendered(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
    }

    public function test_reset_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertSessionHas('status', __(Password::RESET_LINK_SENT));
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create([
            'password' => 'old-password',
        ]);
        $token = Password::createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', __(Password::PASSWORD_RESET));
        $this->assertTrue(Hash::check('new-password123', $user->refresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_password_reset_requires_valid_token(): void
    {
        $user = User::factory()->create([
            'password' => 'old-password',
        ]);

        $response = $this->from(route('password.reset', ['token' => 'bad-token', 'email' => $user->email]))
            ->post(route('password.update'), [
                'token' => 'bad-token',
                'email' => $user->email,
                'password' => 'new-password123',
                'password_confirmation' => 'new-password123',
            ]);

        $response->assertRedirect(route('password.reset', ['token' => 'bad-token', 'email' => $user->email]));
        $response->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('old-password', $user->refresh()->password));
    }
}
