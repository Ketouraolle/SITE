<?php

namespace Tests\Feature\Shop;

use App\Livewire\AddToCart;
use App\Livewire\Shop\Cart;
use App\Models\Pack;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private function addProduct(Product $product, int $quantity = 1): Testable
    {
        return Livewire::test(AddToCart::class, [
            'kind' => 'product',
            'itemId' => $product->id,
            'orderable' => $product->isOrderable(),
            'maxQuantity' => $product->stock,
        ])->set('quantity', $quantity)->call('add');
    }

    private function addPack(Pack $pack, int $quantity = 1): Testable
    {
        return Livewire::test(AddToCart::class, [
            'kind' => 'pack',
            'itemId' => $pack->id,
            'orderable' => true,
            'maxQuantity' => 10,
        ])->set('quantity', $quantity)->call('add');
    }

    /**
     * @return array{lines: array<int, array<string, mixed>>, total: float, total_label: string, count: int, has_blocking_issue: bool}
     */
    private function cart(): array
    {
        return Livewire::test(Cart::class)->viewData('cart');
    }

    public function test_un_produit_peut_etre_ajoute_au_panier_sans_compte(): void
    {
        $product = Product::factory()->stock(10)->price(2500)->create();

        $this->addProduct($product, 3)
            ->assertHasNoErrors()
            ->assertDispatched('cart-updated')
            ->assertDispatched('toast', type: 'success');

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSeeLivewire(Cart::class)
            ->assertSee($product->name);

        $cart = $this->cart();
        $this->assertCount(1, $cart['lines']);
        $this->assertSame(3, $cart['lines'][0]['quantity']);
        $this->assertSame(7500.0, (float) $cart['total']);
        $this->assertSame(3, $cart['count']);

        $this->assertGuest();
    }

    public function test_ajouter_deux_fois_le_meme_produit_cumule_les_quantites(): void
    {
        $product = Product::factory()->stock(10)->create();

        $this->addProduct($product, 2);
        $this->addProduct($product, 1);

        $cart = $this->cart();
        $this->assertCount(1, $cart['lines']);
        $this->assertSame(3, $cart['lines'][0]['quantity']);
    }

    public function test_un_produit_en_rupture_ne_peut_pas_etre_ajoute(): void
    {
        $product = Product::factory()->outOfStock()->create();

        $this->addProduct($product)
            ->assertHasErrors('quantity')
            ->assertNotDispatched('cart-updated');

        $this->assertSame(0, app(CartService::class)->count());
    }

    public function test_la_quantite_demandee_ne_peut_pas_depasser_le_stock(): void
    {
        $product = Product::factory()->stock(2)->create();

        $this->addProduct($product, 5)->assertHasErrors('quantity');

        $this->assertSame(0, app(CartService::class)->count());
    }

    public function test_un_produit_indisponible_ne_peut_pas_etre_ajoute(): void
    {
        $product = Product::factory()->unavailable()->stock(10)->create();

        $this->addProduct($product)->assertHasErrors('quantity');
    }

    public function test_la_quantite_d_une_ligne_peut_etre_modifiee(): void
    {
        $product = Product::factory()->stock(10)->create();
        $this->addProduct($product);

        Livewire::test(Cart::class)
            ->call('updateQuantity', 'p:'.$product->id, 4)
            ->assertDispatched('cart-updated');

        $this->assertSame(4, $this->cart()['lines'][0]['quantity']);
    }

    public function test_une_quantite_a_zero_retire_la_ligne(): void
    {
        $product = Product::factory()->stock(10)->create();
        $this->addProduct($product);

        Livewire::test(Cart::class)->call('updateQuantity', 'p:'.$product->id, 0);

        $this->assertSame(0, app(CartService::class)->count());
    }

    public function test_une_ligne_peut_etre_supprimee(): void
    {
        $product = Product::factory()->stock(10)->create();
        $this->addProduct($product);

        Livewire::test(Cart::class)
            ->call('remove', 'p:'.$product->id)
            ->assertDispatched('toast', type: 'success', message: 'Article retire du panier.')
            ->assertDispatched('cart-updated');

        $this->assertSame(0, app(CartService::class)->count());
    }

    public function test_le_panier_peut_etre_vide(): void
    {
        $this->addProduct(Product::factory()->stock(10)->create());
        $this->addProduct(Product::factory()->stock(10)->create());

        Livewire::test(Cart::class)
            ->call('clear')
            ->assertDispatched('toast', type: 'success', message: 'Votre panier a ete vide.')
            ->assertDispatched('cart-updated')
            ->assertSee('Votre panier est vide');

        $this->assertSame(0, app(CartService::class)->count());
    }

    public function test_un_pack_peut_etre_ajoute_au_panier(): void
    {
        $riz = Product::factory()->stock(10)->price(6000)->create();
        $huile = Product::factory()->stock(10)->price(4000)->create();

        $pack = Pack::factory()->price(9000)->create();
        $pack->items()->createMany([
            ['product_id' => $riz->id, 'quantity' => 1],
            ['product_id' => $huile->id, 'quantity' => 1],
        ]);

        $this->addPack($pack, 2)
            ->assertHasNoErrors()
            ->assertDispatched('toast', type: 'success');

        $cart = $this->cart();
        $this->assertCount(1, $cart['lines']);
        $this->assertSame('pack', $cart['lines'][0]['type']);
        $this->assertSame(9000.0, (float) $cart['lines'][0]['unit_price']);
        $this->assertSame(18000.0, (float) $cart['total']);
        $this->assertCount(2, $cart['lines'][0]['components']);
    }

    public function test_un_pack_sans_prix_est_facture_a_la_somme_de_ses_produits(): void
    {
        $a = Product::factory()->stock(10)->price(1500)->create();
        $b = Product::factory()->stock(10)->price(2500)->create();

        $pack = Pack::factory()->price(null)->create();
        $pack->items()->createMany([
            ['product_id' => $a->id, 'quantity' => 2],
            ['product_id' => $b->id, 'quantity' => 1],
        ]);

        $this->addPack($pack);

        $this->assertSame(5500.0, (float) $this->cart()['lines'][0]['unit_price']);
    }

    public function test_un_pack_dont_un_produit_est_en_rupture_n_est_pas_commandable(): void
    {
        $ok = Product::factory()->stock(10)->create();
        $rupture = Product::factory()->outOfStock()->create();

        $pack = Pack::factory()->create();
        $pack->items()->createMany([
            ['product_id' => $ok->id, 'quantity' => 1],
            ['product_id' => $rupture->id, 'quantity' => 1],
        ]);

        $this->addPack($pack)->assertHasErrors('quantity');

        $this->assertSame(0, app(CartService::class)->count());
    }

    public function test_le_panier_reflete_un_prix_modifie_apres_ajout(): void
    {
        $product = Product::factory()->stock(10)->price(1000)->create();
        $this->addProduct($product, 2);

        $product->update(['price' => 1500]);

        $this->assertSame(3000.0, (float) $this->cart()['total']);
    }

    public function test_une_ligne_dont_le_produit_est_depublie_disparait_du_panier(): void
    {
        $product = Product::factory()->stock(10)->create();
        $this->addProduct($product);

        $product->update(['is_published' => false]);

        $this->assertCount(0, $this->cart()['lines']);
    }

    public function test_une_ligne_dont_le_stock_est_devenu_insuffisant_est_signalee(): void
    {
        $product = Product::factory()->stock(5)->create();
        $this->addProduct($product, 5);

        $product->update(['stock' => 2]);

        $cart = $this->cart();
        $this->assertTrue($cart['has_blocking_issue']);
        $this->assertNotNull($cart['lines'][0]['issue']);

        Livewire::test(Cart::class)->assertSee('Corrigez les articles signales avant de valider votre commande.');
    }
}
