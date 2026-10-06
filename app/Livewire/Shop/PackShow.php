<?php

namespace App\Livewire\Shop;

use App\Models\Pack;
use App\Support\Presenters;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Fiche d'un pack : contenu, prix groupe, economie realisee et autres packs.
 */
class PackShow extends Component
{
    public Pack $pack;

    public function mount(Pack $pack): void
    {
        abort_unless($pack->isVisible(), 404);

        $this->pack = $pack;
    }

    public function render(): View
    {
        $this->pack->load(['items.product.primaryImage']);

        $others = Pack::visible()
            ->with(['items.product'])
            ->whereKeyNot($this->pack->id)
            ->ordered()
            ->take(3)
            ->get()
            ->map(fn (Pack $p) => Presenters::pack($p, withItems: false));

        return view('livewire.shop.pack-show', [
            'packData' => Presenters::pack($this->pack),
            'others' => $others,
        ])->title($this->pack->name);
    }
}
