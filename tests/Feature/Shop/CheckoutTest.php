<?php

namespace Tests\Feature\Shop;

use App\Enums\OrderStatus;
use App\Livewire\AddToCart;
use App\Livewire\Shop\Cart;
use App\Livewire\Shop\Checkout;
use App\Livewire\Shop\Confirmation;
use App\Models\Order;
use App\Models\Pack;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('shop.whatsapp.number', '237699000000');
        config()->set('shop.currency', 'FCFA');
    }

    private function addToCart(string $kind, int $id, int $quantity = 1): void
    {
        Livewire::test(AddToCart::class, [
            'kind' => $kind,
            'itemId' => $id,
            'orderable' => true,
            'maxQuantity' => 99,
        ])->set('quantity', $quantity)->call('add')->assertHasNoErrors();
    }

    /**
     * @param  array<string, string>  $customer
     */
    private function submitCheckout(array $customer = []): Testable
    {
        $component = Livewire::test(Checkout::class);

        foreach ($customer as $field => $value) {
            $component->set($field, $value);
        }

        return $component->call('place');
    }

    /**
     * @return array{0: Product, 1: Testable}
     */
    private function placeSimpleOrder(int $stock = 10, int $quantity = 2): array
    {
        $product = Product::factory()->stock($stock)->price(2500)->create(['name' => 'Produit A']);

        $this->addToCart('product', $product->id, $quantity);

        $component = $this->submitCheckout([
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '+237 699 00 00 00',
            'customer_address' => 'Yaounde, Bastos',
        ]);

        return [$product->fresh(), $component];
    }

    public function test_la_page_de_commande_redirige_si_le_panier_est_vide(): void
    {
        $this->get(route('checkout.create'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error', 'Votre panier est vide.');
    }

    public function test_la_page_de_commande_redirige_si_un_article_est_bloque(): void
    {
        $product = Product::factory()->stock(5)->create();
        $this->addToCart('product', $product->id, 5);
        $product->update(['stock' => 2]);

        $this->get(route('checkout.create'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');
    }

    public function test_la_page_de_commande_s_affiche_avec_un_panier(): void
    {
        $product = Product::factory()->stock(10)->create();
        $this->addToCart('product', $product->id);

        $this->get(route('checkout.create'))
            ->assertOk()
            ->assertSeeLivewire(Checkout::class)
            ->assertSee($product->name);

        Livewire::test(Checkout::class)
            ->assertViewHas('cart', fn (array $cart): bool => count($cart['lines']) === 1)
            ->assertViewHas('whatsappConfigured', true);
    }

    public function test_une_commande_est_enregistree_et_le_stock_decremente(): void
    {
        [$product, $component] = $this->placeSimpleOrder(stock: 10, quantity: 2);

        $order = Order::firstOrFail();

        $component->assertHasNoErrors()->assertRedirect(route('checkout.confirmation', [
            'order' => $order->number,
            'token' => $order->public_token,
        ]));

        $this->assertSame('CMD-'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT), $order->number);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame('Jean Mbarga', $order->customer_name);
        $this->assertSame('5000.00', $order->total);
        $this->assertSame(2, $order->items_count);
        $this->assertSame(8, $product->stock);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'name' => 'Produit A',
        ]);
    }

    public function test_le_panier_est_vide_apres_la_commande(): void
    {
        $this->placeSimpleOrder();

        Livewire::test(Cart::class)
            ->assertViewHas('cart', fn (array $cart): bool => $cart['lines'] === []);
    }

    public function test_le_message_whatsapp_est_genere(): void
    {
        $this->placeSimpleOrder(quantity: 2);

        $order = Order::firstOrFail();

        $this->assertStringContainsString("commande #{$order->number}", $order->whatsapp_message);
        $this->assertStringContainsString('Produit A x 2', $order->whatsapp_message);
        $this->assertStringContainsString('Total : 5', $order->whatsapp_message);
        $this->assertStringContainsString('Adresse : Yaounde, Bastos', $order->whatsapp_message);
    }

    public function test_la_page_de_confirmation_expose_le_lien_whatsapp(): void
    {
        $this->placeSimpleOrder();

        $order = Order::firstOrFail();

        $this->get(route('checkout.confirmation', [
            'order' => $order->number,
            'token' => $order->public_token,
        ]))
            ->assertOk()
            ->assertSeeLivewire(Confirmation::class)
            ->assertSee($order->number)
            ->assertSee('https://wa.me/237699000000?text=', false);

        Livewire::test(Confirmation::class, ['order' => $order, 'token' => $order->public_token])
            ->assertViewHas('details', fn (array $details): bool => $details['number'] === $order->number)
            ->assertViewHas('whatsappUrl', fn (string $url): bool => str_starts_with($url, 'https://wa.me/237699000000?text='));
    }

    public function test_la_confirmation_est_inaccessible_sans_le_bon_jeton(): void
    {
        $this->placeSimpleOrder();

        $order = Order::firstOrFail();

        $this->get(route('checkout.confirmation', [
            'order' => $order->number,
            'token' => 'jeton-invalide',
        ]))->assertNotFound();
    }

    public function test_les_coordonnees_sont_obligatoires(): void
    {
        $product = Product::factory()->stock(10)->create();
        $this->addToCart('product', $product->id);

        $this->submitCheckout()
            ->assertHasErrors(['customer_name', 'customer_phone', 'customer_address'])
            ->assertNoRedirect();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_un_numero_de_telephone_invalide_est_refuse(): void
    {
        $product = Product::factory()->stock(10)->create();
        $this->addToCart('product', $product->id);

        $this->submitCheckout([
            'customer_name' => 'Jean',
            'customer_phone' => 'pas-un-numero',
            'customer_address' => 'Yaounde',
        ])->assertHasErrors(['customer_phone' => 'regex']);
    }

    public function test_une_commande_echoue_si_le_stock_a_baisse_entre_temps(): void
    {
        $product = Product::factory()->stock(5)->create(['name' => 'Produit rare']);

        $this->addToCart('product', $product->id, 5);

        $component = Livewire::test(Checkout::class)
            ->set('customer_name', 'Jean')
            ->set('customer_phone', '699000000')
            ->set('customer_address', 'Yaounde');

        // Le stock fond avant la validation : la commande doit etre refusee.
        $product->update(['stock' => 1]);

        $component->call('place')
            ->assertHasErrors('cart')
            ->assertNoRedirect()
            ->assertSee("Votre commande n'a pas pu etre enregistree", false);

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_une_commande_de_pack_decremente_le_stock_de_chaque_produit(): void
    {
        $riz = Product::factory()->stock(10)->price(6000)->create();
        $huile = Product::factory()->stock(10)->price(4000)->create();

        $pack = Pack::factory()->price(9000)->create();
        $pack->items()->createMany([
            ['product_id' => $riz->id, 'quantity' => 2],
            ['product_id' => $huile->id, 'quantity' => 1],
        ]);

        $this->addToCart('pack', $pack->id, 2);

        $this->submitCheckout([
            'customer_name' => 'Jean',
            'customer_phone' => '699000000',
            'customer_address' => 'Yaounde',
        ])->assertHasNoErrors();

        $order = Order::firstOrFail();

        $this->assertSame('18000.00', $order->total);
        $this->assertSame(6, $riz->fresh()->stock);
        $this->assertSame(8, $huile->fresh()->stock);

        $item = $order->items->first();
        $this->assertSame('pack', $item->type);
        $this->assertCount(2, $item->components);
    }

    public function test_les_validations_de_commande_sont_limitees_par_minute(): void
    {
        $product = Product::factory()->stock(10)->create();
        $this->addToCart('product', $product->id);

        $component = Livewire::test(Checkout::class);

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $component->call('place')->assertHasErrors('customer_name');
        }

        $component
            ->set('customer_name', 'Jean')
            ->set('customer_phone', '699000000')
            ->set('customer_address', 'Yaounde')
            ->call('place')
            ->assertHasErrors('cart')
            ->assertNoRedirect();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_la_commande_apparait_immediatement_dans_le_dashboard(): void
    {
        $this->placeSimpleOrder();

        $order = Order::firstOrFail();
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee($order->number)
            ->assertSee('Jean Mbarga');
    }
}
