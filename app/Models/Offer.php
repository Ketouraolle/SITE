<?php

namespace App\Models;

use App\Services\Promotions;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Offre promotionnelle : remise en pourcentage sur des produits et des packs.
 *
 * Rattachee a une periode, l'offre en suit les dates et l'activation ; sinon
 * elle s'appuie sur ses propres dates (facultatives).
 */
class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'occasion_id',
        'name',
        'description',
        'discount_percent',
        'starts_on',
        'ends_on',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_percent' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => app(Promotions::class)->flush());
        static::deleted(fn () => app(Promotions::class)->flush());
    }

    public function occasion(): BelongsTo
    {
        return $this->belongsTo(Occasion::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function packs(): BelongsToMany
    {
        return $this->belongsToMany(Pack::class);
    }

    public function startsOn(): ?CarbonInterface
    {
        return $this->occasion_id ? $this->occasion?->starts_on : $this->starts_on;
    }

    public function endsOn(): ?CarbonInterface
    {
        return $this->occasion_id ? $this->occasion?->ends_on : $this->ends_on;
    }

    public function isRunning(?CarbonInterface $date = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->occasion_id) {
            return (bool) $this->occasion?->isRunning($date);
        }

        return Occasion::coversDate($this->starts_on, $this->ends_on, $date);
    }

    /**
     * Prix apres remise, arrondi a l'unite (le FCFA n'a pas de centimes).
     */
    public function apply(float $price): float
    {
        return max(0.0, round($price * (100 - $this->discount_percent) / 100));
    }

    public function badge(): string
    {
        return '-'.$this->discount_percent.' %';
    }

    /**
     * Remplace les produits et packs concernes par l'offre.
     *
     * @param  array<int, int>  $productIds
     * @param  array<int, int>  $packIds
     */
    public function syncTargets(array $productIds, array $packIds): void
    {
        $this->products()->sync($productIds);
        $this->packs()->sync($packIds);

        app(Promotions::class)->flush();
    }
}
