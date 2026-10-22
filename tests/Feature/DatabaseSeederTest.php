<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_does_not_create_default_admin_or_demo_catalog_in_testing(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_seeder_preserves_existing_administrator(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@maboutique.cm',
        ]);

        $originalAttributes = $admin->getAttributes();

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $actualAttributes = $admin->fresh()->getAttributes();

        ksort($originalAttributes);
        ksort($actualAttributes);

        $this->assertSame($originalAttributes, $actualAttributes);
    }
}
