<?php

namespace App\Livewire\Shop;

use App\Models\Occasion;
use App\Models\Offer;
use App\Models\Pack;
use App\Models\Product;
use App\Support\Presenters;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

/**
 * Vitrine d'une periode en cours (Noel, Saint-Valentin...) : articles reserves
 * a la periode et articles en promotion via une offre de la periode.
 */
class OccasionShow extends Component
{
    public Occasion $occasion;

    public function mount(Occasion $occasion): void
    {
        abort_unless($occasion->isRunning(), 404);

        $this->occasion = $occasion;
    }

    /**
     * Produits visibles lies a la periode, directement ou via une offre.
     *
     * @return Collection<int, Product>
     */
    public static function products(Occasion $occasion, ?int $limit = null): Collection
    {
        return Product::visible()
            ->with(['primaryImage', 'category'])
            ->where(fn (Builder $q) => $q
                ->where('occasion_id', $occasion->id)
                ->orWhereHas('offers', fn (Builder $o) => $o->where('occasion_id', $occasion->id)->where('is_active', true)))
            ->orderByDesc('is_featured')
            ->latest('published_at')
            ->when($limit, fn (Builder $q) => $q->take($limit))
            ->get();
    }

    /**
     * Packs visibles lies a la periode, directement ou via une offre.
     *
     * @return Collection<int, Pack>
     */
    public static function packs(Occasion $occasion, ?int $limit = null): Collection
    {
        return Pack::visible()
            ->with(['items.product.primaryImage'])
            ->where(fn (Builder $q) => $q
                ->where('occasion_id', $occasion->id)
                ->orWhereHas('offers', fn (Builder $o) => $o->where('occasion_id', $occasion->id)->where('is_active', true)))
            ->ordered()
            ->when($limit, fn (Builder $q) => $q->take($limit))
            ->get();
    }

    public function render(): View
    {
        return view('livewire.shop.occasion-show', [
            'occasionData' => [
                ...Presenters::occasionTag($this->occasion),
                'description' => $this->occasion->description,
            ],
            'offers' => $this->occasion->offers()
                ->where('is_active', true)
                ->orderByDesc('discount_percent')
                ->get()
                ->map(fn (Offer $offer) => [
                    'name' => $offer->name,
                    'description' => $offer->description,
                    'badge' => $offer->badge(),
                ]),
            'products' => self::products($this->occasion)
                ->map(fn (Product $product) => Presenters::productCard($product)),
            'packs' => self::packs($this->occasion)
                ->map(fn (Pack $pack) => Presenters::pack($pack)),
        ])->title($this->occasion->name);
    }
}
