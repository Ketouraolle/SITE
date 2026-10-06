<?php

namespace App\Livewire\Shop;

use App\Models\Category;
use App\Models\Occasion;
use App\Models\Pack;
use App\Models\Product;
use App\Support\Presenters;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Page d'accueil : bandeau, carrousel, rayons, periodes en cours, vedettes,
 * packs et derniers arrivages.
 */
#[Title('Accueil')]
class Home extends Component
{
    public function render(): View
    {
        $featured = Product::visible()
            ->with(['primaryImage', 'category'])
            ->where('is_featured', true)
            ->latest('published_at')
            ->take(8)
            ->get()
            ->map(fn (Product $product) => Presenters::productCard($product));

        $latest = Product::visible()
            ->with(['primaryImage', 'category'])
            ->latest('published_at')
            ->take(8)
            ->get()
            ->map(fn (Product $product) => Presenters::productCard($product));

        $packs = Pack::visible()
            ->with(['items.product.primaryImage'])
            ->ordered()
            ->take(3)
            ->get()
            ->map(fn (Pack $pack) => Presenters::pack($pack));

        // Produits avec photo : alimentent le carrousel, le bandeau defilant
        // et la vignette de chaque rayon.
        $showcase = Product::visible()
            ->whereHas('images')
            ->with('primaryImage')
            ->latest('published_at')
            ->take(60)
            ->get();

        $gallery = $showcase->take(16)->map(fn (Product $product) => [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'image' => $product->primaryImage->url,
        ])->values();

        $categories = Category::active()
            ->ordered()
            ->withCount(['products' => fn ($q) => $q->visible()])
            ->get()
            // Un rayon vide n'a rien a montrer en vitrine.
            ->filter(fn (Category $category) => $category->products_count > 0)
            ->values()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'products_count' => $category->products_count,
                'image' => $showcase->firstWhere('category_id', $category->id)?->primaryImage->url,
            ]);

        // Periodes en cours (Noel, Saint-Valentin...) : une vitrine chacune.
        $occasions = Occasion::running()
            ->ordered()
            ->get()
            ->map(fn (Occasion $occasion) => [
                ...Presenters::occasionTag($occasion),
                'description' => $occasion->description,
                'best_offer' => $occasion->offers()->where('is_active', true)->max('discount_percent'),
                'products' => OccasionShow::products($occasion, 8)
                    ->map(fn (Product $product) => Presenters::productCard($product)),
                'packs' => OccasionShow::packs($occasion, 3)
                    ->map(fn (Pack $pack) => Presenters::pack($pack)),
            ])
            ->filter(fn (array $occasion) => $occasion['products']->isNotEmpty() || $occasion['packs']->isNotEmpty())
            ->values();

        return view('livewire.shop.home', [
            'occasions' => $occasions,
            'featured' => $featured,
            'latest' => $latest,
            'packs' => $packs,
            'categories' => $categories,
            'gallery' => $gallery,
            'stats' => [
                'products' => Product::visible()->count(),
                'categories' => $categories->count(),
                'packs' => Pack::visible()->count(),
            ],
            'customPack' => config('shop.custom_pack'),
        ]);
    }
}
