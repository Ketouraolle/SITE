<?php

namespace App\Models;

use App\Services\Promotions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Pack extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'occasion_id',
        'name',
        'slug',
        'description',
        'image_path',
        'price',
        'is_published',
        'is_featured',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $pack) {
            if (blank($pack->slug)) {
                $pack->slug = static::uniqueSlug($pack->name, $pack->id);
            }
        });
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'pack';
        $slug = $base;
        $i = 2;

        while (static::withTrashed()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function items(): HasMany
    {
        return $this->hasMany(PackItem::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'pack_items')->withPivot('quantity');
    }

    public function occasion(): BelongsTo
    {
        return $this->belongsTo(Occasion::class);
    }

    public function offers(): BelongsToMany
    {
        return $this->belongsToMany(Offer::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /* --------------------------------------------------------------------- */
    /* Prix et disponibilite */
    /* --------------------------------------------------------------------- */

    /**
     * Somme des produits qui composent le pack, au prix du jour (offres en
     * cours comprises). La relation "items.product" doit etre chargee.
     */
    public function componentsTotal(): float
    {
        return (float) $this->items->sum(
            fn (PackItem $item) => $item->product ? $item->product->salePrice() * $item->quantity : 0
        );
    }

    public function activeOffer(): ?Offer
    {
        return $this->id ? app(Promotions::class)->packOffer($this->id) : null;
    }

    /**
     * Prix avant offre : le prix fixe s'il est defini, sinon la somme des
     * produits.
     */
    public function basePrice(?float $componentsTotal = null): float
    {
        return $this->price !== null
            ? (float) $this->price
            : round($componentsTotal ?? $this->componentsTotal(), 2);
    }

    /**
     * Prix facture : le prix de base, remise de l'offre en cours deduite.
     *
     * $componentsTotal permet de fournir une somme deja calculee (passage de
     * commande) sans dependre de la relation items.product.
     */
    public function effectivePrice(?float $componentsTotal = null): float
    {
        $base = $this->basePrice($componentsTotal);
        $offer = $this->activeOffer();

        return $offer ? $offer->apply($base) : $base;
    }

    /**
     * Economie realisee par rapport a l'achat des produits a l'unite.
     */
    public function savings(): float
    {
        return max(0, round($this->componentsTotal() - $this->effectivePrice(), 2));
    }

    /**
     * Nombre d'exemplaires du pack que le stock courant permet de servir.
     */
    public function availableQuantity(): int
    {
        if ($this->items->isEmpty()) {
            return 0;
        }

        $quantities = $this->items->map(function (PackItem $item) {
            if (! $item->product || ! $item->product->isOrderable() || $item->quantity < 1) {
                return 0;
            }

            return intdiv($item->product->stock, $item->quantity);
        });

        return (int) $quantities->min();
    }

    /**
     * Publie et, s'il est reserve a une periode, pendant celle-ci.
     */
    public function isVisible(): bool
    {
        return $this->is_published && app(Promotions::class)->isOccasionRunning($this->occasion_id);
    }

    public function isOrderable(): bool
    {
        return $this->isVisible() && $this->availableQuantity() > 0;
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * Ce que voit le client : publie, et dans sa periode s'il en a une.
     */
    public function scopeVisible(Builder $query): void
    {
        $running = app(Promotions::class)->runningOccasionIds();

        $query->published()->where(
            fn (Builder $q) => $q->whereNull('occasion_id')->orWhereIn('occasion_id', $running)
        );
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('name');
    }
}
