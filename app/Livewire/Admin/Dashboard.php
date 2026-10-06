<?php

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Pack;
use App\Models\Product;
use App\Support\Money;
use App\Support\Presenters;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Tableau de bord : indicateurs, chiffre d'affaires des 14 derniers jours,
 * dernieres commandes et stocks a surveiller.
 */
#[Layout('layouts.admin')]
#[Title('Tableau de bord')]
class Dashboard extends Component
{
    public function render(): View
    {
        $threshold = (int) config('shop.low_stock_threshold');

        $revenue = (float) Order::whereNot('status', OrderStatus::Cancelled)->sum('total');

        $statusCounts = Order::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $lowStock = Product::published()
            ->where('is_available', true)
            ->where('stock', '<=', $threshold)
            ->orderBy('stock')
            ->take(8)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'stock' => $product->stock,
                'stock_state' => $product->stockState()->value,
                'stock_label' => $product->stockState()->label(),
            ]);

        $recentOrders = Order::latest()
            ->take(8)
            ->get()
            ->map(fn (Order $order) => Presenters::orderRow($order));

        return view('livewire.admin.dashboard', [
            'stats' => [
                'orders_total' => Order::count(),
                'orders_pending' => (int) ($statusCounts[OrderStatus::Pending->value] ?? 0),
                'orders_today' => Order::whereDate('created_at', today())->count(),
                'revenue' => $revenue,
                'revenue_label' => Money::format($revenue),
                'products_total' => Product::count(),
                'products_published' => Product::published()->count(),
                'products_out_of_stock' => Product::published()->where('is_available', true)->where('stock', '<=', 0)->count(),
                'products_unavailable' => Product::published()->where('is_available', false)->count(),
                'packs_total' => Pack::count(),
                'packs_published' => Pack::published()->count(),
                'low_stock_threshold' => $threshold,
            ],
            'statusBreakdown' => collect(OrderStatus::options())->map(fn (array $option) => [
                ...$option,
                'count' => (int) ($statusCounts[$option['value']] ?? 0),
            ])->values(),
            'lowStock' => $lowStock,
            'recentOrders' => $recentOrders,
            'series' => $this->revenueSeries(),
        ]);
    }

    /**
     * Chiffre d'affaires et nombre de commandes des 14 derniers jours,
     * annulations exclues.
     *
     * @return Collection<int, array{date: string, label: string, revenue: float, revenue_label: string, orders: int}>
     */
    private function revenueSeries(): Collection
    {
        $daily = Order::query()
            ->whereNot('status', OrderStatus::Cancelled)
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->select(DB::raw('date(created_at) as day'), DB::raw('sum(total) as total'), DB::raw('count(*) as orders'))
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        return collect(range(13, 0))->map(function (int $daysAgo) use ($daily) {
            $day = now()->subDays($daysAgo);
            $row = $daily->get($day->toDateString());
            $revenue = (float) ($row->total ?? 0);

            return [
                'date' => $day->toDateString(),
                'label' => $day->translatedFormat('d/m'),
                'revenue' => $revenue,
                'revenue_label' => Money::format($revenue),
                'orders' => (int) ($row->orders ?? 0),
            ];
        })->values();
    }
}
