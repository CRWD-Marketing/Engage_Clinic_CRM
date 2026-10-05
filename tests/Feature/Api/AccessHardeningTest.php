<?php

namespace Tests\Feature\Api;

use App\Models\RoleTemplate;
use App\Models\User;

/**
 * Only a Full Admin may touch a Full Admin or Clinical Supervisor account, or
 * hand out Billing, Settings or "Manage users & roles". Anyone else who
 * manages users & roles (HR) keeps every other change.
 */
class AccessHardeningTest extends ApiTestCase
{
    private function grants(User $u, array $modules, array $actions = null): array
    {
        return ['modules' => $modules, 'module_levels' => [], 'actions' => $actions ?? ($u->effectiveActions() ?? [])];
    }

    public function test_hr_cannot_change_a_full_admin_or_clinical_supervisor_account(): void
    {
        $this->actingAsEmail('hr@engagebehavior.com');
        $admin = $this->user('admin@gmail.com');
        $supervisor = $this->user('indira@engagebehavior.com');
        $basic = RoleTemplate::where('key', 'basic')->firstOrFail();

        foreach ([$admin, $supervisor] as $u) {
            $id = $u->public_id;
            $this->putJson($this->api("roles-access/users/{$id}/template"), ['role_template_id' => $basic->id])->assertForbidden();
            $this->putJson($this->api("roles-access/users/{$id}/access"), $this->grants($u, ['dashboard']))->assertForbidden();
            $this->putJson($this->api("roles-access/users/{$id}/suspend"))->assertForbidden();
            $this->deleteJson($this->api("roles-access/users/{$id}"))->assertForbidden();
            $this->putJson($this->api("roles-access/users/{$id}"), [
                'first_name' => $u->first_name, 'last_name' => $u->last_name, 'email' => 'taken.over@example.com', 'role_template_id' => $u->role_template_id, 'password' => 'Takeover123',
            ])->assertForbidden();
            // User Management: same rule, so the email / password can't be swapped there either.
            $this->putJson($this->api("users/{$id}"), ['email' => 'taken.over@example.com'])->assertForbidden();
        }

        $this->putJson($this->api("roles-access/users/{$supervisor->public_id}/suspend"))
            ->assertJsonPath('message', 'Only a Full Admin can change the access of a Clinical Supervisor.');
        $this->assertSame('admin@gmail.com', $admin->fresh()->email);
        $this->assertTrue($supervisor->fresh()->is_active);
    }

    public function test_hr_cannot_grant_billing_settings_or_manage_users_but_can_grant_the_rest(): void
    {
        $me = $this->actingAsEmail('hr@engagebehavior.com');
        $therapist = User::where('role', 'THERAPIST')->firstOrFail();
        $mine = $me->effectiveModules();

        $this->putJson($this->api("roles-access/users/{$therapist->public_id}/access"), $this->grants($therapist, ['dashboard', 'patients', 'calendar', 'billing']))
            ->assertForbidden()->assertJsonPath('message', 'Only a Full Admin can grant Billing.');
        $this->putJson($this->api("roles-access/users/{$therapist->public_id}/access"), $this->grants($therapist, ['dashboard', 'patients', 'calendar'], ['manage_users_roles']))
            ->assertForbidden()->assertJsonPath('message', 'Only a Full Admin can grant Manage users & roles.');
        // Not even to themselves.
        $this->putJson($this->api("roles-access/users/{$me->public_id}/access"), $this->grants($me, array_merge($mine, ['billing', 'settings'])))
            ->assertForbidden()->assertJsonPath('message', 'Only a Full Admin can grant Billing, Settings.');

        // Everything else still works: add Leads and Reports, take Calendar away.
        $this->putJson($this->api("roles-access/users/{$therapist->public_id}/access"), $this->grants($therapist, ['dashboard', 'patients', 'leads', 'reports']))
            ->assertOk()->assertJsonPath('user.modules', ['dashboard', 'patients', 'leads', 'reports']);
    }

    public function test_hr_cannot_put_protected_grants_on_a_template_or_edit_a_sensitive_one(): void
    {
        $this->actingAsEmail('hr@engagebehavior.com');
        $staffAdmin = RoleTemplate::where('key', 'staff_admin')->firstOrFail();
        $clinicalAdmin = RoleTemplate::where('key', 'clinical_admin')->firstOrFail();
        $sales = RoleTemplate::where('key', 'sales')->firstOrFail();

        $this->postJson($this->api('roles-access/templates'), ['name' => 'Cashier', 'modules' => ['billing'], 'actions' => []])->assertForbidden();
        $this->putJson($this->api("roles-access/templates/{$staffAdmin->id}"), ['modules' => array_merge($staffAdmin->modules, ['settings'])])
            ->assertForbidden()->assertJsonPath('message', 'Only a Full Admin can grant Settings.');
        $this->putJson($this->api("roles-access/templates/{$clinicalAdmin->id}"), ['description' => 'Changed'])
            ->assertForbidden()->assertJsonPath('message', 'Only a Full Admin can change the Clinical Admin Access template.');

        // Ordinary template work is unchanged.
        $this->postJson($this->api('roles-access/templates'), ['name' => 'Front desk', 'modules' => ['leads', 'contacts'], 'actions' => ['add_lead_notes']])->assertCreated();
        $this->putJson($this->api("roles-access/templates/{$sales->id}"), ['modules' => array_values(array_diff($sales->modules, ['reports']))])->assertOk();
        // Sending a template's existing protected grants back unchanged is fine.
        $this->putJson($this->api("roles-access/templates/{$staffAdmin->id}"), ['actions' => $staffAdmin->actions, 'description' => 'HR team'])->assertOk();
    }

    public function test_a_full_admin_can_still_do_all_of_it(): void
    {
        $this->actingAsEmail('admin@gmail.com');
        $supervisor = $this->user('indira@engagebehavior.com');
        $therapist = User::where('role', 'THERAPIST')->firstOrFail();

        $this->putJson($this->api("roles-access/users/{$therapist->public_id}/access"), $this->grants($therapist, ['dashboard', 'billing', 'settings'], ['manage_users_roles']))->assertOk();
        $this->postJson($this->api('roles-access/templates'), ['name' => 'Cashier', 'modules' => ['billing'], 'actions' => ['create_invoice']])->assertCreated();
        $this->putJson($this->api("roles-access/users/{$supervisor->public_id}/suspend"))->assertOk()->assertJsonPath('user.status', 'suspended');
        $this->putJson($this->api("users/{$supervisor->public_id}"), ['phone_number' => '0500000000'])->assertOk();
    }
}
