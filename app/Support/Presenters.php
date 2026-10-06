<?php

namespace App\Support;

use App\Models\Occasion;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Pack;
use App\Models\Product;
use Carbon\CarbonInterface;

/**
 * Mise en forme des modeles pour les vues Livewire.
 *
 * Centraliser la projection evite qu'une page expose par megarde un champ
 * interne (stock reel, jeton public, adresse IP) et garantit que la carte
 * produit a la meme forme partout.
 */
class Presenters
{
    /**
     * Badge de remise affiche sur les cartes ("-20 %", "Offre Noel").
     *
     * @return array<string, mixed>|null
     */
    public static function offerBadge(?Offer $offer): ?array
    {
        if (! $offer) {
            return null;
        }

        return [
            'name' => $offer->name,
            'percent' => $offer->discount_percent,
            'badge' => $offer->badge(),
            'ends_on_label' => self::dateLabel($offer->endsOn()),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function occasionTag(?Occasion $occasion): ?array
    {
        if (! $occasion) {
            return null;
        }

        return [
            'id' => $occasion->id,
            'name' => $occasion->name,
            'slug' => $occasion->slug,
            'status' => $occasion->status(),
            'period_label' => self::periodLabel($occasion->starts_on, $occasion->ends_on),
        ];
    }

    /**
     * Periode cote admin, avec ses compteurs.
     *
     * @return array<string, mixed>
     */
    public static function occasionAdmin(Occasion $occasion): array
    {
        return [
            ...self::occasionTag($occasion),
            'description' => $occasion->description,
            'starts_on' => $occasion->starts_on?->toDateString(),
            'ends_on' => $occasion->ends_on?->toDateString(),
            'is_active' => $occasion->is_active,
            'position' => $occasion->position,
            'products_count' => $occasion->products_count ?? 0,
            'packs_count' => $occasion->packs_count ?? 0,
            'offers_count' => $occasion->offers_count ?? 0,
        ];
    }

    /**
     * Offre cote admin. Relations occasion, products et packs chargees.
     *
     * @return array<string, mixed>
     */
    public static function offerAdmin(Offer $offer): array
    {
        $status = match (true) {
            ! $offer->is_active => 'inactive',
            $offer->isRunning() => 'running',
            $offer->startsOn() && $offer->startsOn()->isFuture() => 'upcoming',
            default => 'ended',
        };

        return [
            'id' => $offer->id,
            'name' => $offer->name,
            'description' => $offer->description,
            'discount_percent' => $offer->discount_percent,
            'badge' => $offer->badge(),
            'occasion_id' => $offer->occasion_id,
            'occasion' => self::occasionTag($offer->occasion),
            'starts_on' => $offer->starts_on?->toDateString(),
            'ends_on' => $offer->ends_on?->toDateString(),
            'period_label' => self::periodLabel($offer->startsOn(), $offer->endsOn()),
            'is_active' => $offer->is_active,
            'status' => $status,
            'product_ids' => $offer->products->pluck('id')->all(),
            'pack_ids' => $offer->packs->pluck('id')->all(),
            'products' => $offer->products->map(fn (Product $p) => $p->only('id', 'name'))->all(),
            'packs' => $offer->packs->map(fn (Pack $p) => $p->only('id', 'name'))->all(),
        ];
    }

    public static function periodLabel(?CarbonInterface $start, ?CarbonInterface $end): string
    {
        return match (true) {
            $start && $end => 'Du '.self::dateLabel($start).' au '.self::dateLabel($end),
            (bool) $start => 'A partir du '.self::dateLabel($start),
            (bool) $end => "Jusqu'au ".self::dateLabel($end),
            default => 'Sans limite de date',
        };
    }

    private static function dateLabel(?CarbonInterface $date): ?string
    {
        return $date?->translatedFormat('j F Y');
    }

    /**
     * Carte produit : grilles du catalogue, accueil, resultats de recherche.
     *
     * @return array<string, mixed>
     */
    public static function productCard(Product $product): array
    {
        $state = $product->stockState();
        $offer = $product->activeOffer();
        $price = $product->salePrice();

        // Pendant une offre, le prix barre est le prix habituel (ou l'ancien
        // prix barre s'il est plus eleve).
        $compareAt = $product->compare_at_price !== null ? (float) $product->compare_at_price : null;
        if ($offer) {
            $compareAt = max($compareAt ?? 0, (float) $product->price);
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'short_description' => $product->short_description,
            'price' => $price,
            'price_label' => Money::format($price),
            'compare_at_price' => $compareAt,
            'compare_at_price_label' => $compareAt !== null ? Money::format($compareAt) : null,
            'offer' => self::offerBadge($offer),
            'image' => $product->relationLoaded('primaryImage')
                ? $product->primaryImage?->url
                : $product->primaryImage()->first()?->url,
            'category' => $product->relationLoaded('category') && $product->category
                ? ['name' => $product->category->name, 'slug' => $product->category->slug]
                : null,
            'is_featured' => $product->is_featured,
            'stock_state' => $state->value,
            'stock_label' => $state->label(),
            'stock_color' => $state->color(),
            'orderable' => $product->isOrderable(),
            // Plafond de la quantite proposee au client. Le stock exact n'est
            // revele que lorsqu'il devient une contrainte.
            'max_quantity' => $product->isOrderable() ? $product->stock : 0,
        ];
    }

    /**
     * Fiche produit detaillee.
     *
     * @return array<string, mixed>
     */
    public static function productDetail(Product $product): array
    {
        return [
            ...self::productCard($product),
            'description' => $product->description,
            'sku' => $product->sku,
            'images' => $product->images->map(fn ($image) => [
                'id' => $image->id,
                'url' => $image->url,
                'alt' => $image->alt ?: $product->name,
            ])->all(),
        ];
    }

    /**
     * Vue admin d'un produit : le stock reel et les etats y sont exposes.
     *
     * @return array<string, mixed>
     */
    public static function productAdmin(Product $product): array
    {
        $state = $product->stockState();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'short_description' => $product->short_description,
            'description' => $product->description,
            'price' => (float) $product->price,
            'price_label' => Money::format($product->price),
            'compare_at_price' => $product->compare_at_price !== null ? (float) $product->compare_at_price : null,
            'stock' => $product->stock,
            'is_available' => $product->is_available,
            'is_published' => $product->is_published,
            'is_featured' => $product->is_featured,
            'category_id' => $product->category_id,
            'category' => $product->category?->only('id', 'name'),
            'occasion_id' => $product->occasion_id,
            'occasion' => self::occasionTag($product->occasion_id ? $product->occasion : null),
            'offer' => self::offerBadge($product->activeOffer()),
            'sale_price_label' => Money::format($product->salePrice()),
            'stock_state' => $state->value,
            'stock_label' => $state->label(),
            'stock_color' => $state->color(),
            'images' => $product->relationLoaded('images')
                ? $product->images->map(fn ($image) => [
                    'id' => $image->id,
                    'url' => $image->url,
                    'alt' => $image->alt,
                    'is_primary' => $image->is_primary,
                ])->all()
                : [],
            'image' => $product->relationLoaded('primaryImage') ? $product->primaryImage?->url : null,
            'updated_at' => $product->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Pack cote client. La relation items.product doit etre chargee.
     *
     * @return array<string, mixed>
     */
    public static function pack(Pack $pack, bool $withItems = true): array
    {
        $available = $pack->availableQuantity();
        $price = $pack->effectivePrice();
        $savings = $pack->savings();

        return [
            'id' => $pack->id,
            'name' => $pack->name,
            'slug' => $pack->slug,
            'description' => $pack->description,
            'image' => $pack->image_url,
            'price' => $price,
            'price_label' => Money::format($price),
            'components_total' => $pack->componentsTotal(),
            'components_total_label' => Money::format($pack->componentsTotal()),
            'savings' => $savings,
            'savings_label' => $savings > 0 ? Money::format($savings) : null,
            'offer' => self::offerBadge($pack->activeOffer()),
            'is_featured' => $pack->is_featured,
            'orderable' => $pack->isOrderable(),
            'max_quantity' => $available,
            'stock_label' => $available > 0 ? 'Disponible' : 'Indisponible',
            'stock_color' => $available > 0 ? 'emerald' : 'rose',
            'items' => $withItems
                ? $pack->items->filter(fn ($item) => $item->product !== null)->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'name' => $item->product->name,
                    'slug' => $item->product->slug,
                    'quantity' => $item->quantity,
                    'image' => $item->product->relationLoaded('primaryImage')
                        ? $item->product->primaryImage?->url
                        : null,
                    'unit_price_label' => Money::format($item->product->salePrice()),
                ])->values()->all()
                : [],
        ];
    }

    /**
     * Pack cote admin.
     *
     * @return array<string, mixed>
     */
    public static function packAdmin(Pack $pack): array
    {
        return [
            'id' => $pack->id,
            'name' => $pack->name,
            'slug' => $pack->slug,
            'description' => $pack->description,
            'image' => $pack->image_url,
            'price' => $pack->price !== null ? (float) $pack->price : null,
            'effective_price' => $pack->effectivePrice(),
            'effective_price_label' => Money::format($pack->effectivePrice()),
            'components_total_label' => Money::format($pack->componentsTotal()),
            'is_published' => $pack->is_published,
            'is_featured' => $pack->is_featured,
            'position' => $pack->position,
            'occasion_id' => $pack->occasion_id,
            'occasion' => self::occasionTag($pack->occasion_id ? $pack->occasion : null),
            'offer' => self::offerBadge($pack->activeOffer()),
            'available_quantity' => $pack->availableQuantity(),
            'items' => $pack->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'name' => $item->product?->name ?? 'Produit supprime',
                'stock' => $item->product?->stock ?? 0,
            ])->values()->all(),
        ];
    }

    /**
     * Ligne de la liste des commandes (dashboard).
     *
     * @return array<string, mixed>
     */
    public static function orderRow(Order $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_address' => $order->customer_address,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'status_color' => $order->status->color(),
            'total' => (float) $order->total,
            'total_label' => Money::format($order->total),
            'items_count' => $order->items_count,
            'created_at' => $order->created_at?->toIso8601String(),
            'created_at_label' => $order->created_at?->translatedFormat('d/m/Y H:i'),
        ];
    }

    /**
     * Commande complete : page de confirmation client et detail admin.
     *
     * @return array<string, mixed>
     */
    public static function orderDetail(Order $order): array
    {
        return [
            ...self::orderRow($order),
            'customer_note' => $order->customer_note,
            'currency' => $order->currency,
            'whatsapp_message' => $order->whatsapp_message,
            'confirmed_at_label' => $order->confirmed_at?->translatedFormat('d/m/Y H:i'),
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'type' => $item->type,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'unit_price_label' => Money::format($item->unit_price),
                'line_total_label' => Money::format($item->line_total),
                'components' => collect($item->components ?? [])->map(fn (array $c) => [
                    'name' => $c['name'] ?? '?',
                    'quantity' => $c['quantity'] ?? 1,
                ])->all(),
            ])->all(),
        ];
    }
}
