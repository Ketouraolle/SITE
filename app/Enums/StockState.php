<?php

namespace App\Enums;

/**
 * Etat de disponibilite affiche au client. Il est toujours derive du couple
 * (is_available, stock) et n'est donc jamais stocke en base.
 */
enum StockState: string
{
    case InStock = 'in_stock';
    case LowStock = 'low_stock';
    case OutOfStock = 'out_of_stock';
    case Unavailable = 'unavailable';

    public function label(): string
    {
        return match ($this) {
            self::InStock => 'En stock',
            self::LowStock => 'Bientot epuise',
            self::OutOfStock => 'En rupture',
            self::Unavailable => 'Indisponible',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::InStock => 'emerald',
            self::LowStock => 'amber',
            self::OutOfStock => 'rose',
            self::Unavailable => 'slate',
        };
    }

    public function isOrderable(): bool
    {
        return $this === self::InStock || $this === self::LowStock;
    }

    public static function forProduct(bool $isAvailable, int $stock): self
    {
        if (! $isAvailable) {
            return self::Unavailable;
        }

        if ($stock <= 0) {
            return self::OutOfStock;
        }

        if ($stock <= config('shop.low_stock_threshold')) {
            return self::LowStock;
        }

        return self::InStock;
    }
}
