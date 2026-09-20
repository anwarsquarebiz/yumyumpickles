<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Versioned cache keys for storefront reads.
 *
 * The configured cache store is not guaranteed to support tagging, so instead of
 * flushing tags we keep a monotonically increasing version number per domain and
 * embed it in every key. Bumping the version orphans all previous entries, which
 * then expire naturally, giving tag-like invalidation on any driver.
 */
final class CacheKeys
{
    public const Products = 'products';

    public const Collections = 'collections';

    public const Pages = 'pages';

    public const Blogs = 'blogs';

    public const Settings = 'settings';

    public const ShippingMethods = 'shipping_methods';

    public const Banners = 'banners';

    public const Navigation = 'navigation';

    /**
     * All invalidatable domains, used by the cache:clear tooling and tests.
     *
     * @return array<int, string>
     */
    public static function domains(): array
    {
        return [
            self::Products,
            self::Collections,
            self::Pages,
            self::Blogs,
            self::Settings,
            self::ShippingMethods,
            self::Banners,
            self::Navigation,
        ];
    }

    public static function version(string $domain): int
    {
        return (int) Cache::get(self::versionKey($domain), 1);
    }

    /**
     * Invalidate every cached entry for the domain.
     */
    public static function bump(string $domain): void
    {
        Cache::forever(self::versionKey($domain), self::version($domain) + 1);
    }

    public static function make(string $domain, string $key): string
    {
        return sprintf('shop:%s:v%d:%s', $domain, self::version($domain), $key);
    }

    /**
     * Remember a value within a domain, honouring the shop.cache.enabled switch
     * so tests and local debugging can bypass the cache entirely.
     */
    public static function remember(string $domain, string $key, Closure $callback): mixed
    {
        if (! config('shop.cache.enabled', true)) {
            return $callback();
        }

        return Cache::remember(
            self::make($domain, $key),
            (int) config('shop.cache.ttl', 900),
            $callback,
        );
    }

    private static function versionKey(string $domain): string
    {
        return "shop:{$domain}:version";
    }
}
