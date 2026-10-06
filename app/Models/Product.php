<?php

namespace App\Models;

use App\Enums\StockState;
use App\Models\Concerns\SearchesCaseInsensitively;
use App\Services\Promotions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SearchesCaseInsensitively, SoftDeletes;

    protected $fillable = [
        'category_id',
        'occasion_id',
        'name',
        'slug',
        'sku',
        'short_description',
        'description',
        'price',
        'compare_at_price',
        'stock',
        'is_available',
        'is_published',
        'is_featured',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'stock' => 'integer',
            'is_available' => 'boolean',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $product) {
            if (blank($product->slug)) {
                $product->slug = static::uniqueSlug($product->name, $product->id);
            }

            if ($product->is_published && ! $product->published_at) {
                $product->published_at = now();
            }
        });
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'produit';
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function occasion(): BelongsTo
    {
        return $this->belongsTo(Occasion::class);
    }

    public function offers(): BelongsToMany
    {
        return $this->belongsToMany(Offer::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position')->orderBy('id');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->orderByDesc('is_primary')->orderBy('position')->orderBy('id');
    }

    public function packs(): BelongsToMany
    {
        return $this->belongsToMany(Pack::class, 'pack_items')->withPivot('quantity');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /* --------------------------------------------------------------------- */
    /* Etat */
    /* --------------------------------------------------------------------- */

    public function stockState(): StockState
    {
        return StockState::forProduct($this->is_available, $this->stock);
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
        return $this->isVisible() && $this->stockState()->isOrderable();
    }

    /* --------------------------------------------------------------------- */
    /* Prix */
    /* --------------------------------------------------------------------- */

    /**
     * Offre en cours la plus avantageuse pour ce produit.
     */
    public function activeOffer(): ?Offer
    {
        return $this->id ? app(Promotions::class)->productOffer($this->id) : null;
    }

    /**
     * Prix facture aujourd'hui : le prix de base, remise de l'offre en cours
     * deduite le cas echeant.
     */
    public function salePrice(): float
    {
        $offer = $this->activeOffer();

        return $offer ? $offer->apply((float) $this->price) : (float) $this->price;
    }

    /* --------------------------------------------------------------------- */
    /* Scopes */
    /* --------------------------------------------------------------------- */

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

    public function scopeOrderable(Builder $query): void
    {
        $query->visible()->where('is_available', true)->where('stock', '>', 0);
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $operator = static::likeOperator($query);
        $like = static::likeTerm($term);

        $query->where(function (Builder $q) use ($operator, $like) {
            $q->where('name', $operator, $like)
                ->orWhere('sku', $operator, $like)
                ->orWhere('short_description', $operator, $like)
                ->orWhere('description', $operator, $like);
        });
    }
}
