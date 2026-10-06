<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait SearchesCaseInsensitively
{
    /**
     * Operateur de comparaison insensible a la casse selon le moteur :
     * PostgreSQL a besoin de ILIKE, MySQL et SQLite traitent deja LIKE comme
     * insensible a la casse avec leurs collations par defaut.
     */
    protected static function likeOperator(Builder $query): string
    {
        return $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    /**
     * Echappe les jokers SQL pour qu'une recherche sur « 100% » ne devienne
     * pas un joker.
     */
    protected static function likeTerm(string $term): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term).'%';
    }
}
