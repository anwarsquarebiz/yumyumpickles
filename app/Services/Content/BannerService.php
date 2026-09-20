<?php

namespace App\Services\Content;

use App\Models\Banner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * @phpstan-type BannerPayload array{
 *     title: string,
 *     subtitle?: string|null,
 *     button_label?: string|null,
 *     button_url?: string|null,
 *     alt?: string|null,
 *     position?: int,
 *     status?: string,
 *     published_at?: string|null,
 *     starts_at?: string|null,
 *     ends_at?: string|null,
 *     image?: UploadedFile|null,
 *     remove_image?: bool
 * }
 */
class BannerService
{
    /**
     * @param  BannerPayload  $data
     */
    public function create(array $data): Banner
    {
        return DB::transaction(function () use ($data): Banner {
            $banner = Banner::query()->create($this->attributes($data));
            $this->storeImage($banner, $data['image'] ?? null);

            return $banner->refresh();
        });
    }

    /**
     * @param  BannerPayload  $data
     */
    public function update(Banner $banner, array $data): Banner
    {
        DB::transaction(function () use ($banner, $data): void {
            $banner->update($this->attributes($data));

            if (($data['remove_image'] ?? false) === true) {
                $this->deleteImage($banner);
            }

            $this->storeImage($banner, $data['image'] ?? null);
        });

        return $banner->refresh();
    }

    public function delete(Banner $banner): void
    {
        DB::transaction(function () use ($banner): void {
            $this->deleteImage($banner);
            $banner->delete();
        });
    }

    /**
     * @param  BannerPayload  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        $attributes = [
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'button_label' => $data['button_label'] ?? null,
            'button_url' => $data['button_url'] ?? null,
            'alt' => $data['alt'] ?? null,
            'position' => $data['position'] ?? 0,
            'published_at' => $data['published_at'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ];

        if (array_key_exists('status', $data)) {
            $attributes['status'] = $data['status'];
        }

        return $attributes;
    }

    private function storeImage(Banner $banner, ?UploadedFile $image): void
    {
        if ($image === null) {
            return;
        }

        $this->deleteImage($banner);

        $disk = (string) config('shop.catalog.image_disk', 'public');

        $banner->forceFill([
            'image_disk' => $disk,
            'image_path' => $image->store('banners', $disk),
        ])->save();
    }

    private function deleteImage(Banner $banner): void
    {
        if ($banner->image_path === null || $banner->image_disk === null) {
            return;
        }

        Storage::disk($banner->image_disk)->delete($banner->image_path);

        $banner->forceFill(['image_disk' => null, 'image_path' => null])->save();
    }
}
