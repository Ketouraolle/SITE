<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Pack;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly CartService $cart,
        private readonly WhatsAppService $whatsapp,
    ) {}

    /**
     * Enregistre la commande a partir du panier en session.
     *
     * Tout est fait dans une transaction, avec un verrou sur les lignes
     * produits concernees : deux clients qui valident le dernier article en
     * meme temps ne peuvent pas passer tous les deux.
     *
     * @param  array{customer_name:string, customer_phone:string, customer_address:string, customer_note?:?string}  $customer
     *
     * @throws ValidationException
     */
    public function place(array $customer, ?string $ipAddress = null): Order
    {
        $raw = $this->cart->lines();

        if ($raw === []) {
            throw ValidationException::withMessages([
                'cart' => 'Votre panier est vide.',
            ]);
        }

        return DB::transaction(function () use ($raw, $customer, $ipAddress) {
            [$items, $required] = $this->buildItems($raw);

            if ($items === []) {
                throw ValidationException::withMessages([
                    'cart' => 'Votre panier est vide.',
                ]);
            }

            $this->assertStockAvailable($required);

            $total = round(array_sum(array_column($items, 'line_total')), 2);

            $order = Order::create([
                'public_token' => Str::random(48),
                'customer_name' => $customer['customer_name'],
                'customer_phone' => $customer['customer_phone'],
                'customer_address' => $customer['customer_address'],
                'customer_note' => $customer['customer_note'] ?? null,
                'status' => OrderStatus::Pending,
                'total' => $total,
                'currency' => config('shop.currency'),
                'items_count' => array_sum(array_column($items, 'quantity')),
                'ip_address' => $ipAddress,
            ]);

            // Le numero lisible derive de la clef primaire : unique par
            // construction, sans compteur concurrent a gerer.
            $order->forceFill(['number' => Order::formatNumber($order->id)])->save();

            $order->items()->createMany($items);

            foreach ($required as $productId => $quantity) {
                Product::whereKey($productId)->decrement('stock', $quantity);
            }

            $order->load('items');
            $order->forceFill(['whatsapp_message' => $this->whatsapp->messageFor($order)])->save();

            $this->cart->clear();

            return $order;
        });
    }

    /**
     * Change le statut d'une commande et ajuste le stock en consequence :
     * une annulation rend les articles, un retour depuis "annulee" les reprend.
     *
     * @throws ValidationException
     */
    public function changeStatus(Order $order, OrderStatus $status): Order
    {
        return DB::transaction(function () use ($order, $status) {
            $order = Order::whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status === $status) {
                return $order;
            }

            $wasReleased = $order->stock_released_at !== null;
            $shouldRelease = $status->releasesStock();

            if ($shouldRelease && ! $wasReleased) {
                $this->adjustStock($order, +1);
                $order->stock_released_at = now();
            }

            if (! $shouldRelease && $wasReleased) {
                $this->adjustStock($order, -1);
                $order->stock_released_at = null;
            }

            $order->status = $status;

            if ($status === OrderStatus::Confirmed && ! $order->confirmed_at) {
                $order->confirmed_at = now();
            }

            $order->save();

            return $order;
        });
    }

    /* --------------------------------------------------------------------- */
    /* Construction des lignes */
    /* --------------------------------------------------------------------- */

    /**
     * Transforme les lignes de session en lignes de commande et calcule le
     * besoin total par produit.
     *
     * @param  array<string, array<string, mixed>>  $raw
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, int>}
     *
     * @throws ValidationException
     */
    private function buildItems(array $raw): array
    {
        $packIds = [];
        foreach ($raw as $line) {
            if ($line['type'] === 'pack' && $line['pack_id']) {
                $packIds[] = (int) $line['pack_id'];
            }
        }

        $packs = $packIds === []
            ? collect()
            : Pack::with('items')->whereIn('id', array_unique($packIds))->get()->keyBy('id');

        // Tous les produits touches, directement ou via un pack.
        $productIds = [];
        foreach ($raw as $line) {
            if ($line['type'] === 'product' && $line['product_id']) {
                $productIds[] = (int) $line['product_id'];
            }

            foreach ($line['components'] ?? [] as $component) {
                $productIds[] = (int) $component['product_id'];
            }
        }
        foreach ($packs as $pack) {
            foreach ($pack->items as $item) {
                $productIds[] = (int) $item->product_id;
            }
        }

        $products = $productIds === []
            ? collect()
            : Product::whereIn('id', array_unique($productIds))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

        $items = [];
        $required = [];
        $errors = [];

        foreach ($raw as $line) {
            $quantity = max(1, (int) $line['quantity']);

            if ($line['type'] === 'product') {
                /** @var Product|null $product */
                $product = $products->get((int) $line['product_id']);

                if (! $product || ! $product->isVisible()) {
                    continue;
                }

                if (! $product->is_available) {
                    $errors[] = "\"{$product->name}\" n'est plus disponible.";

                    continue;
                }

                $items[] = [
                    'type' => 'product',
                    'product_id' => $product->id,
                    'pack_id' => null,
                    'name' => $product->name,
                    'unit_price' => $product->salePrice(),
                    'quantity' => $quantity,
                    'line_total' => round($product->salePrice() * $quantity, 2),
                    'components' => null,
                ];

                $required[$product->id] = ($required[$product->id] ?? 0) + $quantity;

                continue;
            }

            if ($line['type'] === 'pack') {
                /** @var Pack|null $pack */
                $pack = $packs->get((int) $line['pack_id']);

                if (! $pack || ! $pack->isVisible()) {
                    continue;
                }

                $components = [];
                $componentsTotal = 0.0;
                $blocked = false;

                foreach ($pack->items as $packItem) {
                    /** @var Product|null $product */
                    $product = $products->get((int) $packItem->product_id);

                    if (! $product || ! $product->isVisible() || ! $product->is_available) {
                        $errors[] = "Le pack \"{$pack->name}\" contient un produit indisponible.";
                        $blocked = true;

                        break;
                    }

                    $units = max(1, (int) $packItem->quantity);

                    $components[] = [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'quantity' => $units,
                        'unit_price' => $product->salePrice(),
                    ];

                    $componentsTotal += $product->salePrice() * $units;
                    $required[$product->id] = ($required[$product->id] ?? 0) + ($units * $quantity);
                }

                if ($blocked || $components === []) {
                    continue;
                }

                $unitPrice = $pack->effectivePrice($componentsTotal);

                $items[] = [
                    'type' => 'pack',
                    'product_id' => null,
                    'pack_id' => $pack->id,
                    'name' => $pack->name,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'line_total' => round($unitPrice * $quantity, 2),
                    'components' => $components,
                ];

                continue;
            }

            if ($line['type'] === 'custom_pack') {
                $components = [];
                $unitPrice = 0.0;
                $blocked = false;

                foreach ($line['components'] ?? [] as $component) {
                    /** @var Product|null $product */
                    $product = $products->get((int) $component['product_id']);

                    if (! $product || ! $product->isVisible() || ! $product->is_available) {
                        $errors[] = 'Votre pack personnalise contient un produit indisponible.';
                        $blocked = true;

                        break;
                    }

                    $units = max(1, (int) $component['quantity']);

                    $components[] = [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'quantity' => $units,
                        'unit_price' => $product->salePrice(),
                    ];

                    $unitPrice += $product->salePrice() * $units;
                    $required[$product->id] = ($required[$product->id] ?? 0) + ($units * $quantity);
                }

                if ($blocked || $components === []) {
                    continue;
                }

                $unitPrice = round($unitPrice, 2);

                $items[] = [
                    'type' => 'custom_pack',
                    'product_id' => null,
                    'pack_id' => null,
                    'name' => $line['label'] ?: 'Pack personnalise',
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'line_total' => round($unitPrice * $quantity, 2),
                    'components' => $components,
                ];
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'cart' => array_values(array_unique($errors)),
            ]);
        }

        return [$items, $required];
    }

    /**
     * @param  array<int, int>  $required
     *
     * @throws ValidationException
     */
    private function assertStockAvailable(array $required): void
    {
        if ($required === []) {
            return;
        }

        $products = Product::whereIn('id', array_keys($required))->get()->keyBy('id');
        $errors = [];

        foreach ($required as $productId => $quantity) {
            /** @var Product|null $product */
            $product = $products->get($productId);

            if (! $product) {
                $errors[] = 'Un produit de votre panier a ete retire du catalogue.';

                continue;
            }

            if ($product->stock < $quantity) {
                $errors[] = $product->stock > 0
                    ? "Stock insuffisant pour \"{$product->name}\" : {$product->stock} restant(s) pour {$quantity} demande(s)."
                    : "\"{$product->name}\" est en rupture de stock.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'cart' => array_values(array_unique($errors)),
            ]);
        }
    }

    /**
     * Rend ($sign = +1) ou reprend ($sign = -1) le stock d'une commande.
     */
    private function adjustStock(Order $order, int $sign): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            if ($item->type === 'product') {
                if ($item->product_id) {
                    $this->shiftStock($item->product_id, $sign * $item->quantity);
                }

                continue;
            }

            foreach ($item->components ?? [] as $component) {
                if (empty($component['product_id'])) {
                    continue;
                }

                $units = max(1, (int) ($component['quantity'] ?? 1));
                $this->shiftStock((int) $component['product_id'], $sign * $units * $item->quantity);
            }
        }
    }

    private function shiftStock(int $productId, int $delta): void
    {
        if ($delta === 0) {
            return;
        }

        $product = Product::whereKey($productId)
            ->lockForUpdate()
            ->first();

        if (! $product) {
            if ($delta < 0) {
                throw ValidationException::withMessages([
                    'status' => 'Impossible de reactiver cette commande : un produit a ete retire du catalogue.',
                ]);
            }

            return;
        }

        $newStock = $product->stock + $delta;

        if ($newStock < 0) {
            throw ValidationException::withMessages([
                'status' => "Stock insuffisant pour \"{$product->name}\" : {$product->stock} disponible(s), ".abs($delta).' necessaire(s).',
            ]);
        }

        $product->stock = $newStock;
        $product->save();
    }
}
