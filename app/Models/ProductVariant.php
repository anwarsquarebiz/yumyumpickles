<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\InventoryPolicy;
use App\Support\Money;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'title',
        'sku',
        'barcode',
        'price_amount',
        'compare_at_price_amount',
        'cost_amount',
        'option1',
        'option2',
        'option3',
        'inventory_quantity',
        'track_inventory',
        'inventory_policy',
        'weight',
        'weight_unit',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_amount' => MoneyCast::class,
            'compare_at_price_amount' => MoneyCast::class,
            'cost_amount' => MoneyCast::class,
            'inventory_quantity' => 'integer',
            'track_inventory' => 'boolean',
            'inventory_policy' => InventoryPolicy::class,
            'weight' => 'decimal:3',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * @return HasMany<InventoryMovement, $this>
     */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function price(): Money
    {
        return $this->price_amount ?? Money::zero();
    }

    /**
     * Whether the variant can be sold at all right now.
     */
    public function isInStock(): bool
    {
        if (! $this->track_inventory) {
            return true;
        }

        if ($this->inventory_policy->allowsOversell()) {
            return true;
        }

        return $this->inventory_quantity > 0;
    }

    /**
     * Whether the requested quantity can be fulfilled.
     */
    public function canFulfill(int $quantity): bool
    {
        if ($quantity < 1) {
            return false;
        }

        if (! $this->track_inventory || $this->inventory_policy->allowsOversell()) {
            return true;
        }

        return $this->inventory_quantity >= $quantity;
    }

    /**
     * The most a customer may put in their cart, bounded by the per-line cap.
     */
    public function purchasableQuantity(): int
    {
        $cap = (int) config('shop.cart.max_quantity_per_line', 99);

        if (! $this->track_inventory || $this->inventory_policy->allowsOversell()) {
            return $cap;
        }

        return max(0, min($cap, $this->inventory_quantity));
    }

    public function isOnSale(): bool
    {
        return $this->compare_at_price_amount !== null
            && $this->compare_at_price_amount->greaterThan($this->price());
    }

    public function isLowStock(): bool
    {
        return $this->track_inventory
            && $this->inventory_quantity > 0
            && $this->inventory_quantity <= (int) config('shop.inventory.low_stock_threshold', 5);
    }

    /**
     * The chosen option values, for example ["500mg", "60 tablets"].
     *
     * @return array<int, string>
     */
    public function optionValues(): array
    {
        return array_values(array_filter([$this->option1, $this->option2, $this->option3]));
    }

    public function displayTitle(): string
    {
        $values = $this->optionValues();

        return $values === [] ? $this->title : implode(' / ', $values);
    }

    /**
     * @param  Builder<ProductVariant>  $query
     */
    public function scopeInStock(Builder $query): void
    {
        $query->where(function (Builder $query): void {
            $query->where('track_inventory', false)
                ->orWhere('inventory_policy', InventoryPolicy::Continue)
                ->orWhere('inventory_quantity', '>', 0);
        });
    }
}
