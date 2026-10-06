<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Livewire\Admin\Statistics;
use App\Models\Order;
use App\Models\Pack;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Tests\TestCase;

class StatisticsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Passe une commande cote client avec les lignes donnees.
     *
     * @param  array<int, array{item: Product|Pack, quantity: int}>  $lines
     */
    private function placeOrder(array $lines): Order
    {
        $cart = app(CartService::class);

        foreach ($lines as $line) {
            $line['item'] instanceof Pack
                ? $cart->addPack($line['item'], $line['quantity'])
                : $cart->addProduct($line['item'], $line['quantity']);
        }

        return app(OrderService::class)->place([
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '699000000',
            'customer_address' => 'Yaounde',
        ]);
    }

    public function test_les_produits_sont_classes_par_quantite_commandee_packs_inclus(): void
    {
        $parfum = Product::factory()->stock(50)->price(1000)->create(['name' => 'Parfum']);
        $collier = Product::factory()->stock(50)->price(2000)->create(['name' => 'Collier']);
        $pagne = Product::factory()->stock(50)->price(3000)->create(['name' => 'Pagne']);

        $pack = Pack::factory()->price(2500)->create(['name' => 'Pack fete']);
        $pack->items()->create(['product_id' => $collier->id, 'quantity' => 2]);

        $this->placeOrder([
            ['item' => $parfum, 'quantity' => 3],
            ['item' => $pack, 'quantity' => 2],
        ]);

        $cancelled = $this->placeOrder([
            ['item' => $pagne, 'quantity' => 10],
        ]);
        $cancelled->update(['status' => OrderStatus::Cancelled]);

        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.statistics'))
            ->assertOk()
            ->assertSeeLivewire(Statistics::class)
            ->assertSee('Collier');

        Livewire::actingAs($admin)
            ->test(Statistics::class)
            ->assertViewHas('summary', fn (array $summary) => $summary['orders'] === 1 && $summary['units'] === 7)
            ->assertViewHas('topProducts', fn (Collection $products) => $products->count() === 2
                && $products[0]['name'] === 'Collier'
                && $products[0]['units'] === 4
                && $products[0]['units_in_packs'] === 4
                && $products[1]['name'] === 'Parfum'
                && $products[1]['units'] === 3)
            ->assertViewHas('topPacks', fn (Collection $packs) => $packs[0]['name'] === 'Pack fete' && $packs[0]['units'] === 2)
            ->assertViewHas('neverOrdered', fn (Collection $products) => $products[0]['name'] === 'Pagne');
    }

    public function test_la_periode_exclut_les_commandes_anciennes(): void
    {
        $parfum = Product::factory()->stock(50)->price(1000)->create();

        $order = $this->placeOrder([
            ['item' => $parfum, 'quantity' => 2],
        ]);
        $order->forceFill(['created_at' => now()->subDays(40)])->save();

        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->withQueryParams(['period' => '30'])
            ->test(Statistics::class)
            ->assertViewHas('topProducts', fn (Collection $products) => $products->isEmpty())
            ->call('selectPeriod', 'all')
            ->assertSet('period', 'all')
            ->assertViewHas('topProducts', fn (Collection $products) => $products[0]['units'] === 2);

        Livewire::actingAs($admin)
            ->withQueryParams(['period' => 'nawak'])
            ->test(Statistics::class)
            ->assertSet('period', '30');
    }

    public function test_les_statistiques_sont_reservees_aux_administrateurs(): void
    {
        $this->get(route('admin.statistics'))->assertRedirect(route('login'));
    }
}
