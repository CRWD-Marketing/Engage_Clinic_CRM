<?php

namespace Tests\Feature\Api;

use App\Models\Insurance;
use App\Models\Location;
use App\Models\Service;

class SettingsTest extends ApiTestCase
{
    public function test_the_three_lists(): void
    {
        $this->actingAsEmail('admin@gmail.com');

        $this->getJson($this->api('settings'))->assertOk()
            ->assertJsonStructure([
                'services' => [['id', 'name', 'is_active']],
                'locations' => [['id', 'name', 'is_active']],
                'insurances' => [['id', 'name', 'is_active', 'default_coverage_percent']],
            ])
            ->assertJsonCount(Service::count(), 'services')
            ->assertJsonCount(Insurance::count(), 'insurances');
    }

    public function test_add_rename_toggle_and_remove_each_kind(): void
    {
        $this->actingAsEmail('admin@gmail.com');

        foreach (['services' => 'Parent coaching', 'locations' => 'Dubai Marina'] as $kind => $name) {
            $this->postJson($this->api("settings/{$kind}"), ['name' => ''])->assertStatus(422);
            $id = $this->postJson($this->api("settings/{$kind}"), ['name' => $name])->assertCreated()
                ->assertJsonPath('item.name', $name)->assertJsonPath('item.is_active', true)->json('item.id');
            $this->putJson($this->api("settings/{$kind}/{$id}"), ['name' => "{$name} (new)"])->assertOk()->assertJsonPath('item.name', "{$name} (new)");
            $this->patchJson($this->api("settings/{$kind}/{$id}/toggle"))->assertOk()->assertJsonPath('item.is_active', false);
            $this->patchJson($this->api("settings/{$kind}/{$id}/toggle"))->assertOk()->assertJsonPath('item.is_active', true);
            $this->deleteJson($this->api("settings/{$kind}/{$id}"))->assertOk();
        }
        $this->assertDatabaseMissing('services', ['name' => 'Parent coaching (new)']);
        $this->assertDatabaseMissing('locations', ['name' => 'Dubai Marina (new)']);

        $this->postJson($this->api('settings/insurances'), ['name' => 'Oman Insurance', 'default_coverage_percent' => 120])->assertStatus(422);
        $id = $this->postJson($this->api('settings/insurances'), ['name' => 'Oman Insurance', 'default_coverage_percent' => 70])->assertCreated()
            ->assertJsonPath('message', 'Insurance added.')
            ->assertJsonPath('item.default_coverage_percent', 70)->json('item.id');
        $this->putJson($this->api("settings/insurances/{$id}"), ['name' => 'Oman Insurance', 'default_coverage_percent' => 80])->assertOk()->assertJsonPath('item.default_coverage_percent', 80);
        $this->deleteJson($this->api("settings/insurances/{$id}"))->assertOk()->assertJsonPath('message', 'Insurance removed.');
        $this->assertNull(Insurance::find($id));
        $this->assertNotNull(Location::first());
    }

    public function test_only_roles_with_settings_get_in(): void
    {
        $this->actingAsEmail('kavitha@engagebehavior.com');
        $this->getJson($this->api('settings'))->assertForbidden();
    }
}
