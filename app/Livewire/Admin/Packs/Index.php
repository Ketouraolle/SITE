<?php

namespace App\Livewire\Admin\Packs;

use App\Models\Pack;
use App\Support\Presenters;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Packs predefinis : composition, prix, stock servable et publication.
 */
#[Layout('layouts.admin')]
#[Title('Packs')]
class Index extends Component
{
    public function togglePublish(int $packId): void
    {
        $pack = Pack::findOrFail($packId);

        $pack->update(['is_published' => ! $pack->is_published]);

        $this->dispatch('toast', type: 'success', message: $pack->is_published
            ? "\"{$pack->name}\" est maintenant publie."
            : "\"{$pack->name}\" a ete depublie.");
    }

    public function destroy(int $packId): void
    {
        $pack = Pack::findOrFail($packId);
        $pack->delete();

        $this->dispatch('toast', type: 'success', message: "Le pack \"{$pack->name}\" a ete supprime.");
    }

    public function render(): View
    {
        return view('livewire.admin.packs.index', [
            'packs' => Pack::with(['items.product', 'occasion'])
                ->ordered()
                ->get()
                ->map(fn (Pack $pack) => Presenters::packAdmin($pack)),
        ]);
    }
}
