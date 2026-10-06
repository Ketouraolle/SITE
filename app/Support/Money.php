<?php

namespace App\Support;

class Money
{
    /**
     * 25000 -> "25 000 FCFA". Les decimales ne sont affichees que si elles
     * portent une information.
     */
    public static function format(float|int|string|null $amount, bool $withCurrency = true): string
    {
        $amount = (float) ($amount ?? 0);
        $decimals = fmod($amount, 1.0) === 0.0 ? 0 : 2;

        $formatted = number_format($amount, $decimals, ',', "\u{202F}");

        return $withCurrency
            ? $formatted.' '.config('shop.currency')
            : $formatted;
    }
}
