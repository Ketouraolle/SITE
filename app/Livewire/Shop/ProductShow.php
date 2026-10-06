<?php

namespace App\Livewire\Shop;

use App\Models\Product;
use App\Services\WhatsAppService;
use App\Support\Presenters;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

/**
 * Fiche produit : galerie, achat avec quantite et produits lies.
 * Un produit non publie (ou d'une periode terminee) renvoie une 404.
 */
class ProductShow extends Component
{
    public Product $product;

    public function mount(Product $product): void
    {
        abort_unless($product->isVisible(), 404);

        $this->product = $product;
    }

    public function render(WhatsAppService $whatsApp): View
    {
        $this->product->load(['images', 'category']);

        $related = Product::visible()
            ->with(['primaryImage', 'category'])
            ->whereKeyNot($this->product->id)
            ->when(
                $this->product->category_id,
                fn (Builder $q) => $q->where('category_id', $this->product->category_id),
                fn (Builder $q) => $q->where('is_featured', true)
            )
            ->inRandomOrder()
            ->take(4)
            ->get()
            ->map(fn (Product $p) => Presenters::productCard($p));

        return view('livewire.shop.product-show', [
            'productData' => Presenters::productDetail($this->product),
            'related' => $related,
            'whatsappUrl' => $whatsApp->contactUrl("Bonjour, je souhaite des informations sur « {$this->product->name} »."),
        ])->title($this->product->name);
    }
}
