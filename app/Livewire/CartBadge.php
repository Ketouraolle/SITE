<?php

namespace App\Livewire;

use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Pastille du nombre d'articles, rafraichie par l'evenement "cart-updated".
 */
class CartBadge extends Component
{
    /**
     * "header" (icone de l'en-tete) ou "tab" (barre de navigation mobile).
     */
    public string $variant = 'header';

    #[On('cart-updated')]
    public function refreshCount(): void
    {
        // Le rendu suivant relit le panier en session.
    }

    public function render(CartService $cart): View
    {
        return view('livewire.cart-badge', [
            'count' => $cart->count(),
        ]);
    }
}
