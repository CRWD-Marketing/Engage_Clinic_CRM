<?php

namespace Tests\Feature\Api;

use App\Models\User;

class AuthTest extends ApiTestCase
{
    public function test_login_returns_a_token_and_the_user_with_their_role_template(): void
    {
        $response = $this->postJson($this->api('login'), ['email' => 'admin@gmail.com', 'password' => 'admin123']);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'public_id', 'first_name', 'last_name', 'email', 'role', 'department', 'is_active', 'modules', 'role_template']])
            ->assertJsonPath('user.email', 'admin@gmail.com')
            ->assertJsonMissingPath('user.password');

        $this->withToken($response->json('token'))->getJson($this->api('me'))
            ->assertOk()
            ->assertJsonPath('email', 'admin@gmail.com')
            ->assertJsonStructure(['role_template' => ['key', 'modules', 'actions']]);
    }

    public function test_wrong_password_and_missing_fields_are_rejected(): void
    {
        $this->postJson($this->api('login'), ['email' => 'admin@gmail.com', 'password' => 'nope'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Invalid email or password.')
            ->assertJsonPath('errors.email.0', 'Invalid email or password.');

        $this->postJson($this->api('login'), ['email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_a_suspended_account_cannot_sign_in(): void
    {
        $user = $this->user('coordinator@engagebehavior.com');
        $user->update(['is_active' => false]);

        $this->postJson($this->api('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Invalid email or password.');
    }

    public function test_repeated_failures_lock_the_account_out(): void
    {
        foreach (range(1, 5) as $i) {
            $this->postJson($this->api('login'), ['email' => 'admin@gmail.com', 'password' => 'nope'])->assertStatus(422);
        }

        $this->postJson($this->api('login'), ['email' => 'admin@gmail.com', 'password' => 'admin123'])
            ->assertStatus(429)
            ->assertJsonPath('errors.email.0', fn (string $m) => str_starts_with($m, 'Too many login attempts.'));
    }

    public function test_protected_routes_need_a_token_and_logout_revokes_it(): void
    {
        $this->getJson($this->api('me'))->assertUnauthorized()->assertJsonPath('message', 'Unauthenticated.');

        $token = $this->postJson($this->api('login'), ['email' => 'admin@gmail.com', 'password' => 'admin123'])->json('token');

        $this->withToken($token)->postJson($this->api('logout'))->assertOk();
        $this->assertSame(0, User::where('email', 'admin@gmail.com')->first()->tokens()->count());
    }

    public function test_requests_without_a_json_accept_header_still_get_json(): void
    {
        // A browser-style request: no token, no Accept header. Not a redirect to the login page.
        $this->get($this->api('me'))->assertUnauthorized()->assertJsonPath('message', 'Unauthenticated.');
        $this->post($this->api('login'), [])->assertStatus(422)->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_forgot_password_gives_the_same_answer_for_any_email(): void
    {
        $expected = 'If an account exists for that email, a password reset link has been sent.';

        $this->postJson($this->api('forgot-password'), ['email' => 'admin@gmail.com'])->assertOk()->assertJsonPath('message', $expected);
        $this->postJson($this->api('forgot-password'), ['email' => 'nobody@example.com'])->assertOk()->assertJsonPath('message', $expected);
        $this->postJson($this->api('forgot-password'), ['email' => ''])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_profile_update_saves_personal_details_and_returns_the_user(): void
    {
        $this->actingAsEmail('coordinator@engagebehavior.com');

        $this->putJson($this->api('profile'), [
            'first_name' => 'Cora', 'last_name' => 'Staff', 'email' => 'coordinator@engagebehavior.com',
            'job_title' => 'Intake coordinator', 'phone_number' => '0501112222', 'timezone' => 'Asia/Dubai (GST)', 'message_signature' => null,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.first_name', 'Cora')
            ->assertJsonPath('user.job_title', 'Intake coordinator')
            ->assertJsonStructure(['user' => ['role_template']]);

        $this->putJson($this->api('profile'), ['first_name' => '', 'last_name' => 'Staff', 'email' => 'admin@gmail.com'])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['first_name', 'email']]);
    }
}
