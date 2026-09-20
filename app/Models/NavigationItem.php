<?php

namespace App\Models;

use App\Enums\NavigationLinkType;
use App\Support\CacheKeys;
use Database\Factories\NavigationItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NavigationItem extends Model
{
    /** @use HasFactory<NavigationItemFactory> */
    use HasFactory;

    public const MenuHeader = 'header';

    protected $fillable = [
        'menu',
        'title',
        'type',
        'resource_id',
        'url',
        'position',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'menu' => self::MenuHeader,
        'position' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => NavigationLinkType::class,
            'resource_id' => 'integer',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => CacheKeys::bump(CacheKeys::Navigation));
        static::deleted(fn () => CacheKeys::bump(CacheKeys::Navigation));
    }

    /**
     * @param  Builder<NavigationItem>  $query
     */
    public function scopeForMenu(Builder $query, string $menu = self::MenuHeader): void
    {
        $query->where('menu', $menu)->orderBy('position')->orderBy('id');
    }
}
