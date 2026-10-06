<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Products\Form;
use App\Livewire\Admin\Products\Index;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
    }

    public function test_les_pages_produits_s_affichent(): void
    {
        $product = Product::factory()->create(['name' => 'Huile de palme 1 L']);

        $this->actingAs($this->admin);

        $this->get(route('admin.products.index'))
            ->assertOk()
            ->assertSeeLivewire(Index::class)
            ->assertSee('Huile de palme 1 L');

        $this->get(route('admin.products.create'))->assertOk()->assertSeeLivewire(Form::class);

        $this->get(route('admin.products.edit', $product->id))
            ->assertOk()
            ->assertSeeLivewire(Form::class)
            ->assertSee('Huile de palme 1 L');
    }

    public function test_un_produit_peut_etre_cree(): void
    {
        $category = Category::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->set('name', 'Riz parfume 5 kg')
            ->set('category_id', (string) $category->id)
            ->set('short_description', 'Sac de 5 kg')
            ->set('price', '6500')
            ->set('stock', '40')
            ->set('is_available', true)
            ->set('is_published', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.products.index'));

        $this->assertSame('Le produit "Riz parfume 5 kg" a ete cree.', session('success'));

        $product = Product::firstOrFail();

        $this->assertSame('Riz parfume 5 kg', $product->name);
        $this->assertSame('riz-parfume-5-kg', $product->slug);
        $this->assertSame($category->id, $product->category_id);
        $this->assertSame(40, $product->stock);
        $this->assertTrue($product->is_published);
        $this->assertNotNull($product->published_at);
    }

    public function test_les_champs_obligatoires_sont_valides(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->set('stock', '')
            ->call('save')
            ->assertHasErrors(['name', 'price', 'stock']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_le_prix_barre_doit_depasser_le_prix_de_vente(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->set('name', 'Test')
            ->set('price', '1000')
            ->set('compare_at_price', '900')
            ->set('stock', '1')
            ->call('save')
            ->assertHasErrors(['compare_at_price' => 'gt']);
    }

    public function test_un_produit_peut_etre_modifie_sans_changer_son_slug(): void
    {
        $product = Product::factory()->create(['name' => 'Ancien nom', 'slug' => 'ancien-nom']);

        Livewire::actingAs($this->admin)
            ->test(Form::class, ['product' => $product])
            ->assertSet('name', 'Ancien nom')
            ->set('name', 'Nouveau nom')
            ->set('slug', '')
            ->set('price', '2000')
            ->set('stock', '5')
            ->set('is_published', true)
            ->set('is_available', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();

        $this->assertSame('Nouveau nom', $product->name);
        $this->assertSame('ancien-nom', $product->slug, 'Renommer un produit ne doit pas casser son URL.');
    }

    public function test_un_produit_peut_etre_publie_et_depublie(): void
    {
        $product = Product::factory()->draft()->create();

        $component = Livewire::actingAs($this->admin)->test(Index::class);

        $component->call('togglePublish', $product->id)->assertDispatched('toast');
        $this->assertTrue($product->fresh()->is_published);

        $component->call('togglePublish', $product->id);
        $this->assertFalse($product->fresh()->is_published);
    }

    public function test_la_disponibilite_peut_etre_basculee_sans_toucher_au_stock(): void
    {
        $product = Product::factory()->stock(12)->create();

        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->call('toggleAvailability', $product->id)
            ->assertDispatched('toast');

        $product->refresh();

        $this->assertFalse($product->is_available);
        $this->assertSame(12, $product->stock);
        $this->assertSame('unavailable', $product->stockState()->value);
    }

    public function test_un_produit_est_supprime_logiquement(): void
    {
        $product = Product::factory()->create(['name' => 'Produit a retirer du catalogue']);

        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->call('destroy', $product->id)
            ->assertDispatched('toast');

        $this->assertSoftDeleted($product);

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertDontSee('Produit a retirer du catalogue');
    }

    public function test_un_produit_peut_etre_supprime_depuis_sa_fiche(): void
    {
        $product = Product::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(Form::class, ['product' => $product])
            ->call('destroy')
            ->assertRedirect(route('admin.products.index'));

        $this->assertSoftDeleted($product);
    }

    public function test_des_images_peuvent_etre_televersees(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->set('name', 'Produit avec images')
            ->set('price', '1000')
            ->set('stock', '1')
            ->set('is_published', true)
            ->set('is_available', true)
            ->set('images', [
                UploadedFile::fake()->image('une.jpg'),
                UploadedFile::fake()->image('deux.jpg'),
            ])
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::firstOrFail();

        $this->assertCount(2, $product->images);
        $this->assertTrue($product->images->first()->is_primary);
        $this->assertFalse($product->images->last()->is_primary);

        foreach ($product->images as $image) {
            Storage::disk('public')->assertExists($image->path);
        }
    }

    public function test_une_image_peut_etre_supprimee_et_le_fichier_est_efface(): void
    {
        Storage::fake('public');

        $product = Product::factory()->create();
        $image = $product->images()->create([
            'path' => UploadedFile::fake()->image('a.jpg')->store('products', 'public'),
            'is_primary' => true,
        ]);

        Storage::disk('public')->assertExists($image->path);

        Livewire::actingAs($this->admin)
            ->test(Form::class, ['product' => $product])
            ->call('deleteImage', $image->id)
            ->assertDispatched('toast');

        $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($image->path);
    }

    public function test_l_image_principale_peut_etre_changee(): void
    {
        Storage::fake('public');

        $product = Product::factory()->create();
        $first = $product->images()->create(['path' => 'products/a.jpg', 'is_primary' => true, 'position' => 1]);
        $second = $product->images()->create(['path' => 'products/b.jpg', 'is_primary' => false, 'position' => 2]);

        Livewire::actingAs($this->admin)
            ->test(Form::class, ['product' => $product])
            ->call('setPrimaryImage', $second->id);

        $this->assertFalse($first->fresh()->is_primary);
        $this->assertTrue($second->fresh()->is_primary);
    }

    public function test_les_filtres_de_la_liste_fonctionnent(): void
    {
        Product::factory()->create(['name' => 'Publie']);
        Product::factory()->draft()->create(['name' => 'Brouillon']);

        Livewire::actingAs($this->admin)
            ->withQueryParams(['state' => 'draft'])
            ->test(Index::class)
            ->assertSet('state', 'draft')
            ->assertViewHas('products', fn (LengthAwarePaginator $products) => $products->total() === 1
                && $products->items()[0]['name'] === 'Brouillon');
    }
}
