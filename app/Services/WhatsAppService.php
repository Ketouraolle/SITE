<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Money;

/**
 * Generation du message de commande et du lien de redirection.
 *
 * Le driver par defaut est "click_to_chat" (wa.me), qui ne demande aucune
 * configuration cote Meta. Le jour ou la WhatsApp Business API est branchee,
 * il suffit d'implementer un driver "business_api" qui consomme le meme
 * message : la generation du texte reste ici et ne change pas.
 */
class WhatsAppService
{
    public function isConfigured(): bool
    {
        return $this->number() !== '';
    }

    public function number(): string
    {
        return (string) config('shop.whatsapp.number');
    }

    /**
     * Message prerempli envoye au vendeur.
     */
    public function messageFor(Order $order): string
    {
        $products = $order->items
            ->map(fn (OrderItem $item) => $this->describeItem($item))
            ->implode(', ');

        $lines = [
            "Bonjour, je viens de passer la commande #{$order->number}.",
            'Produits : '.($products !== '' ? $products : 'aucun'),
            'Total : '.Money::format($order->total),
            'Nom : '.$order->customer_name,
            'Telephone : '.$order->customer_phone,
            'Adresse : '.$order->customer_address,
        ];

        if (filled($order->customer_note)) {
            $lines[] = 'Note : '.$order->customer_note;
        }

        return implode("\n", $lines);
    }

    /**
     * Lien Click-to-Chat vers le vendeur, message prerempli.
     */
    public function chatUrl(Order $order): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $message = $order->whatsapp_message ?: $this->messageFor($order);

        return 'https://wa.me/'.$this->number().'?text='.rawurlencode($message);
    }

    /**
     * Lien de contact simple, sans commande (bouton d'aide du site).
     */
    public function contactUrl(?string $message = null): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $url = 'https://wa.me/'.$this->number();

        return $message ? $url.'?text='.rawurlencode($message) : $url;
    }

    private function describeItem(OrderItem $item): string
    {
        $label = $item->name;

        if ($item->isPack() && filled($item->components)) {
            $detail = collect($item->components)
                ->map(fn (array $c) => ($c['name'] ?? '?').' x'.($c['quantity'] ?? 1))
                ->implode(', ');

            $label .= " ({$detail})";
        }

        return $label.' x '.$item->quantity;
    }
}
