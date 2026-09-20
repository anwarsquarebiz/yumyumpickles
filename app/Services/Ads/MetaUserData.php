<?php

namespace App\Services\Ads;

/**
 * Normalise and hash identifiers the way Meta's Conversions API expects.
 */
final class MetaUserData
{
    public static function hashEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $normalized = strtolower(trim($email));

        if ($normalized === '') {
            return null;
        }

        return hash('sha256', $normalized);
    }

    public static function hashPhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        return hash('sha256', $digits);
    }
}
