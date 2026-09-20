<?php

namespace App\Models;

use App\Enums\PublishStatus;
use App\Support\CacheKeys;
use Database\Factories\BlogPostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BlogPost extends Model
{
    /** @use HasFactory<BlogPostFactory> */
    use HasFactory;

    protected $fillable = [
        'blog_id',
        'blog_category_id',
        'user_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image_disk',
        'featured_image_path',
        'status',
        'seo_title',
        'seo_description',
        'metadata',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PublishStatus::class,
            'metadata' => 'array',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => CacheKeys::bump(CacheKeys::Blogs));
        static::deleted(fn () => CacheKeys::bump(CacheKeys::Blogs));
    }

    /**
     * @return BelongsTo<Blog, $this>
     */
    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }

    /**
     * @return BelongsTo<BlogCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @param  Builder<BlogPost>  $query
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

    public function featuredImageUrl(): ?string
    {
        if ($this->featured_image_path === null || $this->featured_image_disk === null) {
            return null;
        }

        return Storage::disk($this->featured_image_disk)->url($this->featured_image_path);
    }

    public function metaTitle(): string
    {
        return $this->seo_title ?: $this->title;
    }

    public function metaDescription(): ?string
    {
        return $this->seo_description ?: $this->excerpt;
    }
}
