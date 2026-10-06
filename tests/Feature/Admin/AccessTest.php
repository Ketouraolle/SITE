<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use RefreshDatabase;

    public static function adminRoutes(): array
    {
        return [
            ['admin.dashboard'],
            ['admin.products.index'],
            ['admin.products.create'],
            ['admin.categories.index'],
            ['admin.packs.index'],
            ['admin.packs.create'],
            ['admin.orders.index'],
            ['admin.stock.index'],
            ['admin.profile.edit'],
        ];
    }

    #[DataProvider('adminRoutes')]
    public function test_le_dashboard_est_inaccessible_sans_authentification(string $name): void
    {
        $this->get(route($name))->assertRedirect(route('login'));
    }

    #[DataProvider('adminRoutes')]
    public function test_un_administrateur_connecte_accede_au_dashboard(string $name): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route($name))
            ->assertOk();
    }

    public function test_il_n_existe_aucune_route_d_inscription(): void
    {
        $this->assertFalse(
            app('router')->has('register'),
            'La table users est reservee aux administrateurs : aucune inscription publique.'
        );

        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
    }
}
