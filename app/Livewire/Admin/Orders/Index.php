<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\Money;
use App\Support\Presenters;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Liste des commandes : recherche plein texte et filtre par statut.
 */
#[Layout('layouts.admin')]
#[Title('Commandes')]
class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: 'all')]
    public string $status = 'all';

    public function mount(): void
    {
        $this->normalizeFilters();
    }

    public function updatedQ(): void
    {
        $this->normalizeFilters();
        $this->resetPage();
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->normalizeFilters();
        $this->resetPage();
    }

    public function render(): View
    {
        $orders = $this->filteredQuery()
            ->latest('id')
            ->paginate(20)
            ->through(fn (Order $order) => Presenters::orderRow($order));

        return view('livewire.admin.orders.index', [
            'orders' => $orders,
            'statuses' => OrderStatus::options(),
            'summary' => [
                'count' => $orders->total(),
                'total_label' => Money::format($this->filteredQuery()->sum('total')),
            ],
        ]);
    }

    /**
     * @return Builder<Order>
     */
    private function filteredQuery(): Builder
    {
        return Order::query()
            ->search($this->q)
            ->when($this->status !== 'all', fn (Builder $query) => $query->where('status', $this->status));
    }

    /**
     * Ramene les filtres modifiables dans l'URL a des valeurs connues.
     */
    private function normalizeFilters(): void
    {
        $this->q = mb_substr($this->q, 0, 120);

        if ($this->status !== 'all' && OrderStatus::tryFrom($this->status) === null) {
            $this->status = 'all';
        }
    }
}
