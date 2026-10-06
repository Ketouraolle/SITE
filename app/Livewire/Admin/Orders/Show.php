<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\WhatsAppService;
use App\Support\Presenters;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Detail d'une commande et changement de statut. Le jeton public et l'adresse
 * IP ne sont jamais transmis a la vue : seule la projection orderDetail l'est.
 */
#[Layout('layouts.admin')]
class Show extends Component
{
    #[Locked]
    public Order $order;

    public function mount(Order $order): void
    {
        $this->order = $order;
    }

    public function updateStatus(string $status, OrderService $orders): void
    {
        $this->resetErrorBag();

        Validator::make(['status' => $status], [
            'status' => ['required', Rule::enum(OrderStatus::class)],
        ])->validate();

        $this->order = $orders->changeStatus($this->order, OrderStatus::from($status));

        $this->dispatch('toast', type: 'success', message: 'Statut de la commande mis a jour.');
    }

    public function render(WhatsAppService $whatsapp): View
    {
        $this->order->load('items');

        return view('livewire.admin.orders.show', [
            'detail' => Presenters::orderDetail($this->order),
            'statuses' => OrderStatus::options(),
            'whatsappUrl' => $whatsapp->chatUrl($this->order),
            'customerWhatsappUrl' => $this->customerWhatsappUrl(),
        ])->title('Commande '.$this->order->number);
    }

    /**
     * Lien WhatsApp vers le client, pour le rappeler depuis le dashboard.
     */
    private function customerWhatsappUrl(): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $this->order->customer_phone);

        if (strlen((string) $digits) < 8) {
            return null;
        }

        $message = "Bonjour {$this->order->customer_name}, nous avons bien recu votre commande #{$this->order->number}.";

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($message);
    }
}
