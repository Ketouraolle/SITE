<?php

namespace App\Livewire\Shop;

use App\Models\Category;
use App\Models\Product;
use App\Support\Presenters;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Catalogue public : recherche, filtres et tri synchronises avec l'URL
 * (la recherche de l'en-tete envoie ?q=...).
 */
#[Title('Catalogue')]
class Catalog extends Component
{
    use WithPagination;

    /**
     * @var list<string>
     */
    public const AVAILABILITIES = ['all', 'in_stock', 'out_of_stock', 'unavailable'];

    /**
     * @var list<string>
     */
    public const SORTS = ['recent', 'price_asc', 'price_desc', 'name'];

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $category = '';

    #[Url(except: 'all')]
    public string $availability = 'all';

    #[Url(as: 'min_price', except: '')]
    public string $minPrice = '';

    #[Url(as: 'max_price', except: '')]
    public string $maxPrice = '';

    #[Url(except: 'recent')]
    public string $sort = 'recent';

    /**
     * Toute modification d'un filtre ramene a la premiere page.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['q', 'category', 'availability', 'minPrice', 'maxPrice', 'sort'], true)) {
            $this->resetPage();
        }
    }

    public function selectCategory(string $slug = ''): void
    {
        $this->category = $slug;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('q', 'category', 'availability', 'minPrice', 'maxPrice', 'sort');
        $this->resetPage();
    }

    /**
     * Filtres nettoyes : une valeur invalide venue de l'URL est ignoree,
     * comme le faisait la validation de l'ancien controleur.
     *
     * @return array{q: string, category: string, availability: string, min_price: ?float, max_price: ?float, sort: string}
     */
    protected function filters(): array
    {
        $price = fn (string $value): ?float => is_numeric($value) && (float) $value >= 0 && (float) $value <= 999999999
            ? (float) $value
            : null;

        return [
            'q' => mb_substr(trim($this->q), 0, 120),
            'category' => mb_substr($this->category, 0, 160),
            'availability' => in_array($this->availability, self::AVAILABILITIES, true) ? $this->availability : 'all',
            'min_price' => $price($this->minPrice),
            'max_price' => $price($this->maxPrice),
            'sort' => in_array($this->sort, self::SORTS, true) ? $this->sort : 'recent',
        ];
    }

    public function render(): View
    {
        $filters = $this->filters();
        $availability = $filters['availability'];
        $sort = $filters['sort'];

        $products = Product::visible()
            ->with(['primaryImage', 'category'])
            ->search($filters['q'])
            ->when($filters['category'], fn (Builder $q, string $slug) => $q->whereHas(
                'category',
                fn (Builder $c) => $c->where('slug', $slug)
            ))
            ->when($filters['min_price'] !== null, fn (Builder $q) => $q->where('price', '>=', $filters['min_price']))
            ->when($filters['max_price'] !== null, fn (Builder $q) => $q->where('price', '<=', $filters['max_price']))
            ->when($availability === 'in_stock', fn (Builder $q) => $q->where('is_available', true)->where('stock', '>', 0))
            ->when($availability === 'out_of_stock', fn (Builder $q) => $q->where('is_available', true)->where('stock', '<=', 0))
            ->when($availability === 'unavailable', fn (Builder $q) => $q->where('is_available', false))
            ->when($sort === 'recent', fn (Builder $q) => $q->orderByDesc('published_at')->orderByDesc('id'))
            ->when($sort === 'price_asc', fn (Builder $q) => $q->orderBy('price'))
            ->when($sort === 'price_desc', fn (Builder $q) => $q->orderByDesc('price'))
            ->when($sort === 'name', fn (Builder $q) => $q->orderBy('name'))
            ->paginate(12)
            ->through(fn (Product $product) => Presenters::productCard($product));

        $activeCount = collect([
            $filters['q'],
            $filters['category'],
            $availability !== 'all' ? $availability : '',
            $filters['min_price'],
            $filters['max_price'],
        ])->filter(fn (mixed $value) => $value !== '' && $value !== null)->count();

        return view('livewire.shop.catalog', [
            'products' => $products,
            'categories' => Category::active()
                ->ordered()
                ->withCount(['products' => fn (Builder $q) => $q->visible()])
                ->get(['id', 'name', 'slug']),
            'filters' => $filters,
            'activeCount' => $activeCount,
            'priceRange' => [
                'min' => (float) (Product::visible()->min('price') ?? 0),
                'max' => (float) (Product::visible()->max('price') ?? 0),
            ],
            'availabilityOptions' => [
                'all' => 'Tous les produits',
                'in_stock' => 'Disponibles',
                'out_of_stock' => 'En rupture',
                'unavailable' => 'Indisponibles',
            ],
            'sortOptions' => [
                'recent' => 'Les plus recents',
                'price_asc' => 'Prix croissant',
                'price_desc' => 'Prix decroissant',
                'name' => 'Nom (A → Z)',
            ],
        ]);
    }
}
