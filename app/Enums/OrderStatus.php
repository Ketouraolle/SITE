<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Nouvelle',
            self::Confirmed => 'Confirmee',
            self::Processing => 'En preparation',
            self::Delivered => 'Livree',
            self::Cancelled => 'Annulee',
        };
    }

    /**
     * Palette Tailwind utilisee par les badges du dashboard.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Confirmed => 'blue',
            self::Processing => 'indigo',
            self::Delivered => 'emerald',
            self::Cancelled => 'rose',
        };
    }

    /**
     * Une commande annulee rend le stock reserve.
     */
    public function releasesStock(): bool
    {
        return $this === self::Cancelled;
    }

    /**
     * @return array<int, array{value: string, label: string, color: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $status) => [
            'value' => $status->value,
            'label' => $status->label(),
            'color' => $status->color(),
        ], self::cases());
    }
}
