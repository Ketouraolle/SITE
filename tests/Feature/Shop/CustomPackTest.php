<?php

namespace Tests\Feature\Shop;

use App\Livewire\Shop\Cart;
use App\Livewire\Shop\Checkout;
use App\Livewire\Shop\CustomPack;
use App\Models\Order;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Tests\TestCase;

class CustomPackTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_page_de_composition_ne_propose_que_des_produits_commandables(): void
    {
        Product::factory()->stock(10)->create(['name' => 'Disponible']);
        Product::factory()->outOfStock()->create(['name' => 'Rupture']);
        Product::factory()->unavailable()->create(['name' => 'Indisponible']);
        Product::factory()->draft()->create(['name' => 'Brouillon']);

        $this->get(route('custom-pack.create'))
            ->assertOk()
            ->assertSeeLivewire(CustomPack::class);

        Livewire::test(CustomPack::class)
            ->assertViewHas('products', fn (Collection $products): bool => $products->count() === 1
                && $products->first()['name'] === 'Disponible');
    }

    public function test_un_client_peut_composer_son_pack(): void
    {
        $a = Product::factory()->stock(10)->price(1000)->create();
        $b = Product::factory()->stock(10)->price(2000)->create();

        Livewire::test(CustomPack::class)
            ->call('toggle', $a->id)
            ->call('toggle', $b->id)
            ->set('items.0.quantity', 2)
            ->set('quantity', 2)
            ->set('label', 'Pack de la semaine')
            ->assertViewHas('unitTotalLabel', fn (string $label): bool => str_contains($label, '4'))
            ->call('addToCart')
            ->assertHasNoErrors()
            ->assertRedirect(route('cart.index'));

        $this->assertSame('Votre pack a ete ajoute au panier.', session('success'));

        $cart = Livewire::test(Cart::class)->viewData('cart');
        $this->assertCount(1, $cart['lines']);
        $this->assertSame('custom_pack', $cart['lines'][0]['type']);
        $this->assertSame('Pack de la semaine', $cart['lines'][0]['name']);
        $this->assertSame(4000.0, (float) $cart['lines'][0]['unit_price']);
        $this->assertSame(8000.0, (float) $cart['total']);
        $this->assertCount(2, $cart['lines'][0]['components']);
    }

    public function test_un_pack_personnalise_exige_un_minimum_de_produits(): void
    {
        $product = Product::factory()->stock(10)->create();

        Livewire::test(CustomPack::class)
            ->set('items', [['product_id' => $product->id, 'quantity' => 1]])
            ->call('addToCart')
            ->assertHasErrors(['items' => 'min'])
            ->assertNoRedirect();

        $this->assertSame(0, app(CartService::class)->count());
    }

    public function test_un_produit_indisponible_est_refuse_dans_un_pack_personnalise(): void
    {
        $ok = Product::factory()->stock(10)->create();
        $rupture = Product::factory()->outOfStock()->create();

        Livewire::test(CustomPack::class)
            ->set('items', [
                ['product_id' => $ok->id, 'quantity' => 1],
                ['product_id' => $rupture->id, 'quantity' => 1],
            ])
            ->call('addToCart')
            ->assertHasErrors('items');

        $this->assertSame(0, app(CartService::class)->count());
    }

    public function test_le_stock_est_verifie_en_tenant_compte_du_nombre_de_packs(): void
    {
        $a = Product::factory()->stock(10)->create();
        $b = Product::factory()->stock(3)->create();

        // 2 unites de B par pack x 3 packs = 6 > 3 en stock.
        Livewire::test(CustomPack::class)
            ->set('quantity', 3)
            ->set('items', [
                ['product_id' => $a->id, 'quantity' => 1],
                ['product_id' => $b->id, 'quantity' => 2],
            ])
            ->call('addToCart')
            ->assertHasErrors('items');

        $this->assertSame(0, app(CartService::class)->count());
    }

    public function test_un_meme_produit_ne_peut_pas_apparaitre_deux_fois(): void
    {
        $product = Product::factory()->stock(10)->create();
        $other = Product::factory()->stock(10)->create();

        Livewire::test(CustomPack::class)
            ->set('items', [
                ['product_id' => $product->id, 'quantity' => 1],
                ['product_id' => $product->id, 'quantity' => 1],
                ['product_id' => $other->id, 'quantity' => 1],
            ])
            ->call('addToCart')
            ->assertHasErrors(['items.0.product_id' => 'distinct']);

        $this->assertSame(0, app(CartService::class)->count());
    }

    public function test_un_pack_personnalise_commande_decremente_chaque_produit(): void
    {
        $a = Product::factory()->stock(10)->price(1000)->create();
        $b = Product::factory()->stock(10)->price(2000)->create();

        Livewire::test(CustomPack::class)
            ->set('quantity', 2)
            ->set('items', [
                ['product_id' => $a->id, 'quantity' => 2],
                ['product_id' => $b->id, 'quantity' => 1],
            ])
            ->call('addToCart')
            ->assertHasNoErrors();

        Livewire::test(Checkout::class)
            ->set('customer_name', 'Jean')
            ->set('customer_phone', '699000000')
            ->set('customer_address', 'Yaounde')
            ->call('place')
            ->assertHasNoErrors();

        $order = Order::firstOrFail();

        $this->assertSame('8000.00', $order->total);
        $this->assertSame('custom_pack', $order->items->first()->type);
        $this->assertSame(6, $a->fresh()->stock);
        $this->assertSame(8, $b->fresh()->stock);
    }

    public function test_les_ajouts_de_pack_personnalise_sont_limites_par_minute(): void
    {
        $component = Livewire::test(CustomPack::class);

        for ($attempt = 0; $attempt < 30; $attempt++) {
            $component->call('addToCart')->assertHasErrors(['items' => 'required']);
        }

        $component->call('addToCart')
            ->assertHasErrors('items')
            ->assertSee('Trop de tentatives');
    }
}
