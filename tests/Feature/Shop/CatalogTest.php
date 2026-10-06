<?php

namespace Tests\Feature\Shop;

use App\Livewire\Shop\Catalog;
use App\Livewire\Shop\Home;
use App\Livewire\Shop\OccasionShow;
use App\Livewire\Shop\PackIndex;
use App\Livewire\Shop\PackShow;
use App\Livewire\Shop\ProductShow;
use App\Models\Category;
use App\Models\Occasion;
use App\Models\Pack;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_page_d_accueil_s_affiche(): void
    {
        $products = Product::factory()->count(3)->create(['is_featured' => true]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSeeLivewire(Home::class)
            ->assertSee($products->first()->name);

        Livewire::test(Home::class)
            ->assertViewHas('featured', fn (Collection $featured) => $featured->count() === 3);
    }

    public function test_l_accueil_expose_les_photos_des_produits_visibles_pour_le_carrousel(): void
    {
        $category = Category::factory()->create();
        $withPhoto = Product::factory()->for($category)->create();
        $withPhoto->images()->create(['path' => 'products/photo.jpeg', 'is_primary' => true]);
        Product::factory()->for($category)->create();
        Product::factory()->draft()->create()->images()->create(['path' => 'products/brouillon.jpeg']);

        $this->get(route('home'))->assertOk();

        Livewire::test(Home::class)
            ->assertViewHas('gallery', fn (Collection $gallery) => $gallery->count() === 1
                && $gallery[0]['id'] === $withPhoto->id
                && $gallery[0]['image'] === $withPhoto->primaryImage->url)
            ->assertViewHas('categories', fn (Collection $categories) => $categories[0]['image'] === $withPhoto->primaryImage->url);
    }

    public function test_l_accueil_masque_les_rayons_sans_produit_visible(): void
    {
        $bijoux = Category::factory()->create(['name' => 'Bijoux']);
        Product::factory()->for($bijoux)->create();
        Product::factory()->draft()->for(Category::factory()->create(['name' => 'Habits']))->create();
        Category::factory()->create(['name' => 'Accessoires']);

        $this->get(route('home'))->assertOk();

        Livewire::test(Home::class)
            ->assertViewHas('categories', fn (Collection $categories) => $categories->count() === 1
                && $categories[0]['id'] === $bijoux->id);
    }

    public function test_le_catalogue_n_expose_que_les_produits_publies(): void
    {
        $published = Product::factory()->create(['name' => 'Produit visible']);
        Product::factory()->draft()->create(['name' => 'Produit brouillon']);

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSeeLivewire(Catalog::class)
            ->assertSee('Produit visible')
            ->assertDontSee('Produit brouillon');

        Livewire::test(Catalog::class)
            ->assertViewHas('products', fn (LengthAwarePaginator $products) => $products->total() === 1
                && $products->items()[0]['id'] === $published->id);
    }

    public function test_les_produits_en_rupture_et_indisponibles_restent_visibles_mais_signales(): void
    {
        Product::factory()->outOfStock()->create(['name' => 'Rupture']);
        Product::factory()->unavailable()->create(['name' => 'Indisponible']);

        $this->get(route('catalog.index', ['sort' => 'name']))->assertOk();

        Livewire::withQueryParams(['sort' => 'name'])
            ->test(Catalog::class)
            ->assertViewHas('products', function (LengthAwarePaginator $products): bool {
                $items = $products->items();

                return count($items) === 2
                    && $items[0]['stock_state'] === 'unavailable'
                    && $items[0]['orderable'] === false
                    && $items[1]['stock_state'] === 'out_of_stock'
                    && $items[1]['orderable'] === false;
            });
    }

    public function test_la_recherche_filtre_les_produits(): void
    {
        Product::factory()->create(['name' => 'Collier plaque or']);
        Product::factory()->create(['name' => 'Pyjama satin']);

        $this->get(route('catalog.index', ['q' => 'collier']))
            ->assertOk()
            ->assertSee('Collier plaque or')
            ->assertDontSee('Pyjama satin');

        Livewire::test(Catalog::class)
            ->set('q', 'collier')
            ->assertViewHas('products', fn (LengthAwarePaginator $products) => $products->total() === 1
                && $products->items()[0]['name'] === 'Collier plaque or');
    }

    public function test_le_filtre_par_categorie_fonctionne(): void
    {
        $category = Category::factory()->create(['name' => 'Bijoux', 'slug' => 'bijoux']);
        Product::factory()->for($category)->create();
        Product::factory()->create();

        $this->get(route('catalog.index', ['category' => 'bijoux']))->assertOk();

        Livewire::withQueryParams(['category' => 'bijoux'])
            ->test(Catalog::class)
            ->assertViewHas('products', fn (LengthAwarePaginator $products) => $products->total() === 1);
    }

    public function test_le_filtre_de_disponibilite_fonctionne(): void
    {
        Product::factory()->stock(10)->create();
        Product::factory()->outOfStock()->create();

        $this->get(route('catalog.index', ['availability' => 'out_of_stock']))->assertOk();

        Livewire::test(Catalog::class)
            ->set('availability', 'out_of_stock')
            ->assertViewHas('products', fn (LengthAwarePaginator $products) => $products->total() === 1
                && $products->items()[0]['stock_state'] === 'out_of_stock');
    }

    public function test_la_fiche_d_un_produit_non_publie_renvoie_404(): void
    {
        $product = Product::factory()->draft()->create();

        $this->get(route('catalog.show', $product->slug))->assertNotFound();
    }

    public function test_la_fiche_produit_s_affiche(): void
    {
        $product = Product::factory()->create();

        $this->get(route('catalog.show', $product->slug))
            ->assertOk()
            ->assertSeeLivewire(ProductShow::class)
            ->assertSee($product->name);

        Livewire::test(ProductShow::class, ['product' => $product])
            ->assertViewHas('productData', fn (array $data) => $data['name'] === $product->name);
    }

    public function test_les_pages_des_packs_s_affichent(): void
    {
        $product = Product::factory()->stock(10)->create(['name' => 'Collier plaque or']);
        $pack = Pack::factory()->create(['name' => 'Pack fete']);
        $pack->items()->create(['product_id' => $product->id, 'quantity' => 2]);
        Pack::factory()->draft()->create(['name' => 'Pack brouillon']);

        $this->get(route('packs.index'))
            ->assertOk()
            ->assertSeeLivewire(PackIndex::class)
            ->assertSee('Pack fete')
            ->assertDontSee('Pack brouillon');

        $this->get(route('packs.show', $pack->slug))
            ->assertOk()
            ->assertSeeLivewire(PackShow::class)
            ->assertSee('Collier plaque or');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Pack fete');
    }

    public function test_la_fiche_d_un_pack_non_publie_renvoie_404(): void
    {
        $pack = Pack::factory()->draft()->create();

        $this->get(route('packs.show', $pack->slug))->assertNotFound();
    }

    public function test_la_vitrine_d_une_periode_en_cours_s_affiche(): void
    {
        $occasion = Occasion::factory()->create(['name' => 'Noel']);
        Product::factory()->create(['name' => 'Coffret Noel', 'occasion_id' => $occasion->id]);

        $this->get(route('occasions.show', $occasion->slug))
            ->assertOk()
            ->assertSeeLivewire(OccasionShow::class)
            ->assertSee('Coffret Noel');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Coffret Noel');
    }

    public function test_la_vitrine_d_une_periode_terminee_renvoie_404(): void
    {
        $occasion = Occasion::factory()->ended()->create();

        $this->get(route('occasions.show', $occasion->slug))->assertNotFound();
    }
}
