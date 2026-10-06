<?php

namespace App\Livewire\Shop;

use App\Services\CartService;
use App\Services\OrderService;
use App\Services\WhatsAppService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Saisie des coordonnees et enregistrement de la commande.
 */
#[Title('Finaliser ma commande')]
class Checkout extends Component
{
    /**
     * Nombre de validations autorisees par minute et par adresse IP.
     */
    private const MAX_ATTEMPTS_PER_MINUTE = 10;

    public string $customer_name = '';

    public string $customer_phone = '';

    public string $customer_address = '';

    public string $customer_note = '';

    public function mount(CartService $cart): void
    {
        $contents = $cart->contents();

        if ($contents['lines'] === []) {
            session()->flash('error', 'Votre panier est vide.');
            $this->redirectRoute('cart.index', navigate: true);

            return;
        }

        if ($contents['has_blocking_issue']) {
            session()->flash('error', 'Certains articles ne sont plus disponibles. Merci de corriger votre panier.');
            $this->redirectRoute('cart.index', navigate: true);
        }
    }

    public function place(OrderService $orders): void
    {
        $rateLimitKey = 'checkout:'.request()->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::MAX_ATTEMPTS_PER_MINUTE)) {
            $this->addError('cart', 'Trop de tentatives. Merci de patienter '.RateLimiter::availableIn($rateLimitKey).' seconde(s) avant de reessayer.');

            return;
        }

        RateLimiter::hit($rateLimitKey, 60);

        $data = $this->validate([
            'customer_name' => ['required', 'string', 'min:2', 'max:120'],
            // Format volontairement souple : indicatifs, espaces et tirets
            // sont toleres, seul le nombre de chiffres est contraint.
            'customer_phone' => ['required', 'string', 'max:40', 'regex:/^\+?[\d\s().-]{6,30}$/'],
            'customer_address' => ['required', 'string', 'min:3', 'max:255'],
            'customer_note' => ['nullable', 'string', 'max:500'],
        ], [
            'customer_name.required' => 'Merci d\'indiquer votre nom.',
            'customer_phone.required' => 'Merci d\'indiquer votre numero de telephone.',
            'customer_phone.regex' => 'Ce numero de telephone ne semble pas valide.',
            'customer_address.required' => 'Merci d\'indiquer votre adresse de livraison.',
        ]);

        $data['customer_note'] = trim($data['customer_note'] ?? '') ?: null;

        // Leve une ValidationException sur la clef "cart" si le stock a bouge
        // entre l'affichage du panier et la validation.
        $order = $orders->place($data, request()->ip());

        session()->flash('success', 'Votre commande a bien ete enregistree.');

        $this->redirectRoute('checkout.confirmation', [
            'order' => $order->number,
            'token' => $order->public_token,
        ]);
    }

    public function render(CartService $cart, WhatsAppService $whatsapp): View
    {
        return view('livewire.shop.checkout', [
            'cart' => Cart::payload($cart->contents()),
            'whatsappConfigured' => $whatsapp->isConfigured(),
        ]);
    }
}
