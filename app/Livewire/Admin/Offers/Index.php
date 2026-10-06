<?php

namespace App\Livewire\Admin\Offers;

use App\Models\Offer;
use App\Support\Presenters;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Offres promotionnelles : remise en pourcentage sur des produits et packs,
 * le temps d'une periode (Noel, Saint-Valentin...) ou entre deux dates.
 */
#[Layout('layouts.admin')]
#[Title('Offres')]
class Index extends Component
{
    public function toggleActive(int $offerId): void
    {
        $offer = Offer::findOrFail($offerId);
        $offer->update(['is_active' => ! $offer->is_active]);

        $this->dispatch('toast', type: 'success', message: $offer->is_active
            ? "L'offre \"{$offer->name}\" est activee."
            : "L'offre \"{$offer->name}\" est desactivee.");
    }

    public function delete(int $offerId): void
    {
        $offer = Offer::findOrFail($offerId);
        $offer->delete();

        $this->dispatch('toast', type: 'success', message: "L'offre \"{$offer->name}\" a ete supprimee.");
    }

    public function render(): View
    {
        return view('livewire.admin.offers.index', [
            'offers' => Offer::with(['occasion', 'products:id,name', 'packs:id,name'])
                ->latest('id')
                ->get()
                ->map(fn (Offer $offer) => Presenters::offerAdmin($offer)),
            'statuses' => [
                'running' => ['label' => 'En cours', 'color' => 'emerald'],
                'upcoming' => ['label' => 'A venir', 'color' => 'amber'],
                'ended' => ['label' => 'Terminee', 'color' => 'slate'],
                'inactive' => ['label' => 'Desactivee', 'color' => 'slate'],
            ],
        ]);
    }
}
