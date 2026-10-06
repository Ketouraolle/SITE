<?php

namespace App\Livewire\Shop;

use App\Services\CartService;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Panier du client (stocke en session, sans compte).
 */
#[Title('Mon panier')]
class Cart extends Component
{
    /**
     * Modifie la quantite d'une ligne ; 0 retire la ligne.
     */
    public function updateQuantity(CartService $cart, string $key, mixed $quantity): void
    {
        $validator = Validator::make(['quantity' => $quantity], [
            'quantity' => ['required', 'integer', 'min:0', 'max:'.config('shop.max_line_quantity')],
        ]);

        if ($validator->fails()) {
            $this->dispatch('toast', type: 'error', message: $validator->errors()->first('quantity'));

            return;
        }

        if (! $cart->has($key)) {
            $this->dispatch('toast', type: 'error', message: 'Cet article n\'est plus dans votre panier.');

            return;
        }

        $cart->setQuantity($key, (int) $quantity);

        $this->dispatch('cart-updated');
    }

    public function remove(CartService $cart, string $key): void
    {
        $cart->remove($key);

        $this->dispatch('cart-updated');
        $this->dispatch('toast', type: 'success', message: 'Article retire du panier.');
    }

    public function clear(CartService $cart): void
    {
        $cart->clear();

        $this->dispatch('cart-updated');
        $this->dispatch('toast', type: 'success', message: 'Votre panier a ete vide.');
    }

    /**
     * Panier resolu et enrichi des montants formates.
     *
     * @param  array{lines: array<int, array<string, mixed>>, total: float, count: int, has_blocking_issue: bool}  $contents
     * @return array{lines: array<int, array<string, mixed>>, total: float, total_label: string, count: int, has_blocking_issue: bool}
     */
    public static function payload(array $contents): array
    {
        return [
            'lines' => array_map(fn (array $line): array => [
                ...$line,
                'unit_price_label' => Money::format($line['unit_price']),
                'line_total_label' => Money::format($line['line_total']),
            ], $contents['lines']),
            'total' => $contents['total'],
            'total_label' => Money::format($contents['total']),
            'count' => $contents['count'],
            'has_blocking_issue' => $contents['has_blocking_issue'],
        ];
    }

    public function render(CartService $cart): View
    {
        return view('livewire.shop.cart', [
            'cart' => self::payload($cart->contents()),
        ]);
    }
}
