<?php

namespace App\Livewire\Admin\Products;

use App\Models\Category;
use App\Models\Product;
use App\Support\Presenters;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Liste des fiches produit : filtres, publication, disponibilite et
 * suppression (logique) en un clic.
 */
#[Layout('layouts.admin')]
#[Title('Produits')]
class Index extends Component
{
    use WithPagination;

    /**
     * @var array<string, string>
     */
    public const STATES = [
        'all' => 'Tous',
        'published' => 'Publies',
        'draft' => 'Brouillons',
        'available' => 'En stock',
        'out_of_stock' => 'En rupture',
        'unavailable' => 'Indisponibles',
    ];

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $category = '';

    #[Url(except: 'all')]
    public string $state = 'all';

    public function updated(string $property): void
    {
        if (in_array($property, ['q', 'category', 'state'], true)) {
            $this->resetPage();
        }
    }

    /**
     * Publie / depublie en un clic depuis la liste.
     */
    public function togglePublish(int $productId): void
    {
        $product = Product::findOrFail($productId);

        $product->update([
            'is_published' => ! $product->is_published,
            'published_at' => $product->is_published ? $product->published_at : now(),
        ]);

        $this->dispatch('toast', type: 'success', message: $product->is_published
            ? "\"{$product->name}\" est maintenant publie."
            : "\"{$product->name}\" a ete depublie.");
    }

    /**
     * Bascule disponible / indisponible sans toucher au stock.
     */
    public function toggleAvailability(int $productId): void
    {
        $product = Product::findOrFail($productId);

        $product->update(['is_available' => ! $product->is_available]);

        $this->dispatch('toast', type: 'success', message: $product->is_available
            ? "\"{$product->name}\" est de nouveau disponible."
            : "\"{$product->name}\" est marque indisponible.");
    }

    /**
     * Suppression logique : les commandes passees conservent leur reference
     * produit et restent lisibles dans le dashboard.
     */
    public function destroy(int $productId): void
    {
        $product = Product::findOrFail($productId);
        $product->delete();

        $this->dispatch('toast', type: 'success', message: "Le produit \"{$product->name}\" a ete supprime.");
    }

    public function render(): View
    {
        $state = array_key_exists($this->state, self::STATES) ? $this->state : 'all';
        $categoryId = ctype_digit($this->category) ? (int) $this->category : null;

        $products = Product::query()
            ->with(['category', 'occasion', 'primaryImage'])
            ->search(mb_substr($this->q, 0, 120))
            ->when($categoryId, fn (Builder $q, int $id) => $q->where('category_id', $id))
            ->when($state === 'published', fn (Builder $q) => $q->where('is_published', true))
            ->when($state === 'draft', fn (Builder $q) => $q->where('is_published', false))
            ->when($state === 'available', fn (Builder $q) => $q->where('is_available', true)->where('stock', '>', 0))
            ->when($state === 'out_of_stock', fn (Builder $q) => $q->where('is_available', true)->where('stock', '<=', 0))
            ->when($state === 'unavailable', fn (Builder $q) => $q->where('is_available', false))
            ->latest('id')
            ->paginate(15)
            ->through(fn (Product $product) => Presenters::productAdmin($product));

        return view('livewire.admin.products.index', [
            'products' => $products,
            'categories' => Category::ordered()->get(['id', 'name']),
            'states' => self::STATES,
        ]);
    }
}
