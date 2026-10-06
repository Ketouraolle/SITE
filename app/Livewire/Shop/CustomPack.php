<?php

namespace App\Livewire\Shop;

use App\Models\Category;
use App\Models\Product;
use App\Services\CartService;
use App\Support\Money;
use App\Support\Presenters;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Composition d'un pack par le client, a partir des produits commandables.
 * La selection vit dans le composant : changer de filtre ne la perd jamais.
 */
#[Title('Composer mon pack')]
class CustomPack extends Component
{
    /**
     * Nombre d'ajouts au panier autorises par minute et par adresse IP.
     */
    private const MAX_ATTEMPTS_PER_MINUTE = 30;

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $category = '';

    /**
     * Produits retenus, dans l'ordre de selection.
     *
     * @var array<int, array{product_id: int, quantity: int}>
     */
    public array $items = [];

    public string $label = '';

    public int $quantity = 1;

    /**
     * Ajoute le produit a la selection, ou l'en retire s'il y est deja.
     */
    public function toggle(int $productId): void
    {
        if ($this->selectedIndex($productId) !== null) {
            $this->removeItem($productId);

            return;
        }

        $this->items[] = ['product_id' => $productId, 'quantity' => 1];
    }

    public function removeItem(int $productId): void
    {
        $this->items = array_values(array_filter(
            $this->items,
            fn (array $item): bool => (int) $item['product_id'] !== $productId,
        ));
    }

    public function updatedItems(): void
    {
        foreach ($this->items as $index => $item) {
            $this->items[$index]['quantity'] = max(1, (int) ($item['quantity'] ?? 1));
        }
    }

    public function updatedQuantity(): void
    {
        $this->quantity = max(1, $this->quantity);
    }

    public function addToCart(CartService $cart): void
    {
        $rateLimitKey = 'custom-pack:'.request()->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::MAX_ATTEMPTS_PER_MINUTE)) {
            $this->addError('items', 'Trop de tentatives. Merci de patienter '.RateLimiter::availableIn($rateLimitKey).' seconde(s) avant de reessayer.');

            return;
        }

        RateLimiter::hit($rateLimitKey, 60);

        $limits = config('shop.custom_pack');

        $data = $this->validate([
            'label' => ['nullable', 'string', 'max:60'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:'.config('shop.max_line_quantity')],
            'items' => ['required', 'array', 'min:'.$limits['min_products'], 'max:'.$limits['max_products']],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:'.config('shop.max_line_quantity')],
        ], [
            'items.required' => 'Choisissez au moins :min produits pour composer votre pack.',
            'items.min' => 'Choisissez au moins :min produits pour composer votre pack.',
            'items.max' => 'Un pack ne peut pas contenir plus de :max produits differents.',
            'items.*.product_id.distinct' => 'Un produit ne peut etre ajoute qu\'une fois au pack.',
        ]);

        $quantity = (int) ($data['quantity'] ?? 1);

        $products = Product::whereIn('id', array_column($data['items'], 'product_id'))
            ->get()
            ->keyBy('id');

        // On verifie que chaque produit retenu est bien commandable et que le
        // stock couvre la quantite demandee pour l'ensemble du pack.
        $errors = [];

        foreach ($data['items'] as $item) {
            /** @var Product|null $product */
            $product = $products->get((int) $item['product_id']);

            if (! $product || ! $product->isOrderable()) {
                $errors[] = $product
                    ? "\"{$product->name}\" n'est pas disponible."
                    : 'Un produit selectionne est introuvable.';

                continue;
            }

            $needed = (int) $item['quantity'] * $quantity;

            if ($product->stock < $needed) {
                $errors[] = "Stock insuffisant pour \"{$product->name}\" : {$product->stock} disponible(s).";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['items' => $errors]);
        }

        $label = trim((string) ($data['label'] ?? '')) ?: 'Mon pack personnalise';

        $cart->addCustomPack($label, $data['items'], $quantity);

        $this->reset('items', 'label', 'quantity');

        session()->flash('success', 'Votre pack a ete ajoute au panier.');

        $this->redirectRoute('cart.index', navigate: true);
    }

    private function selectedIndex(int $productId): ?int
    {
        foreach ($this->items as $index => $item) {
            if ((int) $item['product_id'] === $productId) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Produits commandables correspondant aux filtres.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function availableProducts(): Collection
    {
        return Product::orderable()
            ->with(['primaryImage', 'category'])
            ->when($this->q !== '', fn (Builder $query) => $query->search($this->q))
            ->when($this->category !== '', fn (Builder $query) => $query->whereHas(
                'category',
                fn (Builder $categories) => $categories->where('slug', $this->category),
            ))
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product): array => Presenters::productCard($product));
    }

    /**
     * Lignes de la selection, enrichies de la carte produit.
     *
     * @return array<int, array{index: int, quantity: int, product: array<string, mixed>}>
     */
    private function selectedLines(): array
    {
        $ids = array_map(fn (array $item): int => (int) $item['product_id'], $this->items);

        if ($ids === []) {
            return [];
        }

        $cards = Product::with(['primaryImage', 'category'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id')
            ->map(fn (Product $product): array => Presenters::productCard($product));

        $lines = [];

        foreach ($this->items as $index => $item) {
            $card = $cards->get((int) $item['product_id']);

            if ($card !== null) {
                $lines[] = ['index' => $index, 'quantity' => max(1, (int) $item['quantity']), 'product' => $card];
            }
        }

        return $lines;
    }

    public function render(): View
    {
        $limits = config('shop.custom_pack');
        $lines = $this->selectedLines();

        $unitTotal = array_sum(array_map(
            fn (array $line): float => (float) $line['product']['price'] * $line['quantity'],
            $lines,
        ));

        // Nombre de packs que le stock autorise, selon le produit le plus contraint.
        $maxPacks = $lines === []
            ? 1
            : max(1, min(array_map(
                fn (array $line): int => intdiv((int) $line['product']['max_quantity'], $line['quantity']),
                $lines,
            )));

        $count = count($lines);

        return view('livewire.shop.custom-pack', [
            'products' => $this->availableProducts(),
            'categories' => Category::active()->ordered()->get(['id', 'name', 'slug']),
            'limits' => $limits,
            'lines' => $lines,
            'selectedIndexes' => collect($lines)
                ->mapWithKeys(fn (array $line): array => [$line['product']['id'] => $line['index']])
                ->all(),
            'count' => $count,
            'maxPacks' => $maxPacks,
            'unitTotalLabel' => Money::format($unitTotal),
            'grandTotalLabel' => Money::format($unitTotal * max(1, $this->quantity)),
            'enoughProducts' => $count >= $limits['min_products'],
            'tooManyProducts' => $count > $limits['max_products'],
        ]);
    }
}
