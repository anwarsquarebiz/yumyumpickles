<?php

namespace App\Models;

use App\Enums\PublishStatus;
use Database\Factories\CollectionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Collection extends Model
{
    /** @use HasFactory<CollectionFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'image_disk',
        'image_path',
        'status',
        'seo_title',
        'seo_description',
        'position',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PublishStatus::class,
            'published_at' => 'datetime',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot('position')
            ->orderBy('collection_product.position');
    }

    /**
     * @param  Builder<Collection>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', PublishStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === PublishStatus::Published
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    public function imageUrl(): ?string
    {
        if ($this->image_path === null || $this->image_disk === null) {
            return null;
        }

        return Storage::disk($this->image_disk)->url($this->image_path);
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
