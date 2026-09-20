<?php

namespace App\Models;

use App\Enums\PageTemplate;
use App\Enums\PublishStatus;
use App\Support\CacheKeys;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'status',
        'template',
        'seo_title',
        'seo_description',
        'published_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'template' => PageTemplate::Default->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PublishStatus::class,
            'template' => PageTemplate::class,
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (): void {
            CacheKeys::bump(CacheKeys::Pages);
            CacheKeys::bump(CacheKeys::Navigation);
        });
        static::deleted(function (): void {
            CacheKeys::bump(CacheKeys::Pages);
            CacheKeys::bump(CacheKeys::Navigation);
        });
    }

    /**
     * @return HasMany<ContactMessage, $this>
     */
    public function contactMessages(): HasMany
    {
        return $this->hasMany(ContactMessage::class);
    }

    /**
     * @param  Builder<Page>  $query
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

    public function isContact(): bool
    {
        return $this->template === PageTemplate::Contact;
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
