<?php

namespace App\Services\Storefront;

use App\Enums\NavigationLinkType;
use App\Models\Blog;
use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\Page;
use App\Support\CacheKeys;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Builds the storefront header and footer navigation.
 *
 * Shared on every request, so the lists are cached and invalidated by the
 * catalog and CMS services bumping their cache domains.
 */
class NavigationService
{
    /**
     * Header links from the admin menu, or the catalog fallback when empty.
     *
     * @return array<int, array{title: string, url: string, external: bool}>
     */
    public function header(): array
    {
        /** @var array<int, array{title: string, url: string, external: bool}> */
        return CacheKeys::remember(CacheKeys::Navigation, 'header', function (): array {
            $items = NavigationItem::query()->forMenu()->get();

            if ($items->isEmpty()) {
                return $this->defaultHeaderLinks();
            }

            $collections = Collection::query()
                ->published()
                ->whereIn('id', $items->where('type', NavigationLinkType::Collection)->pluck('resource_id'))
                ->get(['id', 'title', 'slug'])
                ->keyBy('id');

            $pages = Page::query()
                ->published()
                ->whereIn('id', $items->where('type', NavigationLinkType::Page)->pluck('resource_id'))
                ->get(['id', 'title', 'slug'])
                ->keyBy('id');

            $blogs = Blog::query()
                ->whereIn('id', $items->where('type', NavigationLinkType::Blog)->pluck('resource_id'))
                ->get(['id', 'title', 'slug'])
                ->keyBy('id');

            return $items
                ->map(fn (NavigationItem $item): ?array => $this->resolvedLink($item, $collections, $pages, $blogs))
                ->filter()
                ->values()
                ->all();
        });
    }

    /**
     * Published collections, for the footer.
     *
     * @return array<int, array{title: string, url: string}>
     */
    public function collections(int $limit = 8): array
    {
        /** @var array<int, array{title: string, url: string}> */
        return CacheKeys::remember(CacheKeys::Collections, "navigation:{$limit}", function () use ($limit): array {
            return Collection::query()
                ->published()
                ->orderBy('position')
                ->orderBy('title')
                ->limit($limit)
                ->get(['title', 'slug'])
                ->map(fn (Collection $collection): array => [
                    'title' => $collection->title,
                    'url' => route('collections.show', $collection->slug),
                ])
                ->all();
        });
    }

    /**
     * Published CMS pages, for the footer.
     *
     * @return array<int, array{title: string, url: string}>
     */
    public function pages(): array
    {
        /** @var array<int, array{title: string, url: string}> */
        return CacheKeys::remember(CacheKeys::Pages, 'navigation', function (): array {
            return Page::query()
                ->published()
                ->orderBy('title')
                ->get(['title', 'slug'])
                ->map(fn (Page $page): array => [
                    'title' => $page->title,
                    'url' => route('pages.show', $page->slug),
                ])
                ->all();
        });
    }

    /**
     * @return array<int, array{title: string, url: string, external: bool}>
     */
    private function defaultHeaderLinks(): array
    {
        $links = [
            ['title' => 'Shop', 'url' => route('shop'), 'external' => false],
        ];

        foreach (array_slice($this->collections(5), 0, 5) as $collection) {
            $links[] = [
                'title' => $collection['title'],
                'url' => $collection['url'],
                'external' => false,
            ];
        }

        $blog = Blog::query()->orderBy('id')->first(['title', 'slug']);

        $links[] = [
            'title' => $blog?->title ?? 'Recipes',
            'url' => $blog === null ? '/recipes' : route('blogs.show', $blog->slug),
            'external' => false,
        ];

        return $links;
    }

    /**
     * @param  SupportCollection<int|string, Collection>  $collections
     * @param  SupportCollection<int|string, Page>  $pages
     * @param  SupportCollection<int|string, Blog>  $blogs
     * @return array{title: string, url: string, external: bool}|null
     */
    private function resolvedLink(NavigationItem $item, SupportCollection $collections, SupportCollection $pages, SupportCollection $blogs): ?array
    {
        return match ($item->type) {
            NavigationLinkType::Home => $this->link($item->title, route('home')),
            NavigationLinkType::Catalog => $this->link($item->title, route('shop')),
            NavigationLinkType::Collection => $this->resourceLink($item->title, $collections->get($item->resource_id), 'collections.show'),
            NavigationLinkType::Page => $this->resourceLink($item->title, $pages->get($item->resource_id), 'pages.show'),
            NavigationLinkType::Blog => $this->blogLink($item->title, $blogs->get($item->resource_id)),
            NavigationLinkType::Url => $this->customLink($item->title, $item->url),
        };
    }

    /**
     * @return array{title: string, url: string, external: bool}
     */
    private function link(string $title, string $url, bool $external = false): array
    {
        return [
            'title' => $title,
            'url' => $url,
            'external' => $external,
        ];
    }

    /**
     * @return array{title: string, url: string, external: bool}|null
     */
    private function resourceLink(string $title, Collection|Page|null $resource, string $route): ?array
    {
        if ($resource === null) {
            return null;
        }

        if ($resource instanceof Page) {
            $pretty = ['our-story', 'faq', 'contact', 'shipping', 'refunds', 'privacy', 'terms'];

            $url = in_array($resource->slug, $pretty, true)
                ? url('/'.$resource->slug)
                : route('pages.show', $resource->slug);

            return $this->link($title, $url);
        }

        return $this->link($title, route($route, $resource->slug));
    }

    /**
     * @return array{title: string, url: string, external: bool}|null
     */
    private function blogLink(string $title, ?Blog $blog): ?array
    {
        if ($blog === null) {
            return null;
        }

        $url = $blog->slug === 'recipes'
            ? route('recipes')
            : route('blogs.show', $blog->slug);

        return $this->link($title, $url);
    }

    /**
     * @return array{title: string, url: string, external: bool}|null
     */
    private function customLink(string $title, ?string $url): ?array
    {
        if ($url === null || $url === '') {
            return null;
        }

        if (str_starts_with($url, '/')) {
            return str_starts_with($url, '//') ? null : $this->link($title, $url);
        }

        if (preg_match('/^https?:\/\//i', $url) === 1) {
            return $this->link($title, $url, true);
        }

        return null;
    }
}
