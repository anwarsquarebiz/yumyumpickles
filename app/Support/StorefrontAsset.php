<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

final class StorefrontAsset
{
    /**
     * Resolve a seeded filename to a public storage URL.
     */
    public static function url(?string $filename): ?string
    {
        if ($filename === null || $filename === '') {
            return null;
        }

        if (str_starts_with($filename, 'http://') || str_starts_with($filename, 'https://') || str_starts_with($filename, '/')) {
            return $filename;
        }

        foreach (['products', 'banners', 'collections', 'recipes', 'content'] as $folder) {
            $path = $folder.'/'.$filename;

            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->url($path);
            }
        }

        return Storage::disk('public')->url('products/'.$filename);
    }
}
