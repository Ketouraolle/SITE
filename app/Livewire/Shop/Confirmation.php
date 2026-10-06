<?php

namespace App\Livewire\Shop;

use App\Models\Order;
use App\Services\WhatsAppService;
use App\Support\Presenters;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Page de confirmation. Le jeton est la seule clef d'acces : sans lui, une
 * commande n'est pas consultable depuis son numero.
 */
class Confirmation extends Component
{
    #[Locked]
    public Order $order;

    public function mount(Order $order, string $token): void
    {
        abort_unless(hash_equals((string) $order->public_token, $token), 404);

        $this->order = $order;
    }

    public function render(WhatsAppService $whatsapp): View
    {
        $this->order->loadMissing('items');

        return view('livewire.shop.confirmation', [
            'details' => Presenters::orderDetail($this->order),
            'whatsappUrl' => $whatsapp->chatUrl($this->order),
            'shopPhone' => config('shop.phone'),
        ])->title("Commande {$this->order->number}");
    }
}
