<?php

namespace App\Livewire\Shop;

use App\Models\Pack;
use App\Support\Presenters;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Liste des packs publies par la boutique.
 */
#[Title('Packs')]
class PackIndex extends Component
{
    public function render(): View
    {
        return view('livewire.shop.pack-index', [
            'packs' => Pack::visible()
                ->with(['items.product.primaryImage'])
                ->ordered()
                ->get()
                ->map(fn (Pack $pack) => Presenters::pack($pack)),
        ]);
    }
}
