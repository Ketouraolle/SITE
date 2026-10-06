<?php

namespace App\Services;

use App\Enums\StockState;
use App\Models\Pack;
use App\Models\Product;
use Illuminate\Session\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Panier stocke en session : le client ne possede aucun compte.
 *
 * La session ne conserve que des references (type, identifiants, quantites).
 * Prix, noms et disponibilites sont toujours relus en base a l'affichage,
 * ce qui empeche un panier de figer un ancien prix.
 *
 * Forme d'une ligne en session :
 *   [
 *     'type'       => 'product'|'pack'|'custom_pack',
 *     'product_id' => ?int,
 *     'pack_id'    => ?int,
 *     'label'      => ?string,  // packs personnalises
 *     'quantity'   => int,
 *     'components' => array<int, array{product_id:int, quantity:int}>,
 *   ]
 */
class CartService
{
    public const SESSION_KEY = 'shop.cart';

    public function __construct(private readonly Store $session) {}

    /* --------------------------------------------------------------------- */
    /* Ecriture */
    /* --------------------------------------------------------------------- */

    public function addProduct(Product $product, int $quantity = 1): string
    {
        $key = 'p:'.$product->id;
        $lines = $this->lines();

        $current = $lines[$key]['quantity'] ?? 0;

        $lines[$key] = [
            'type' => 'product',
            'product_id' => $product->id,
            'pack_id' => null,
            'label' => null,
            'quantity' => $this->clampQuantity($current + $quantity),
            'components' => [],
        ];

        $this->persist($lines);

        return $key;
    }

    public function addPack(Pack $pack, int $quantity = 1): string
    {
        $key = 'k:'.$pack->id;
        $lines = $this->lines();

        $current = $lines[$key]['quantity'] ?? 0;

        $lines[$key] = [
            'type' => 'pack',
            'product_id' => null,
            'pack_id' => $pack->id,
            'label' => null,
            'quantity' => $this->clampQuantity($current + $quantity),
            'components' => [],
        ];

        $this->persist($lines);

        return $key;
    }

    /**
     * @param  array<int, array{product_id:int, quantity:int}>  $components
     */
    public function addCustomPack(string $label, array $components, int $quantity = 1): string
    {
        $key = 'c:'.Str::lower(Str::random(12));
        $lines = $this->lines();

        $lines[$key] = [
            'type' => 'custom_pack',
            'product_id' => null,
            'pack_id' => null,
            'label' => $label,
            'quantity' => $this->clampQuantity($quantity),
            'components' => array_values(array_map(fn (array $c) => [
                'product_id' => (int) $c['product_id'],
                'quantity' => max(1, (int) $c['quantity']),
            ], $components)),
        ];

        $this->persist($lines);

        return $key;
    }

    public function setQuantity(string $key, int $quantity): void
    {
        $lines = $this->lines();

        if (! isset($lines[$key])) {
            return;
        }

        if ($quantity < 1) {
            unset($lines[$key]);
        } else {
            $lines[$key]['quantity'] = $this->clampQuantity($quantity);
        }

        $this->persist($lines);
    }

    public function remove(string $key): void
    {
        $lines = $this->lines();
        unset($lines[$key]);
        $this->persist($lines);
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    public function has(string $key): bool
    {
        return isset($this->lines()[$key]);
    }

    /* --------------------------------------------------------------------- */
    /* Lecture */
    /* --------------------------------------------------------------------- */

    /**
     * Resout le panier contre la base : prix a jour, disponibilites,
     * quantites maximales et messages de blocage.
     *
     * @return array{
     *     lines: array<int, array<string, mixed>>,
     *     total: float,
     *     count: int,
     *     has_blocking_issue: bool
     * }
     */
    public function contents(): array
    {
        $raw = $this->lines();

        if ($raw === []) {
            return ['lines' => [], 'total' => 0.0, 'count' => 0, 'has_blocking_issue' => false];
        }

        [$products, $packs] = $this->loadReferences($raw);

        $lines = [];
        $pruned = false;

        foreach ($raw as $key => $line) {
            $resolved = match ($line['type']) {
                'product' => $this->resolveProductLine($key, $line, $products),
                'pack' => $this->resolvePackLine($key, $line, $packs),
                'custom_pack' => $this->resolveCustomPackLine($key, $line, $products),
                default => null,
            };

            if ($resolved === null) {
                // La reference a disparu (produit supprime, pack depublie) :
                // la ligne est retiree silencieusement du panier.
                unset($raw[$key]);
                $pruned = true;

                continue;
            }

            $lines[] = $resolved;
        }

        if ($pruned) {
            $this->persist($raw);
        }

        $total = array_sum(array_column($lines, 'line_total'));

        return [
            'lines' => $lines,
            'total' => round((float) $total, 2),
            'count' => array_sum(array_column($lines, 'quantity')),
            'has_blocking_issue' => collect($lines)->contains(fn (array $l) => $l['issue'] !== null),
        ];
    }

    /**
     * Nombre d'articles, pour le badge du panier. Volontairement leger : pas
     * de resolution complete des lignes.
     */
    public function count(): int
    {
        return (int) array_sum(array_column($this->lines(), 'quantity'));
    }

    /* --------------------------------------------------------------------- */
    /* Resolution des lignes */
    /* --------------------------------------------------------------------- */

    /**
     * @param  array<string, array<string, mixed>>  $raw
     * @return array{0: Collection<int, Product>, 1: Collection<int, Pack>}
     */
    private function loadReferences(array $raw): array
    {
        $productIds = [];
        $packIds = [];

        foreach ($raw as $line) {
            if ($line['type'] === 'product' && $line['product_id']) {
                $productIds[] = (int) $line['product_id'];
            }

            if ($line['type'] === 'pack' && $line['pack_id']) {
                $packIds[] = (int) $line['pack_id'];
            }

            foreach ($line['components'] ?? [] as $component) {
                $productIds[] = (int) $component['product_id'];
            }
        }

        $products = $productIds === []
            ? collect()
            : Product::with('primaryImage')->whereIn('id', array_unique($productIds))->get()->keyBy('id');

        $packs = $packIds === []
            ? collect()
            : Pack::with(['items.product'])->whereIn('id', array_unique($packIds))->get()->keyBy('id');

        return [$products, $packs];
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return array<string, mixed>|null
     */
    private function resolveProductLine(string $key, array $line, $products): ?array
    {
        /** @var Product|null $product */
        $product = $products->get((int) $line['product_id']);

        if (! $product || ! $product->isVisible()) {
            return null;
        }

        $state = $product->stockState();
        $quantity = (int) $line['quantity'];
        $max = $product->is_available ? $product->stock : 0;

        return [
            'key' => $key,
            'type' => 'product',
            'name' => $product->name,
            'url' => route('catalog.show', $product->slug),
            'image' => $product->primaryImage?->url,
            'unit_price' => $product->salePrice(),
            'quantity' => $quantity,
            'line_total' => round($product->salePrice() * $quantity, 2),
            'max_quantity' => $max,
            'stock_state' => $state->value,
            'stock_label' => $state->label(),
            'components' => [],
            'issue' => $this->issueFor($state, $quantity, $max),
        ];
    }

    /**
     * @param  Collection<int, Pack>  $packs
     * @return array<string, mixed>|null
     */
    private function resolvePackLine(string $key, array $line, $packs): ?array
    {
        /** @var Pack|null $pack */
        $pack = $packs->get((int) $line['pack_id']);

        if (! $pack || ! $pack->isVisible()) {
            return null;
        }

        $quantity = (int) $line['quantity'];
        $max = $pack->availableQuantity();
        $unitPrice = $pack->effectivePrice();

        $issue = match (true) {
            $max <= 0 => 'Ce pack est momentanement indisponible.',
            $quantity > $max => "Seulement {$max} exemplaire(s) disponible(s).",
            default => null,
        };

        return [
            'key' => $key,
            'type' => 'pack',
            'name' => $pack->name,
            'url' => route('packs.show', $pack->slug),
            'image' => $pack->image_url,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'line_total' => round($unitPrice * $quantity, 2),
            'max_quantity' => $max,
            'stock_state' => $max > 0 ? 'in_stock' : 'out_of_stock',
            'stock_label' => $max > 0 ? 'Disponible' : 'Indisponible',
            'components' => $pack->items
                ->filter(fn ($item) => $item->product !== null)
                ->map(fn ($item) => [
                    'name' => $item->product->name,
                    'quantity' => $item->quantity,
                ])->values()->all(),
            'issue' => $issue,
        ];
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return array<string, mixed>|null
     */
    private function resolveCustomPackLine(string $key, array $line, $products): ?array
    {
        $components = [];
        $unitPrice = 0.0;
        $max = PHP_INT_MAX;
        $missing = [];

        foreach ($line['components'] ?? [] as $component) {
            /** @var Product|null $product */
            $product = $products->get((int) $component['product_id']);
            $perPack = max(1, (int) $component['quantity']);

            if (! $product || ! $product->isVisible()) {
                $missing[] = 'un produit retire du catalogue';
                $max = 0;

                continue;
            }

            $components[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'quantity' => $perPack,
                'unit_price' => $product->salePrice(),
            ];

            $unitPrice += $product->salePrice() * $perPack;

            if (! $product->isOrderable()) {
                $missing[] = $product->name;
                $max = 0;
            } else {
                $max = min($max, intdiv($product->stock, $perPack));
            }
        }

        if ($components === [] && $missing === []) {
            return null;
        }

        $max = $max === PHP_INT_MAX ? 0 : $max;
        $quantity = (int) $line['quantity'];

        $issue = match (true) {
            $missing !== [] => 'Indisponible : '.implode(', ', array_unique($missing)).'.',
            $max <= 0 => 'Les produits de ce pack ne sont plus tous disponibles.',
            $quantity > $max => "Seulement {$max} exemplaire(s) disponible(s).",
            default => null,
        };

        return [
            'key' => $key,
            'type' => 'custom_pack',
            'name' => $line['label'] ?: 'Mon pack',
            'url' => route('custom-pack.create'),
            'image' => null,
            'unit_price' => round($unitPrice, 2),
            'quantity' => $quantity,
            'line_total' => round($unitPrice * $quantity, 2),
            'max_quantity' => max(0, $max),
            'stock_state' => $max > 0 ? 'in_stock' : 'out_of_stock',
            'stock_label' => $max > 0 ? 'Disponible' : 'Indisponible',
            'components' => $components,
            'issue' => $issue,
        ];
    }

    private function issueFor(StockState $state, int $quantity, int $max): ?string
    {
        return match (true) {
            ! $state->isOrderable() => 'Ce produit est '.mb_strtolower($state->label()).'.',
            $quantity > $max => "Seulement {$max} en stock.",
            default => null,
        };
    }

    /* --------------------------------------------------------------------- */
    /* Session */
    /* --------------------------------------------------------------------- */

    /**
     * @return array<string, array<string, mixed>>
     */
    public function lines(): array
    {
        $lines = $this->session->get(self::SESSION_KEY, []);

        return is_array($lines) ? $lines : [];
    }

    /**
     * @param  array<string, array<string, mixed>>  $lines
     */
    private function persist(array $lines): void
    {
        if ($lines === []) {
            $this->session->forget(self::SESSION_KEY);

            return;
        }

        $this->session->put(self::SESSION_KEY, $lines);
    }

    private function clampQuantity(int $quantity): int
    {
        return max(1, min($quantity, config('shop.max_line_quantity')));
    }
}
