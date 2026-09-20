<?php

namespace App\Services\Settings;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Store logo and favicon assets configured in admin settings.
 *
 * Paths and disks are persisted through SettingsService so they share the
 * same cache and merge cleanly with other store configuration.
 */
class BrandingService
{
    private const LogoDiskKey = 'brand.logo_disk';

    private const LogoPathKey = 'brand.logo_path';

    private const FaviconDiskKey = 'brand.favicon_disk';

    private const FaviconPathKey = 'brand.favicon_path';

    public function __construct(private readonly SettingsService $settings) {}

    public function logoUrl(): ?string
    {
        return $this->assetUrl(self::LogoDiskKey, self::LogoPathKey);
    }

    public function faviconUrl(): ?string
    {
        return $this->assetUrl(self::FaviconDiskKey, self::FaviconPathKey);
    }

    /**
     * @param  array{logo?: UploadedFile|null, remove_logo?: bool, favicon?: UploadedFile|null, remove_favicon?: bool}  $data
     */
    public function update(array $data): void
    {
        if (($data['remove_logo'] ?? false) === true) {
            $this->deleteAsset(self::LogoDiskKey, self::LogoPathKey);
        }

        if (($data['remove_favicon'] ?? false) === true) {
            $this->deleteAsset(self::FaviconDiskKey, self::FaviconPathKey);
        }

        if (($data['logo'] ?? null) instanceof UploadedFile) {
            $this->storeAsset(self::LogoDiskKey, self::LogoPathKey, $data['logo'], 'brand');
        }

        if (($data['favicon'] ?? null) instanceof UploadedFile) {
            $this->storeAsset(self::FaviconDiskKey, self::FaviconPathKey, $data['favicon'], 'brand');
        }
    }

    private function assetUrl(string $diskKey, string $pathKey): ?string
    {
        $disk = $this->settings->get($diskKey);
        $path = $this->settings->get($pathKey);

        if (! is_string($disk) || ! is_string($path) || $path === '') {
            return null;
        }

        /** @var FilesystemAdapter $filesystem */
        $filesystem = Storage::disk($disk);

        return $filesystem->url($path);
    }

    private function storeAsset(string $diskKey, string $pathKey, UploadedFile $file, string $directory): void
    {
        $this->deleteAsset($diskKey, $pathKey);

        $disk = (string) config('shop.catalog.image_disk', 'public');

        $this->settings->setMany([
            $diskKey => $disk,
            $pathKey => $file->store($directory, $disk),
        ], 'brand');
    }

    private function deleteAsset(string $diskKey, string $pathKey): void
    {
        $disk = $this->settings->get($diskKey);
        $path = $this->settings->get($pathKey);

        if (is_string($disk) && is_string($path) && $path !== '') {
            Storage::disk($disk)->delete($path);
        }

        $this->settings->setMany([
            $diskKey => null,
            $pathKey => null,
        ], 'brand');
    }
}
