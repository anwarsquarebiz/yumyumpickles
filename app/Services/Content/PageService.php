<?php

namespace App\Services\Content;

use App\Models\Page;
use App\Support\RichText;
use Illuminate\Support\Str;

class PageService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Page
    {
        return Page::query()->create($this->attributes($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Page $page, array $data): Page
    {
        $page->update($this->attributes($data, $page));

        return $page->refresh();
    }

    public function delete(Page $page): void
    {
        $page->delete();
    }

    public function generateSlug(string $source, ?Page $ignore = null): string
    {
        $base = Str::slug($source) ?: 'page';
        $slug = $base;
        $suffix = 2;

        while ($this->slugTaken($slug, $ignore)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data, ?Page $existing = null): array
    {
        $attributes = [
            'title' => $data['title'],
            'excerpt' => $data['excerpt'] ?? null,
            'content' => RichText::sanitize($data['content'] ?? null),
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'published_at' => $data['published_at'] ?? null,
        ];

        if (array_key_exists('status', $data)) {
            $attributes['status'] = $data['status'];
        }

        if (array_key_exists('template', $data)) {
            $attributes['template'] = $data['template'];
        }

        $requestedSlug = $data['slug'] ?? null;

        if ($requestedSlug !== null && $requestedSlug !== '') {
            $attributes['slug'] = $this->generateSlug($requestedSlug, $existing);
        } elseif ($existing === null) {
            $attributes['slug'] = $this->generateSlug($data['title']);
        }

        return $attributes;
    }

    private function slugTaken(string $slug, ?Page $ignore): bool
    {
        return Page::query()
            ->where('slug', $slug)
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->exists();
    }
}
