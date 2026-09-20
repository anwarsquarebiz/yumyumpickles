<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\ShippingMethodType;
use App\Support\CacheKeys;
use Database\Factories\ShippingMethodFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    /** @use HasFactory<ShippingMethodFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'rate_amount',
        'free_over_amount',
        'is_active',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ShippingMethodType::class,
            'rate_amount' => MoneyCast::class,
            'free_over_amount' => MoneyCast::class,
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => CacheKeys::bump(CacheKeys::ShippingMethods));
        static::deleted(fn () => CacheKeys::bump(CacheKeys::ShippingMethods));
    }

    /**
     * @param  Builder<ShippingMethod>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('position')->orderBy('id');
    }
}
