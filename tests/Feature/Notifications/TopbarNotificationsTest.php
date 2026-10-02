<?php

namespace Tests\Feature\Notifications;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopbarNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'FULL_ADMIN', 'department' => 'EXECUTIVE']);
    }

    private function lead(string $name): Lead
    {
        return Lead::create([
            'parent_guardian_name' => $name,
            'child_name' => $name,
            'phone' => '+971501230103',
            'status' => Lead::STATUS_NEW,
        ]);
    }

    public function test_clicking_a_notification_lowers_the_count_for_that_user_only(): void
    {
        $admin = $this->admin();
        $other = $this->admin();
        $first = $this->lead('Yusuf');
        $this->lead('Mira');

        $this->actingAs($admin)->getJson(route('notifications.index'))
            ->assertJsonPath('count', 2)
            ->assertJsonPath('items.0.read', false);

        $this->actingAs($admin)->postJson(route('notifications.read', 'lead:'.$first->id))
            ->assertJsonPath('count', 1);

        $items = collect($this->actingAs($admin)->getJson(route('notifications.index'))->json('items'))->keyBy('id');
        $this->assertTrue($items['lead:'.$first->id]['read']);

        $this->actingAs($other)->getJson(route('notifications.index'))->assertJsonPath('count', 2);
    }

    public function test_mark_all_as_read_clears_the_badge_until_something_new_arrives(): void
    {
        $admin = $this->admin();
        $this->lead('Yusuf');
        $this->lead('Mira');

        $this->actingAs($admin)->postJson(route('notifications.read-all'))->assertJsonPath('count', 0);
        $this->actingAs($admin)->getJson(route('notifications.index'))
            ->assertJsonPath('count', 0)
            ->assertJsonCount(2, 'items');

        $this->travel(1)->minutes();
        $this->lead('Marco');

        $this->actingAs($admin)->getJson(route('notifications.index'))->assertJsonPath('count', 1);
    }
}
