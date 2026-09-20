<?php

namespace App\Models;

use App\Enums\PublishStatus;
use App\Support\CacheKeys;
use Database\Factories\BannerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    /** @use HasFactory<BannerFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'button_label',
        'button_url',
        'image_disk',
        'image_path',
        'alt',
        'position',
        'status',
        'published_at',
        'starts_at',
        'ends_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PublishStatus::class,
            'position' => 'integer',
            'published_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => CacheKeys::bump(CacheKeys::Banners));
        static::deleted(fn () => CacheKeys::bump(CacheKeys::Banners));
    }

    /**
     * @param  Builder<Banner>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', PublishStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Published banners that have an image and are inside their optional window.
     *
     * @param  Builder<Banner>  $query
     */
    public function scopeLive(Builder $query): void
    {
        $now = now();

        $query->published()
            ->whereNotNull('image_path')
            ->where(function (Builder $window) use ($now): void {
                $window->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $window) use ($now): void {
                $window->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->orderBy('position')
            ->orderBy('id');
    }

    public function isPublished(): bool
    {
        return $this->status === PublishStatus::Published
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function imageUrl(): ?string
    {
        if ($this->image_path === null || $this->image_disk === null) {
            return null;
        }

        return Storage::disk($this->image_disk)->url($this->image_path);
    }
}
