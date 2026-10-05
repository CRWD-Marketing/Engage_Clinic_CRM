<?php

namespace Tests\Feature\Api;

use App\Models\RoleTemplate;
use App\Models\User;

class RolesAccessTest extends ApiTestCase
{
    public function test_the_page_lists_users_templates_and_the_option_lists(): void
    {
        $this->actingAsEmail('hr@engagebehavior.com');

        $page = $this->getJson($this->api('roles-access'))->assertOk()
            ->assertJsonStructure([
                'users' => [[
                    'id', 'uid', 'name', 'initials', 'email', 'job_title', 'first_name', 'middle_name', 'last_name', 'phone_number', 'department',
                    'manager_id', 'start_date', 'notes', 'is_active', 'template_id', 'base_role', 'modules', 'module_levels', 'actions', 'access', 'status', 'is_me',
                ]],
                'templates' => [['id', 'key', 'name', 'description', 'base_role', 'modules', 'module_levels', 'actions', 'is_system', 'locked', 'users_count']],
                'modules', 'levels', 'actions', 'base_roles', 'departments', 'managers' => [['id', 'name']], 'department_for_role',
                'stats' => ['total', 'active', 'invited', 'suspended'],
                'can_manage',
            ])
            ->assertJsonPath('can_manage', true);
        $this->assertSame(User::count(), $page->json('stats.total'));
        $this->assertSame(1, collect($page->json('users'))->where('is_me', true)->count());
    }

    public function test_templates_are_created_edited_and_deleted_and_full_admin_stays_locked(): void
    {
        $this->actingAsEmail('hr@engagebehavior.com');

        $this->postJson($this->api('roles-access/templates'), ['name' => ''])->assertStatus(422);
        $created = $this->postJson($this->api('roles-access/templates'), [
            'name' => 'Insurance Officer', 'description' => 'Claims follow-up', 'base_role' => 'FINANCE_STAFF',
            'modules' => ['reports'], 'module_levels' => ['reports' => 'view'], 'actions' => [],
        ])->assertCreated()
            ->assertJsonPath('template.key', 'insurance_officer')
            ->assertJsonPath('template.is_system', false)
            ->assertJsonPath('template.module_levels.reports', 'view');
        // Dashboard is always added to a new template.
        $this->assertEqualsCanonicalizing(['dashboard', 'reports'], $created->json('template.modules'));
        $id = $created->json('template.id');

        $this->putJson($this->api("roles-access/templates/{$id}"), ['actions' => ['create_invoice']])->assertOk()
            ->assertJsonPath('message', 'Template updated — people already assigned keep the access they hold.')
            ->assertJsonPath('template.actions', ['create_invoice']);

        $admin = RoleTemplate::where('key', 'full_admin')->firstOrFail();
        $this->putJson($this->api("roles-access/templates/{$admin->id}"), ['actions' => []])->assertStatus(422);
        $system = RoleTemplate::where('is_system', true)->where('key', '!=', 'full_admin')->firstOrFail();
        $this->deleteJson($this->api("roles-access/templates/{$system->id}"))->assertStatus(422);

        $this->deleteJson($this->api("roles-access/templates/{$id}"))->assertOk()->assertJsonPath('message', 'Role template deleted.');
        $this->assertDatabaseMissing('role_templates', ['id' => $id]);
    }

    public function test_a_user_is_added_from_a_template_then_moved_given_extra_access_and_suspended(): void
    {
        $this->actingAsEmail('hr@engagebehavior.com');
        $therapist = RoleTemplate::where('key', 'clinical_standard')->firstOrFail();
        $supervisor = RoleTemplate::where('key', 'clinical_admin')->firstOrFail();
        $sales = RoleTemplate::where('key', 'sales')->firstOrFail();

        $this->postJson($this->api('roles-access/users'), [
            'first_name' => 'Noor', 'last_name' => 'Haddad', 'email' => 'noor.haddad@engagebehavior.com', 'password' => 'Abcd1234efgh', 'role_template_id' => $supervisor->id,
        ])->assertForbidden();

        $created = $this->postJson($this->api('roles-access/users'), [
            'first_name' => 'Noor', 'last_name' => 'Haddad', 'email' => 'noor.haddad@engagebehavior.com', 'password' => 'Abcd1234efgh',
            'role_template_id' => $therapist->id, 'job_title' => 'RBT', 'send_invite' => false,
        ])->assertCreated()
            ->assertJsonPath('user.base_role', 'THERAPIST')
            ->assertJsonPath('user.department', 'CLINICAL')
            ->assertJsonPath('user.status', 'invited')
            ->assertJsonPath('user.template_id', $therapist->id);
        $publicId = $created->json('user.id');

        $this->putJson($this->api("roles-access/users/{$publicId}/template"), ['role_template_id' => $sales->id])->assertOk()
            ->assertJsonPath('message', "Noor is now on {$sales->name}.")
            ->assertJsonPath('user.base_role', 'SALES_STAFF');

        $this->putJson($this->api("roles-access/users/{$publicId}/access"), ['modules' => ['dashboard', 'leads', 'reports'], 'module_levels' => ['leads' => 'own'], 'actions' => ['add_lead_notes']])->assertOk()
            ->assertJsonPath('message', 'Access updated for Noor.')
            ->assertJsonPath('user.modules', ['dashboard', 'leads', 'reports'])
            ->assertJsonPath('user.module_levels.leads', 'own')
            ->assertJsonPath('user.access', 3);

        $this->putJson($this->api("roles-access/users/{$publicId}"), [
            'first_name' => 'Noor', 'last_name' => 'Haddad-Ali', 'email' => 'noor.haddad@engagebehavior.com', 'role_template_id' => $sales->id, 'password' => 'NewPassw0rd',
        ])->assertOk()->assertJsonPath('message', 'Noor’s details updated. New password set.');

        $this->putJson($this->api("roles-access/users/{$publicId}/suspend"))->assertOk()->assertJsonPath('user.status', 'suspended');
        $this->putJson($this->api("roles-access/users/{$publicId}/suspend"))->assertOk()->assertJsonPath('message', 'Noor reactivated.');

        $this->deleteJson($this->api("roles-access/users/{$publicId}"))->assertOk()->assertJsonPath('message', 'Noor removed.');
        $this->assertDatabaseMissing('users', ['email' => 'noor.haddad@engagebehavior.com']);
    }

    public function test_only_a_full_admin_moves_someone_onto_a_sensitive_template(): void
    {
        $therapist = User::where('role', 'THERAPIST')->firstOrFail();
        $clinicalAdmin = RoleTemplate::where('key', 'clinical_admin')->firstOrFail();
        $fullAdmin = RoleTemplate::where('key', 'full_admin')->firstOrFail();

        $this->actingAsEmail('hr@engagebehavior.com');
        $this->putJson($this->api("roles-access/users/{$therapist->public_id}/template"), ['role_template_id' => $fullAdmin->id])->assertForbidden()
            ->assertJsonPath('message', 'Only a Full Admin can assign Full Admin.');
        $this->putJson($this->api("roles-access/users/{$therapist->public_id}/template"), ['role_template_id' => $clinicalAdmin->id])->assertForbidden();
        $this->assertSame('THERAPIST', $therapist->fresh()->role);

        $this->actingAsEmail('admin@gmail.com');
        $this->putJson($this->api("roles-access/users/{$therapist->public_id}/template"), ['role_template_id' => $clinicalAdmin->id])->assertOk();
        $this->assertSame('CLINICAL_SUPERVISOR', $therapist->fresh()->role);
    }

    public function test_the_web_page_refuses_the_same_move(): void
    {
        $therapist = User::where('role', 'THERAPIST')->firstOrFail();
        $fullAdmin = RoleTemplate::where('key', 'full_admin')->firstOrFail();

        $this->actingAs($this->user('hr@engagebehavior.com'))
            ->putJson(route('roles.users.template', $therapist), ['role_template_id' => $fullAdmin->id])
            ->assertForbidden();
        $this->assertSame('THERAPIST', $therapist->fresh()->role);
    }

    public function test_you_cannot_lock_yourself_out(): void
    {
        $me = $this->actingAsEmail('hr@engagebehavior.com');
        $basic = RoleTemplate::where('key', 'basic')->firstOrFail();

        $this->putJson($this->api("roles-access/users/{$me->public_id}/template"), ['role_template_id' => $basic->id])->assertStatus(422);
        $this->putJson($this->api("roles-access/users/{$me->public_id}/access"), ['modules' => ['dashboard'], 'actions' => []])->assertStatus(422);
        $this->putJson($this->api("roles-access/users/{$me->public_id}/suspend"))->assertStatus(422);
        $this->deleteJson($this->api("roles-access/users/{$me->public_id}"))->assertStatus(422);
    }

    public function test_roles_without_the_module_are_refused(): void
    {
        $this->actingAsEmail('kavitha@engagebehavior.com');
        $this->getJson($this->api('roles-access'))->assertForbidden();
    }
}
