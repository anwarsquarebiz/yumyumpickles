<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Support\Money;
use Database\Factories\CartItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    /** @use HasFactory<CartItemFactory> */
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_variant_id',
        'quantity',
        'unit_price_amount',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_amount' => MoneyCast::class,
        ];
    }

    /**
     * @return BelongsTo<Cart, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function unitPrice(): Money
    {
        return $this->unit_price_amount ?? Money::zero();
    }

    public function lineTotal(): Money
    {
        return $this->unitPrice()->multipliedBy($this->quantity);
    }
}
