<?php

namespace App\Models;

use App\Support\CacheKeys;
use Database\Factories\BlogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Blog extends Model
{
    /** @use HasFactory<BlogFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'seo_title',
        'seo_description',
    ];

    protected static function booted(): void
    {
        static::saved(function (): void {
            CacheKeys::bump(CacheKeys::Blogs);
            CacheKeys::bump(CacheKeys::Navigation);
        });
        static::deleted(function (): void {
            CacheKeys::bump(CacheKeys::Blogs);
            CacheKeys::bump(CacheKeys::Navigation);
        });
    }

    /**
     * @return HasMany<BlogPost, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
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
