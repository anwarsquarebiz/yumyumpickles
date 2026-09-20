<?php

namespace App\Http\Resources;

use App\Enums\NavigationLinkType;
use App\Models\Blog;
use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin NavigationItem
 */
class NavigationItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'resource_id' => $this->resource_id,
            'url' => $this->url,
            'position' => $this->position,
            'destination' => $this->destinationPreview(),
        ];
    }

    private function destinationPreview(): string
    {
        return match ($this->type) {
            NavigationLinkType::Home => 'Home',
            NavigationLinkType::Catalog => 'All products',
            NavigationLinkType::Collection => Collection::query()->find($this->resource_id)?->title ?? 'Missing collection',
            NavigationLinkType::Page => Page::query()->find($this->resource_id)?->title ?? 'Missing page',
            NavigationLinkType::Blog => Blog::query()->find($this->resource_id)?->title ?? 'Missing blog',
            NavigationLinkType::Url => $this->url ?? 'Custom URL',
        };
    }
}
