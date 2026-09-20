<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'body_html',
        'status',
        'vendor',
        'product_type',
        'seo_title',
        'seo_description',
        'metadata',
        'published_at',
        'position',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'position' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'metadata' => 'array',
            'published_at' => 'datetime',
            'position' => 'integer',
        ];
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position');
    }

    /**
     * @return HasMany<ProductOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('position');
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    /**
     * @return BelongsToMany<Collection, $this>
     */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class)
            ->withPivot('position')
            ->orderBy('collection_product.position');
    }

    /**
     * @return HasMany<ProductReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    /**
     * Visible on the storefront: active and published at a past timestamp.
     *
     * @param  Builder<Product>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ProductStatus::Active)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        if ($term === null || trim($term) === '') {
            return;
        }

        $like = '%'.trim($term).'%';

        $query->where(function (Builder $query) use ($like): void {
            $query->where('title', 'like', $like)
                ->orWhere('vendor', 'like', $like)
                ->orWhere('product_type', 'like', $like)
                ->orWhereHas('variants', fn (Builder $variants) => $variants->where('sku', 'like', $like));
        });
    }

    public function isPublished(): bool
    {
        return $this->status === ProductStatus::Active
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    /**
     * The variant a customer gets when they add the product without choosing
     * options. Requires the variants relation to be loaded.
     */
    public function defaultVariant(): ?ProductVariant
    {
        return $this->variants->first();
    }

    /**
     * Total stock across every variant that tracks inventory.
     */
    public function totalInventory(): int
    {
        return $this->variants
            ->where('track_inventory', true)
            ->sum('inventory_quantity');
    }

    public function isInStock(): bool
    {
        return $this->variants->contains(fn (ProductVariant $variant): bool => $variant->isInStock());
    }

    public function metaTitle(): string
    {
        return $this->seo_title ?: $this->title;
    }

    public function metaDescription(): ?string
    {
        return $this->seo_description ?: $this->description;
    }
}
