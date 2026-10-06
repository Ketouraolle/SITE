<?php

namespace App\Livewire;

use App\Models\Pack;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Bouton "Ajouter au panier" d'un produit ou d'un pack, avec quantite
 * optionnelle. Le stock est reverifie cote serveur a chaque ajout.
 */
class AddToCart extends Component
{
    /**
     * "product" ou "pack".
     */
    #[Locked]
    public string $kind = 'product';

    #[Locked]
    public int $itemId;

    public int $maxQuantity = 0;

    public bool $orderable = false;

    public bool $withQuantity = true;

    public string $label = 'Ajouter au panier';

    public string $size = 'md';

    public bool $block = false;

    public int $quantity = 1;

    public function add(CartService $cart): void
    {
        $this->resetErrorBag();

        $this->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:'.config('shop.max_line_quantity')],
        ]);

        $name = $this->kind === 'pack'
            ? $this->addPack($cart)
            : $this->addProduct($cart);

        if ($name === null) {
            return;
        }

        $this->quantity = 1;

        $this->dispatch('cart-updated');
        $this->dispatch('toast', type: 'success', message: $this->kind === 'pack'
            ? "Le pack \"{$name}\" a ete ajoute au panier."
            : "\"{$name}\" a ete ajoute au panier.");
    }

    private function addProduct(CartService $cart): ?string
    {
        $product = Product::findOrFail($this->itemId);

        if (! $product->isOrderable()) {
            $this->addError('quantity', 'Ce produit n\'est pas disponible a la commande.');

            return null;
        }

        $alreadyInCart = $cart->lines()['p:'.$product->id]['quantity'] ?? 0;

        if ($alreadyInCart + $this->quantity > $product->stock) {
            $this->addError('quantity', "Stock insuffisant : {$product->stock} exemplaire(s) disponible(s).");

            return null;
        }

        $cart->addProduct($product, $this->quantity);

        return $product->name;
    }

    private function addPack(CartService $cart): ?string
    {
        $pack = Pack::with('items.product')->findOrFail($this->itemId);

        if (! $pack->isOrderable()) {
            $this->addError('quantity', 'Ce pack n\'est pas disponible a la commande.');

            return null;
        }

        $alreadyInCart = $cart->lines()['k:'.$pack->id]['quantity'] ?? 0;
        $available = $pack->availableQuantity();

        if ($alreadyInCart + $this->quantity > $available) {
            $this->addError('quantity', "Stock insuffisant : {$available} pack(s) disponible(s).");

            return null;
        }

        $cart->addPack($pack, $this->quantity);

        return $pack->name;
    }

    public function render(): View
    {
        return view('livewire.add-to-cart');
    }
}
