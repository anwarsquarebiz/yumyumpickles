<?php

namespace App\Services\Settings;

use App\Models\Setting;
use App\Support\CacheKeys;
use Illuminate\Support\Collection;

/**
 * Read and write store settings.
 *
 * Values are wrapped in an array on the way into the JSON column so scalars,
 * lists and maps can all round-trip through the same schema. The whole table is
 * cached as one entry because it is small and read on nearly every request.
 */
class SettingsService
{
    /**
     * Store-wide defaults, used when a key has never been written.
     *
     * @var array<string, mixed>
     */
    public const Defaults = [
        'store.name' => 'YumYum Pickles',
        'store.email' => 'hello@yumyumhomemadepickles.com',
        'store.phone' => '+91 99999 99999',
        'store.address' => '',
        'store.currency' => 'INR',
        'store.tagline' => 'The Taste of Tradition',
        'store.whatsapp' => '919999999999',
        'store.youtube' => 'https://youtube.com/@yumyumpickles',
        'checkout.tax_rate_basis_points' => 0,
        'checkout.guest_checkout_enabled' => true,
        'checkout.terms_required' => true,
        'social.facebook' => 'https://facebook.com/yumyumpickles',
        'social.instagram' => 'https://instagram.com/yumyumpickles',
        'social.twitter' => '',
        'social.youtube' => 'https://youtube.com/@yumyumpickles',
        'ads.meta.enabled' => false,
        'ads.meta.pixel_id' => '',
        'ads.meta.access_token' => '',
        'ads.meta.test_event_code' => '',
        'ads.meta.advanced_matching' => true,
        'ads.google.enabled' => false,
        'ads.google.measurement_id' => '',
        'ads.gtm.enabled' => false,
        'ads.gtm.container_id' => '',
        'seo.default_title' => 'YumYum Pickles — The Taste of Tradition',
        'seo.default_description' => 'Homemade Goan and coastal pickles, packed in glass and delivered across India.',
        'storefront.announcements' => [],
        'storefront.story_steps' => [],
        'storefront.why_choose' => [],
        'storefront.videos' => [],
        'storefront.testimonials' => [],
        'storefront.instagram' => [],
        'storefront.faqs' => [],
        'storefront.social_proof' => [],
        'storefront.policies' => [],
    ];

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        if ($all->has($key)) {
            return $all->get($key);
        }

        return $default ?? self::Defaults[$key] ?? null;
    }

    public function set(string $key, mixed $value, string $group = 'general'): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => ['data' => $value], 'group' => $group],
        );

        $this->flush();
    }

    /**
     * Persist many settings at once, flushing the cache only after all writes.
     *
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values, string $group = 'general'): void
    {
        foreach ($values as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => ['data' => $value], 'group' => $group],
            );
        }

        $this->flush();
    }

    /**
     * Every stored setting merged over the defaults.
     *
     * @return Collection<string, mixed>
     */
    public function all(): Collection
    {
        /** @var array<string, mixed> $stored */
        $stored = CacheKeys::remember(CacheKeys::Settings, 'all', function (): array {
            return Setting::query()
                ->get(['key', 'value'])
                ->mapWithKeys(fn (Setting $setting): array => [
                    $setting->key => $setting->value['data'] ?? null,
                ])
                ->all();
        });

        return collect(self::Defaults)->merge($stored);
    }

    /**
     * @return Collection<string, mixed>
     */
    public function group(string $prefix): Collection
    {
        return $this->all()->filter(
            fn (mixed $value, string $key): bool => str_starts_with($key, $prefix.'.')
        );
    }

    public function flush(): void
    {
        CacheKeys::bump(CacheKeys::Settings);
    }
}
