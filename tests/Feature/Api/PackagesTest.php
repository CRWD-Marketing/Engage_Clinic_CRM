<?php

namespace Tests\Feature\Api;

use App\Models\Location;
use App\Models\Package;
use App\Models\Service;

class PackagesTest extends ApiTestCase
{
    public function test_the_list_and_the_pickers(): void
    {
        $this->actingAsEmail('kavitha@engagebehavior.com');

        $page = $this->getJson($this->api('packages'))->assertOk()
            ->assertJsonStructure([
                'packages' => [['id', 'name', 'service_id', 'service', 'location_id', 'location', 'funding_type', 'delivery_mode', 'hours_per_week', 'rate', 'total_excl_vat', 'is_active', 'summary']],
                'services' => [['id', 'name']],
                'locations' => [['id', 'name']],
                'funding_types', 'delivery_modes',
            ]);
        $this->assertSame(Package::count(), count($page->json('packages')));
        $first = $page->json('packages.0');
        $this->assertEqualsWithDelta($first['hours_per_week'] * $first['rate'], $first['total_excl_vat'], 0.01);
    }

    public function test_a_package_is_created_edited_and_removed(): void
    {
        $this->actingAsEmail('kavitha@engagebehavior.com');
        $service = Service::where('is_active', true)->firstOrFail();
        $location = Location::where('is_active', true)->firstOrFail();

        $this->postJson($this->api('packages'), ['name' => '', 'funding_type' => 'Cash'])->assertStatus(422)->assertJsonValidationErrors(['name', 'funding_type']);
        $created = $this->postJson($this->api('packages'), [
            'name' => 'Summer intensive', 'service_id' => $service->id, 'location_id' => $location->id,
            'funding_type' => 'Self pay', 'delivery_mode' => 'Clinic', 'hours_per_week' => 15, 'rate' => 300,
        ])->assertCreated()
            ->assertJsonPath('message', 'Package created.')
            ->assertJsonPath('package.service', $service->name)
            ->assertJsonPath('package.total_excl_vat', 4500)
            ->assertJsonPath('package.is_active', true);
        $id = $created->json('package.id');

        $this->putJson($this->api("packages/{$id}"), ['name' => 'Summer intensive', 'hours_per_week' => 20, 'rate' => 300, 'funding_type' => 'Insurance'])->assertOk()
            ->assertJsonPath('message', 'Package updated.')
            ->assertJsonPath('package.total_excl_vat', 6000)
            ->assertJsonPath('package.funding_type', 'Insurance');

        $this->deleteJson($this->api("packages/{$id}"))->assertOk()->assertJsonPath('message', 'Package removed.');
        $this->assertDatabaseMissing('packages', ['id' => $id]);
    }

    public function test_roles_without_the_module_are_refused(): void
    {
        $this->actingAsEmail('hr@engagebehavior.com');
        $this->getJson($this->api('packages'))->assertForbidden();
    }
}
