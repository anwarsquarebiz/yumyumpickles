<?php

namespace App\Services\Storefront;

use App\Models\Banner;
use App\Models\Blog;
use App\Models\Collection;
use App\Services\Settings\SettingsService;
use App\Support\StorefrontAsset;

class StorefrontContentService
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * @return array<string, mixed>
     */
    public function bundle(): array
    {
        return [
            'brand' => [
                'name' => $this->settings->get('store.name', 'YumYum Pickles'),
                'tagline' => $this->settings->get('store.tagline', 'The Taste of Tradition'),
                'phone' => $this->settings->get('store.phone', ''),
                'whatsapp' => $this->settings->get('store.whatsapp', ''),
                'email' => $this->settings->get('store.email', ''),
                'instagram' => $this->settings->get('social.instagram', ''),
                'facebook' => $this->settings->get('social.facebook', ''),
                'youtube' => $this->settings->get('social.youtube', $this->settings->get('store.youtube', '')),
            ],
            'announcements' => $this->settings->get('storefront.announcements', []),
            'storySteps' => $this->withImages($this->settings->get('storefront.story_steps', [])),
            'whyChoose' => $this->settings->get('storefront.why_choose', []),
            'videos' => $this->withImages($this->settings->get('storefront.videos', [])),
            'testimonials' => $this->settings->get('storefront.testimonials', []),
            'instagramPosts' => $this->withImages($this->settings->get('storefront.instagram', [])),
            'faqs' => $this->settings->get('storefront.faqs', []),
            'socialProofEvents' => $this->settings->get('storefront.social_proof', []),
            'policies' => $this->settings->get('storefront.policies', []),
            'recipes' => $this->recipes(),
            'categories' => Collection::query()
                ->published()
                ->orderBy('position')
                ->get()
                ->map(fn (Collection $collection): array => [
                    'name' => $collection->title,
                    'slug' => $collection->slug,
                    'label' => $collection->title,
                    'tagline' => $collection->description,
                    'image' => $collection->imageUrl(),
                    'position' => 'center',
                ])
                ->all(),
            'banners' => Banner::query()
                ->live()
                ->get()
                ->map(fn (Banner $banner): array => [
                    'title' => $banner->title,
                    'subtitle' => $banner->subtitle,
                    'label' => $banner->button_label,
                    'url' => $banner->button_url,
                    'image' => $banner->imageUrl(),
                    'alt' => $banner->alt ?? $banner->title,
                ])
                ->all(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function withImages(array $items): array
    {
        return array_map(function (array $item): array {
            if (isset($item['image'])) {
                $item['image'] = StorefrontAsset::url((string) $item['image']);
            }

            return $item;
        }, $items);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recipes(): array
    {
        $blog = Blog::query()->where('slug', 'recipes')->first();

        if ($blog === null) {
            return [];
        }

        return $blog->posts()
            ->published()
            ->orderBy('id')
            ->get()
            ->map(fn ($post): array => [
                'id' => $post->slug,
                'name' => $post->title,
                'time' => $post->metadata['time'] ?? '',
                'difficulty' => $post->metadata['difficulty'] ?? 'Easy',
                'pickle' => $post->metadata['pickle'] ?? '',
                'excerpt' => $post->excerpt,
                'image' => $post->featuredImageUrl(),
                'position' => 'center',
                'steps' => $post->metadata['steps'] ?? [],
            ])
            ->all();
    }
}
