<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Support\Money;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_title',
        'variant_title',
        'sku',
        'unit_price_amount',
        'quantity',
        'subtotal_amount',
        'discount_amount',
        'total_amount',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_amount' => MoneyCast::class,
            'subtotal_amount' => MoneyCast::class,
            'discount_amount' => MoneyCast::class,
            'total_amount' => MoneyCast::class,
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function lineTotal(): Money
    {
        return $this->total_amount ?? Money::zero();
    }

    public function displayVariantTitle(): ?string
    {
        $title = trim((string) $this->variant_title);

        if ($title === '' || in_array($title, ['Default', 'Default Title'], true)) {
            return null;
        }

        return $title;
    }

    public function imageUrl(): ?string
    {
        $image = $this->variant?->images->first() ?? $this->product?->images->first();

        if ($image === null) {
            return null;
        }

        $url = $image->url();

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return url($url);
    }
}
