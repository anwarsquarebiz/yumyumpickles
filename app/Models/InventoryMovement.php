<?php

namespace App\Models;

use App\Enums\InventoryMovementReason;
use Database\Factories\InventoryMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * An append-only record of a single stock change.
 *
 * Rows are never updated or deleted; a correction is expressed as a new movement
 * so the history always explains the current balance.
 */
class InventoryMovement extends Model
{
    /** @use HasFactory<InventoryMovementFactory> */
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'quantity_delta',
        'quantity_after',
        'reason',
        'reference_type',
        'reference_id',
        'user_id',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason' => InventoryMovementReason::class,
            'quantity_delta' => 'integer',
            'quantity_after' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The order, refund or other record that caused the movement.
     *
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function isIncrease(): bool
    {
        return $this->quantity_delta > 0;
    }
}
