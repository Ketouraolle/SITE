<?php

namespace App\Models;

use App\Services\Promotions;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Periode commerciale (Noel, Saint-Valentin, Aid...).
 *
 * Une periode « en cours » est active et la date du jour est comprise entre
 * ses bornes, chacune etant facultative.
 */
class Occasion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'starts_on',
        'ends_on',
        'is_active',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $occasion) {
            if (blank($occasion->slug)) {
                $occasion->slug = static::uniqueSlug($occasion->name, $occasion->id);
            }
        });

        static::saved(fn () => app(Promotions::class)->flush());
        static::deleted(fn () => app(Promotions::class)->flush());
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'periode';
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function packs(): HasMany
    {
        return $this->hasMany(Pack::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function isRunning(?CarbonInterface $date = null): bool
    {
        return $this->is_active && static::coversDate($this->starts_on, $this->ends_on, $date);
    }

    /**
     * Etat affiche dans le dashboard : running, upcoming, ended ou inactive.
     */
    public function status(?CarbonInterface $date = null): string
    {
        $today = ($date ?? now())->copy()->startOfDay();

        return match (true) {
            ! $this->is_active => 'inactive',
            $this->starts_on && $this->starts_on->gt($today) => 'upcoming',
            $this->ends_on && $this->ends_on->lt($today) => 'ended',
            default => 'running',
        };
    }

    public static function coversDate(?CarbonInterface $start, ?CarbonInterface $end, ?CarbonInterface $date = null): bool
    {
        $today = ($date ?? now())->copy()->startOfDay();

        return (! $start || $start->copy()->startOfDay()->lte($today))
            && (! $end || $end->copy()->startOfDay()->gte($today));
    }

    public function scopeRunning(Builder $query): void
    {
        $today = now()->toDateString();

        $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_on')->orWhereDate('starts_on', '<=', $today))
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $today));
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('starts_on')->orderBy('name');
    }
}
