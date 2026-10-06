<?php

namespace App\Livewire\Admin\Stock;

use App\Models\Category;
use App\Models\Product;
use App\Support\Presenters;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Gestion des stocks : quantites et disponibilite, ligne par ligne ou en
 * lot. Les modifications sont preparees dans le navigateur puis envoyees en
 * une seule fois.
 */
#[Layout('layouts.admin')]
#[Title('Stocks')]
class Index extends Component
{
    use WithPagination;

    /**
     * @var array<string, string>
     */
    public const STATES = [
        'all' => 'Tous',
        'low' => 'Stock faible',
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
     * Ajustement d'une seule ligne : valeur absolue ("stock") ou increment
     * relatif ("delta"), et eventuellement la disponibilite.
     *
     * @param  array{stock?: int|string|null, delta?: int|string|null, is_available?: bool|null}  $changes
     */
    public function updateStock(int $productId, array $changes): void
    {
        $product = Product::findOrFail($productId);

        $data = Validator::make($changes, [
            'stock' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'delta' => ['nullable', 'integer', 'min:-1000000', 'max:1000000'],
            'is_available' => ['nullable', 'boolean'],
        ])->validate();

        DB::transaction(function () use ($data, $product) {
            $fresh = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();

            if (isset($data['stock'])) {
                $fresh->stock = (int) $data['stock'];
            } elseif (! empty($data['delta'])) {
                $fresh->stock = max(0, $fresh->stock + (int) $data['delta']);
            }

            if (isset($data['is_available'])) {
                $fresh->is_available = (bool) $data['is_available'];
            }

            $fresh->save();
        });

        $this->dispatch('toast', type: 'success', message: "Stock de \"{$product->name}\" mis a jour.");
    }

    /**
     * Ajout ou retrait relatif d'unites, sans jamais descendre sous zero.
     */
    public function adjustStock(int $productId, int $delta): void
    {
        $this->updateStock($productId, ['delta' => $delta]);
    }

    /**
     * Enregistrement groupe depuis le tableau de gestion des stocks.
     *
     * @param  array<int, array{id: int, stock: int, is_available: bool}>  $updates
     */
    public function bulkUpdate(array $updates): void
    {
        $data = Validator::make(['updates' => $updates], [
            'updates' => ['required', 'array', 'min:1', 'max:200'],
            'updates.*.id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'updates.*.stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'updates.*.is_available' => ['required', 'boolean'],
        ])->validate();

        DB::transaction(function () use ($data) {
            foreach ($data['updates'] as $update) {
                Product::whereKey($update['id'])->update([
                    'stock' => (int) $update['stock'],
                    'is_available' => (bool) $update['is_available'],
                ]);
            }
        });

        $count = count($data['updates']);

        $this->dispatch('toast', type: 'success', message: "{$count} produit(s) mis a jour.");
    }

    public function render(): View
    {
        $state = array_key_exists($this->state, self::STATES) ? $this->state : 'all';
        $categoryId = ctype_digit($this->category) ? (int) $this->category : null;
        $threshold = (int) config('shop.low_stock_threshold');

        $products = Product::query()
            ->with(['category', 'primaryImage'])
            ->search(mb_substr($this->q, 0, 120))
            ->when($categoryId, fn (Builder $q, int $id) => $q->where('category_id', $id))
            ->when($state === 'low', fn (Builder $q) => $q->where('is_available', true)
                ->where('stock', '>', 0)
                ->where('stock', '<=', $threshold))
            ->when($state === 'out_of_stock', fn (Builder $q) => $q->where('stock', '<=', 0))
            ->when($state === 'unavailable', fn (Builder $q) => $q->where('is_available', false))
            ->orderBy('stock')
            ->orderBy('name')
            ->paginate(25)
            ->through(fn (Product $product) => Presenters::productAdmin($product));

        return view('livewire.admin.stock.index', [
            'products' => $products,
            'categories' => Category::ordered()->get(['id', 'name']),
            'states' => self::STATES,
            'threshold' => $threshold,
            'summary' => [
                'out_of_stock' => Product::where('stock', '<=', 0)->count(),
                'low' => Product::where('is_available', true)->where('stock', '>', 0)->where('stock', '<=', $threshold)->count(),
                'unavailable' => Product::where('is_available', false)->count(),
                'units' => (int) Product::sum('stock'),
            ],
        ]);
    }
}
