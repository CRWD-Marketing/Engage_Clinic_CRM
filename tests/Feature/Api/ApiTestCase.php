<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Base for the mobile API tests: a freshly migrated database seeded with the
 * demo clinic (DatabaseSeeder), and helpers to call /api/v1 as a seeded user.
 *
 * The migrations use MySQL-only SQL, so run these against a MySQL test
 * database rather than phpunit.xml's in-memory SQLite:
 *
 *   DB_CONNECTION=mysql DB_DATABASE=engage_clinic_testing php artisan test tests/Feature/Api
 */
abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    /** Seed once after migrating; each test then runs in a rolled-back transaction. */
    protected bool $seed = true;

    /**
     * `$seed` only seeds when this process is the one that migrates. When
     * other test classes ran first the tables already exist but are empty,
     * so seed here instead - inside this test's transaction, which keeps the
     * demo data out of those other tests.
     */
    protected function afterRefreshingDatabase()
    {
        if (User::query()->doesntExist()) {
            $this->seed();
        }
    }

    protected function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    /** Authenticate the following requests as the seeded user with this email. */
    protected function actingAsEmail(string $email): User
    {
        $user = $this->user($email);
        Sanctum::actingAs($user);

        return $user;
    }

    protected function api(string $path): string
    {
        return '/api/v1/'.ltrim($path, '/');
    }
}
