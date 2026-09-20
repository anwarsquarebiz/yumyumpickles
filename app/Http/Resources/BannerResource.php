<?php

namespace App\Http\Resources;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Banner
 */
class BannerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'button_label' => $this->button_label,
            'button_url' => $this->button_url,
            'image_url' => $this->imageUrl(),
            'alt' => $this->alt,
            'position' => $this->position,
            'status' => $this->status->value,
            'published_at' => $this->published_at?->toDateTimeString(),
            'starts_at' => $this->starts_at?->toDateTimeString(),
            'ends_at' => $this->ends_at?->toDateTimeString(),
            'is_published' => $this->isPublished(),
        ];
    }
}
