<?php

namespace Tests\Feature\Api;

use App\Models\User;

class UserManagementTest extends ApiTestCase
{
    private function body(array $overrides = []): array
    {
        return $overrides + [
            'first_name' => 'Mira',
            'last_name' => 'Santos',
            'email' => 'mira.santos@engagebehavior.com',
            'phone_number' => '0501112233',
            'password' => 'Str0ng!pass',
            'password_confirmation' => 'Str0ng!pass',
            'department' => 'CLINICAL',
            'role' => 'THERAPIST',
            'start_date' => '2026-10-05',
            'is_active' => true,
        ];
    }

    public function test_the_list_pages_filters_and_carries_the_stats_and_options(): void
    {
        $this->actingAsEmail('hr@engagebehavior.com');

        $all = $this->getJson($this->api('users'))->assertOk()
            ->assertJsonStructure([
                'users' => [['id', 'public_id', 'first_name', 'last_name', 'email', 'department', 'role', 'is_active', 'manager']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'stats' => ['total', 'active', 'inactive', 'departments'],
                'departments', 'roles', 'managers' => [['id', 'name']], 'can_delete',
            ])
            ->assertJsonPath('can_delete', false)
            ->assertJsonPath('meta.per_page', 10);
        $this->assertSame(User::count(), $all->json('stats.total'));
        $this->assertArrayNotHasKey('password', $all->json('users.0'));

        $therapists = $this->getJson($this->api('users').'?role=THERAPIST')->assertOk()->json('users');
        $this->assertNotEmpty($therapists);
        $this->assertSame(['THERAPIST'], array_values(array_unique(array_column($therapists, 'role'))));

        $found = $this->getJson($this->api('users').'?search=indira')->assertOk()->json('users');
        $this->assertSame(['indira@engagebehavior.com'], array_column($found, 'email'));
        $this->assertSame(2, $this->getJson($this->api('users').'?page=2')->assertOk()->json('meta.current_page'));
    }

    public function test_the_profile_has_the_manager_and_direct_reports(): void
    {
        $this->actingAsEmail('hr@engagebehavior.com');
        $manager = User::has('subordinates')->firstOrFail();

        $this->getJson($this->api("users/{$manager->public_id}"))->assertOk()
            ->assertJsonPath('email', $manager->email)
            ->assertJsonStructure(['subordinates' => [['id', 'public_id', 'first_name', 'last_name', 'role']], 'manager', 'can_delete'])
            ->assertJsonMissingPath('password');
    }

    public function test_hr_adds_and_edits_a_therapist_but_cannot_hand_out_sensitive_roles_or_delete(): void
    {
        $this->actingAsEmail('hr@engagebehavior.com');

        $this->postJson($this->api('users'), $this->body(['password_confirmation' => 'different']))->assertStatus(422)->assertJsonValidationErrors('password');
        $this->postJson($this->api('users'), $this->body(['role' => 'FULL_ADMIN']))->assertForbidden();

        $created = $this->postJson($this->api('users'), $this->body())->assertCreated()
            ->assertJsonPath('message', 'User created successfully.')
            ->assertJsonPath('data.role', 'THERAPIST');
        $user = User::where('email', 'mira.santos@engagebehavior.com')->firstOrFail();
        $this->assertSame($user->public_id, $created->json('data.public_id'));

        $this->putJson($this->api("users/{$user->public_id}"), ['job_title' => 'x', 'first_name' => 'Mirabel', 'is_active' => false])->assertOk()
            ->assertJsonPath('data.first_name', 'Mirabel')
            ->assertJsonPath('data.is_active', false);
        $this->putJson($this->api("users/{$user->public_id}"), ['role' => 'CLINICAL_SUPERVISOR'])->assertForbidden();
        $this->deleteJson($this->api("users/{$user->public_id}"))->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_a_full_admin_deletes_a_user(): void
    {
        $this->actingAsEmail('admin@gmail.com');
        $user = User::where('role', 'THERAPIST')->firstOrFail();

        $this->getJson($this->api('users'))->assertJsonPath('can_delete', true);
        $this->deleteJson($this->api("users/{$user->public_id}"))->assertOk()->assertJsonPath('message', 'User deleted successfully.');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_roles_without_the_module_are_refused(): void
    {
        $this->actingAsEmail('indira@engagebehavior.com');
        $this->getJson($this->api('users'))->assertForbidden();
    }
}
