<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Models\Concerns\SearchesCaseInsensitively;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory, SearchesCaseInsensitively;

    protected $fillable = [
        'number',
        'public_token',
        'customer_name',
        'customer_phone',
        'customer_address',
        'customer_note',
        'status',
        'total',
        'currency',
        'items_count',
        'whatsapp_message',
        'ip_address',
        'confirmed_at',
        'stock_released_at',
    ];

    protected $hidden = [
        'public_token',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total' => 'decimal:2',
            'items_count' => 'integer',
            'confirmed_at' => 'datetime',
            'stock_released_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Format lisible par le client : CMD-00125.
     */
    public static function formatNumber(int $id): string
    {
        return 'CMD-'.str_pad((string) $id, 5, '0', STR_PAD_LEFT);
    }

    public function scopeStatus(Builder $query, ?string $status): void
    {
        if ($status && OrderStatus::tryFrom($status)) {
            $query->where('status', $status);
        }
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
            $q->where('number', $operator, $like)
                ->orWhere('customer_name', $operator, $like)
                ->orWhere('customer_phone', $operator, $like)
                ->orWhere('customer_address', $operator, $like);
        });
    }
}
