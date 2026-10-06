<?php

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Produits les plus commandes, en comptant les ventes a l'unite et les
 * produits vendus a l'interieur des packs. Les commandes annulees sont
 * exclues.
 */
#[Layout('layouts.admin')]
#[Title('Statistiques')]
class Statistics extends Component
{
    /**
     * Periodes proposees, en jours. "all" couvre tout l'historique.
     *
     * @var array<string, array{label: string, days: int|null}>
     */
    public const PERIODS = [
        '7' => ['label' => '7 jours', 'days' => 7],
        '30' => ['label' => '30 jours', 'days' => 30],
        '90' => ['label' => '3 mois', 'days' => 90],
        '365' => ['label' => '12 mois', 'days' => 365],
        'all' => ['label' => 'Tout', 'days' => null],
    ];

    #[Url(as: 'period', except: '30')]
    public string $period = '30';

    public function mount(): void
    {
        $this->normalizePeriod();
    }

    public function updatedPeriod(): void
    {
        $this->normalizePeriod();
    }

    public function selectPeriod(string $period): void
    {
        $this->period = $period;
        $this->normalizePeriod();
    }

    public function render(): View
    {
        $days = self::PERIODS[$this->period]['days'];

        $orders = Order::query()
            ->whereNot('status', OrderStatus::Cancelled)
            ->when($days, fn ($q) => $q->where('created_at', '>=', now()->subDays($days - 1)->startOfDay()));

        $items = OrderItem::query()
            ->whereIn('order_id', $orders->clone()->select('id'))
            ->get(['order_id', 'type', 'product_id', 'pack_id', 'name', 'quantity', 'line_total', 'components']);

        $products = $this->aggregateProducts($items);
        $revenue = (float) $orders->clone()->sum('total');

        return view('livewire.admin.statistics', [
            'periods' => collect(self::PERIODS)
                ->map(fn (array $definition, string $value) => ['value' => (string) $value, 'label' => $definition['label']])
                ->values(),
            'summary' => [
                'orders' => $orders->clone()->count(),
                'units' => $products->sum('units'),
                'distinct_products' => $products->count(),
                'revenue_label' => Money::format($revenue),
            ],
            'topProducts' => $products->take(20)->values(),
            'topCategories' => $this->aggregateCategories($products),
            'topPacks' => $this->aggregatePacks($items)->take(10)->values(),
            'neverOrdered' => Product::published()
                ->whereNotIn('id', $products->pluck('product_id')->filter())
                ->orderBy('name')
                ->take(10)
                ->get(['id', 'name', 'stock'])
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'stock' => $product->stock,
                ]),
        ]);
    }

    /**
     * Une periode inconnue (URL modifiee a la main) retombe sur 30 jours.
     */
    private function normalizePeriod(): void
    {
        if (! array_key_exists($this->period, self::PERIODS)) {
            $this->period = '30';
        }
    }

    /**
     * @param  Collection<int, OrderItem>  $items
     * @return Collection<int, array{product_id: int|null, name: string, category: string|null, units: int, units_direct: int, units_in_packs: int, orders: int, revenue: float, revenue_label: string, stock: int|null}>
     */
    private function aggregateProducts(Collection $items): Collection
    {
        $rows = [];

        $add = function (?int $productId, string $name, int $units, bool $direct, int $orderId, float $revenue) use (&$rows): void {
            $key = $productId ?? 'name:'.$name;

            $rows[$key] ??= [
                'product_id' => $productId,
                'name' => $name,
                'units_direct' => 0,
                'units_in_packs' => 0,
                'orders' => [],
                'revenue' => 0.0,
            ];

            $rows[$key][$direct ? 'units_direct' : 'units_in_packs'] += $units;
            $rows[$key]['orders'][$orderId] = true;
            $rows[$key]['revenue'] += $revenue;
        };

        foreach ($items as $item) {
            if (! $item->isPack()) {
                $add($item->product_id, $item->name, $item->quantity, true, $item->order_id, (float) $item->line_total);

                continue;
            }

            foreach ($item->components ?? [] as $component) {
                $units = (int) ($component['quantity'] ?? 1) * $item->quantity;

                $add(
                    isset($component['product_id']) ? (int) $component['product_id'] : null,
                    (string) ($component['name'] ?? 'Produit'),
                    $units,
                    false,
                    $item->order_id,
                    (float) ($component['unit_price'] ?? 0) * $units,
                );
            }
        }

        $catalog = Product::withTrashed()
            ->with('category:id,name')
            ->whereIn('id', array_filter(array_column($rows, 'product_id')))
            ->get(['id', 'name', 'category_id', 'stock'])
            ->keyBy('id');

        return collect($rows)
            ->map(function (array $row) use ($catalog) {
                $product = $row['product_id'] ? $catalog->get($row['product_id']) : null;

                return [
                    'product_id' => $row['product_id'],
                    'name' => $product?->name ?? $row['name'],
                    'category' => $product?->category?->name,
                    'units' => $row['units_direct'] + $row['units_in_packs'],
                    'units_direct' => $row['units_direct'],
                    'units_in_packs' => $row['units_in_packs'],
                    'orders' => count($row['orders']),
                    'revenue' => round($row['revenue'], 2),
                    'revenue_label' => Money::format($row['revenue']),
                    'stock' => $product && ! $product->trashed() ? $product->stock : null,
                ];
            })
            ->sortByDesc(fn (array $row) => [$row['units'], $row['revenue']])
            ->values();
    }

    /**
     * @param  Collection<int, array{category: string|null, units: int, revenue: float}>  $products
     * @return Collection<int, array{name: string, units: int, revenue_label: string, share: float}>
     */
    private function aggregateCategories(Collection $products): Collection
    {
        $totalUnits = max(1, $products->sum('units'));

        return $products
            ->groupBy(fn (array $row) => $row['category'] ?? 'Sans categorie')
            ->map(fn (Collection $rows, string $name) => [
                'name' => $name,
                'units' => $rows->sum('units'),
                'revenue_label' => Money::format($rows->sum('revenue')),
                'share' => round($rows->sum('units') / $totalUnits * 100, 1),
            ])
            ->sortByDesc('units')
            ->values();
    }

    /**
     * @param  Collection<int, OrderItem>  $items
     * @return Collection<int, array{name: string, units: int, orders: int, revenue_label: string}>
     */
    private function aggregatePacks(Collection $items): Collection
    {
        return $items
            ->filter(fn (OrderItem $item) => $item->isPack())
            ->groupBy(fn (OrderItem $item) => $item->pack_id ?? 'name:'.$item->name)
            ->map(fn (Collection $rows) => [
                'name' => $rows->first()->name,
                'units' => $rows->sum('quantity'),
                'orders' => $rows->pluck('order_id')->unique()->count(),
                'revenue_label' => Money::format($rows->sum(fn (OrderItem $item) => (float) $item->line_total)),
            ])
            ->sortByDesc('units')
            ->values();
    }
}
