<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Categories\Index as CategoriesIndex;
use App\Livewire\Admin\Packs\Form as PackForm;
use App\Livewire\Admin\Packs\Index as PacksIndex;
use App\Livewire\Admin\Stock\Index as StockIndex;
use App\Models\Category;
use App\Models\Pack;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
    }

    public function test_les_pages_du_catalogue_s_affichent(): void
    {
        $category = Category::factory()->create(['name' => 'Epicerie salee']);
        $product = Product::factory()->for($category)->create(['name' => 'Sel de mer 1 kg']);
        $pack = Pack::factory()->create(['name' => 'Pack du matin']);
        $pack->items()->create(['product_id' => $product->id, 'quantity' => 2]);

        $this->actingAs($this->admin);

        $this->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSeeLivewire(CategoriesIndex::class)
            ->assertSee('Epicerie salee');

        $this->get(route('admin.packs.index'))
            ->assertOk()
            ->assertSeeLivewire(PacksIndex::class)
            ->assertSee('Pack du matin');

        $this->get(route('admin.packs.create'))->assertOk()->assertSeeLivewire(PackForm::class);

        $this->get(route('admin.packs.edit', $pack->id))
            ->assertOk()
            ->assertSee('Pack du matin')
            ->assertSee('Sel de mer 1 kg');

        $this->get(route('admin.stock.index'))
            ->assertOk()
            ->assertSeeLivewire(StockIndex::class)
            ->assertSee('Sel de mer 1 kg');
    }

    /* --------------------------------------------------------------------- */
    /* Categories */
    /* --------------------------------------------------------------------- */

    public function test_une_categorie_peut_etre_creee(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CategoriesIndex::class)
            ->call('create')
            ->assertSet('showForm', true)
            ->set('name', 'Epicerie fine')
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false)
            ->assertDispatched('toast', type: 'success', message: 'La categorie "Epicerie fine" a ete creee.');

        $category = Category::firstOrFail();

        $this->assertSame('Epicerie fine', $category->name);
        $this->assertSame('epicerie-fine', $category->slug);
        $this->assertTrue($category->is_active);
    }

    public function test_une_categorie_peut_etre_modifiee(): void
    {
        $category = Category::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(CategoriesIndex::class)
            ->call('edit', $category->id)
            ->assertSet('name', $category->name)
            ->set('name', 'Boissons fraiches')
            ->set('position', 3)
            ->set('is_active', false)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $category->refresh();

        $this->assertSame('Boissons fraiches', $category->name);
        $this->assertSame(3, $category->position);
        $this->assertFalse($category->is_active);
    }

    public function test_supprimer_une_categorie_conserve_ses_produits(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();

        Livewire::actingAs($this->admin)
            ->test(CategoriesIndex::class)
            ->call('destroy', $category->id)
            ->assertDispatched('toast');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'category_id' => null]);
    }

    public function test_une_categorie_masquee_disparait_de_la_navigation(): void
    {
        Category::factory()->create(['name' => 'Rayon visible']);
        Category::factory()->inactive()->create(['name' => 'Rayon masque']);

        $this->get(route('packs.index'))
            ->assertOk()
            ->assertSee('Rayon visible')
            ->assertDontSee('Rayon masque');
    }

    /* --------------------------------------------------------------------- */
    /* Packs */
    /* --------------------------------------------------------------------- */

    public function test_un_pack_peut_etre_cree_avec_ses_produits(): void
    {
        $a = Product::factory()->stock(10)->price(2000)->create();
        $b = Product::factory()->stock(10)->price(3000)->create();

        Livewire::actingAs($this->admin)
            ->test(PackForm::class)
            ->set('name', 'Pack cuisine')
            ->set('price', '4500')
            ->set('is_published', true)
            ->call('addProduct', $a->id)
            ->call('addProduct', $b->id)
            ->assertSet('items', [
                ['product_id' => $a->id, 'quantity' => 1],
                ['product_id' => $b->id, 'quantity' => 1],
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.packs.index'));

        $this->assertSame('Le pack "Pack cuisine" a ete cree.', session('success'));

        $pack = Pack::with('items')->firstOrFail();

        $this->assertSame('pack-cuisine', $pack->slug);
        $this->assertCount(2, $pack->items);
        $this->assertSame(4500.0, $pack->effectivePrice());
        $this->assertSame(500.0, $pack->savings());
        $this->assertSame(10, $pack->availableQuantity());
    }

    public function test_l_image_d_un_pack_peut_etre_televersee_puis_retiree(): void
    {
        Storage::fake('public');

        $product = Product::factory()->stock(10)->create();
        $pack = Pack::factory()->create();
        $pack->items()->create(['product_id' => $product->id, 'quantity' => 1]);

        Livewire::actingAs($this->admin)
            ->test(PackForm::class, ['pack' => $pack])
            ->set('image', UploadedFile::fake()->image('pack.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $path = $pack->fresh()->image_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        Livewire::actingAs($this->admin)
            ->test(PackForm::class, ['pack' => $pack->fresh()])
            ->set('remove_image', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($pack->fresh()->image_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_un_pack_exige_au_moins_un_produit(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PackForm::class)
            ->set('name', 'Pack vide')
            ->call('save')
            ->assertHasErrors(['items' => 'required']);

        $this->assertDatabaseCount('packs', 0);
    }

    public function test_la_composition_d_un_pack_est_remplacee_a_la_modification(): void
    {
        $a = Product::factory()->stock(10)->create();
        $b = Product::factory()->stock(10)->create();
        $c = Product::factory()->stock(10)->create();

        $pack = Pack::factory()->create();
        $pack->items()->createMany([
            ['product_id' => $a->id, 'quantity' => 1],
            ['product_id' => $b->id, 'quantity' => 1],
        ]);

        Livewire::actingAs($this->admin)
            ->test(PackForm::class, ['pack' => $pack])
            ->assertCount('items', 2)
            ->set('is_published', true)
            ->set('items', [['product_id' => $c->id, 'quantity' => 4]])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.packs.index'));

        $pack->refresh()->load('items');

        $this->assertCount(1, $pack->items);
        $this->assertSame($c->id, $pack->items->first()->product_id);
        $this->assertSame(4, $pack->items->first()->quantity);
    }

    public function test_un_pack_peut_etre_publie_et_depublie(): void
    {
        $pack = Pack::factory()->draft()->create();

        $component = Livewire::actingAs($this->admin)->test(PacksIndex::class);

        $component->call('togglePublish', $pack->id)->assertDispatched('toast');
        $this->assertTrue($pack->fresh()->is_published);

        $component->call('togglePublish', $pack->id);
        $this->assertFalse($pack->fresh()->is_published);
    }

    public function test_un_pack_depublie_n_apparait_pas_sur_le_site(): void
    {
        $pack = Pack::factory()->draft()->create(['name' => 'Pack brouillon invisible']);

        $this->get(route('packs.index'))
            ->assertOk()
            ->assertDontSee('Pack brouillon invisible');

        $this->get(route('packs.show', $pack->slug))->assertNotFound();
    }

    /* --------------------------------------------------------------------- */
    /* Stocks */
    /* --------------------------------------------------------------------- */

    public function test_le_stock_peut_etre_ajuste_ligne_par_ligne(): void
    {
        $product = Product::factory()->stock(10)->create();

        Livewire::actingAs($this->admin)
            ->test(StockIndex::class)
            ->call('updateStock', $product->id, ['stock' => 42])
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertSame(42, $product->fresh()->stock);
    }

    public function test_le_stock_peut_etre_ajuste_de_maniere_relative(): void
    {
        $product = Product::factory()->stock(10)->create();

        Livewire::actingAs($this->admin)
            ->test(StockIndex::class)
            ->call('adjustStock', $product->id, -4);

        $this->assertSame(6, $product->fresh()->stock);
    }

    public function test_le_stock_ne_peut_pas_devenir_negatif(): void
    {
        $product = Product::factory()->stock(2)->create();

        Livewire::actingAs($this->admin)
            ->test(StockIndex::class)
            ->call('updateStock', $product->id, ['delta' => -10]);

        $this->assertSame(0, $product->fresh()->stock);
    }

    public function test_le_stock_peut_etre_enregistre_en_lot(): void
    {
        $a = Product::factory()->stock(10)->create();
        $b = Product::factory()->stock(10)->create();

        Livewire::actingAs($this->admin)
            ->test(StockIndex::class)
            ->call('bulkUpdate', [
                ['id' => $a->id, 'stock' => 0, 'is_available' => true],
                ['id' => $b->id, 'stock' => 25, 'is_available' => false],
            ])
            ->assertHasNoErrors()
            ->assertDispatched('toast', type: 'success', message: '2 produit(s) mis a jour.');

        $this->assertSame(0, $a->fresh()->stock);
        $this->assertSame('out_of_stock', $a->fresh()->stockState()->value);

        $this->assertSame(25, $b->fresh()->stock);
        $this->assertFalse($b->fresh()->is_available);
        $this->assertSame('unavailable', $b->fresh()->stockState()->value);
    }

    public function test_un_stock_negatif_est_refuse_en_lot(): void
    {
        $product = Product::factory()->stock(10)->create();

        Livewire::actingAs($this->admin)
            ->test(StockIndex::class)
            ->call('bulkUpdate', [['id' => $product->id, 'stock' => -3, 'is_available' => true]])
            ->assertHasErrors('updates.0.stock');

        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_la_page_stocks_filtre_les_ruptures(): void
    {
        Product::factory()->stock(50)->create();
        Product::factory()->outOfStock()->create(['name' => 'Rupture']);

        Livewire::actingAs($this->admin)
            ->withQueryParams(['state' => 'out_of_stock'])
            ->test(StockIndex::class)
            ->assertViewHas('products', fn (LengthAwarePaginator $products) => $products->total() === 1
                && $products->items()[0]['name'] === 'Rupture');
    }
}
