<?php

namespace App\Models;

use Database\Factories\ProductOptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOption extends Model
{
    /** @use HasFactory<ProductOptionFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'position',
        'values',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'values' => 'array',
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
     * The variant column this option's values are stored in.
     */
    public function variantColumn(): string
    {
        return 'option'.$this->position;
    }
}
