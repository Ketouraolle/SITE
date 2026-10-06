<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Orders\Index;
use App\Livewire\Admin\Orders\Show;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
    }

    /**
     * Passe une commande cote client pour disposer de donnees realistes.
     *
     * @return array{0: Order, 1: Product}
     */
    private function placeOrder(int $stock = 10, int $quantity = 3): array
    {
        $product = Product::factory()->stock($stock)->price(1000)->create();

        app(CartService::class)->addProduct($product, $quantity);

        app(OrderService::class)->place([
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '699000000',
            'customer_address' => 'Yaounde',
        ]);

        return [Order::firstOrFail(), $product->fresh()];
    }

    public function test_le_detail_d_une_commande_s_affiche(): void
    {
        [$order] = $this->placeOrder();

        $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $order->number))
            ->assertOk()
            ->assertSeeLivewire(Show::class)
            ->assertSee($order->number)
            ->assertSee('Jean Mbarga')
            ->assertSee('699000000')
            ->assertSee('Yaounde');

        Livewire::actingAs($this->admin)
            ->test(Show::class, ['order' => $order])
            ->assertViewHas('detail', fn (array $detail) => $detail['number'] === $order->number
                && $detail['customer_name'] === 'Jean Mbarga'
                && count($detail['items']) === 1)
            ->assertViewHas('statuses', fn (array $statuses) => count($statuses) === count(OrderStatus::cases()));
    }

    public function test_le_statut_peut_etre_modifie(): void
    {
        [$order] = $this->placeOrder();

        Livewire::actingAs($this->admin)
            ->test(Show::class, ['order' => $order])
            ->call('updateStatus', 'confirmed')
            ->assertHasNoErrors()
            ->assertDispatched('toast', type: 'success', message: 'Statut de la commande mis a jour.');

        $order->refresh();

        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertNotNull($order->confirmed_at);
    }

    public function test_un_statut_inconnu_est_refuse(): void
    {
        [$order] = $this->placeOrder();

        Livewire::actingAs($this->admin)
            ->test(Show::class, ['order' => $order])
            ->call('updateStatus', 'nawak')
            ->assertHasErrors('status');

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_annuler_une_commande_rend_le_stock(): void
    {
        [$order, $product] = $this->placeOrder(stock: 10, quantity: 3);

        $this->assertSame(7, $product->stock);

        Livewire::actingAs($this->admin)
            ->test(Show::class, ['order' => $order])
            ->call('updateStatus', 'cancelled');

        $this->assertSame(10, $product->fresh()->stock);
        $this->assertNotNull($order->fresh()->stock_released_at);
    }

    public function test_reactiver_une_commande_annulee_reprend_le_stock(): void
    {
        [$order, $product] = $this->placeOrder(stock: 10, quantity: 3);

        app(OrderService::class)->changeStatus($order, OrderStatus::Cancelled);
        $this->assertSame(10, $product->fresh()->stock);

        app(OrderService::class)->changeStatus($order->fresh(), OrderStatus::Confirmed);

        $this->assertSame(7, $product->fresh()->stock);
        $this->assertNull($order->fresh()->stock_released_at);
    }

    public function test_annuler_deux_fois_ne_credite_le_stock_qu_une_fois(): void
    {
        [$order, $product] = $this->placeOrder(stock: 10, quantity: 3);

        app(OrderService::class)->changeStatus($order, OrderStatus::Cancelled);
        app(OrderService::class)->changeStatus($order->fresh(), OrderStatus::Cancelled);

        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_la_recherche_de_commandes_fonctionne(): void
    {
        [$order] = $this->placeOrder();

        $this->actingAs($this->admin)
            ->get(route('admin.orders.index', ['q' => $order->number]))
            ->assertOk()
            ->assertSeeLivewire(Index::class)
            ->assertSee($order->number);

        Livewire::actingAs($this->admin)
            ->withQueryParams(['q' => $order->number])
            ->test(Index::class)
            ->assertViewHas('orders', fn (LengthAwarePaginator $orders) => $orders->total() === 1)
            ->set('q', 'introuvable-xyz')
            ->assertViewHas('orders', fn (LengthAwarePaginator $orders) => $orders->total() === 0);
    }

    public function test_le_filtre_par_statut_fonctionne(): void
    {
        [$order] = $this->placeOrder();

        Livewire::actingAs($this->admin)
            ->withQueryParams(['status' => 'pending'])
            ->test(Index::class)
            ->assertViewHas('orders', fn (LengthAwarePaginator $orders) => $orders->total() === 1)
            ->call('setStatus', 'delivered')
            ->assertSet('status', 'delivered')
            ->assertViewHas('orders', fn (LengthAwarePaginator $orders) => $orders->total() === 0);

        $this->assertSame(OrderStatus::Pending, $order->status);
    }

    public function test_le_jeton_public_n_est_pas_expose_dans_les_props(): void
    {
        [$order] = $this->placeOrder();

        $this->assertNotEmpty($order->public_token);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $order->number))
            ->assertOk()
            ->assertDontSee($order->public_token, false);

        Livewire::actingAs($this->admin)
            ->test(Show::class, ['order' => $order])
            ->assertViewHas('detail', fn (array $detail) => ! array_key_exists('public_token', $detail))
            ->call('updateStatus', 'confirmed')
            ->assertDontSee($order->public_token, false);
    }

    public function test_le_tableau_de_bord_agrege_les_indicateurs(): void
    {
        $this->placeOrder(stock: 10, quantity: 3);
        Product::factory()->outOfStock()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeLivewire(Dashboard::class);

        Livewire::actingAs($this->admin)
            ->test(Dashboard::class)
            ->assertViewHas('stats', fn (array $stats) => $stats['orders_total'] === 1
                && $stats['orders_pending'] === 1
                && $stats['products_out_of_stock'] === 1)
            ->assertViewHas('series', fn (Collection $series) => $series->count() === 14
                && $series->last()['orders'] === 1)
            ->assertViewHas('statusBreakdown', fn (Collection $breakdown) => $breakdown->count() === 5);
    }
}
