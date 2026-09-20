<?php

namespace App\Services\Currency;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Maps a visitor IP to a country, then to a display currency.
 *
 * CDN country headers are preferred so we do not call a geo service on every
 * first visit. Public IPs fall back to a cached HTTP lookup.
 */
class GeoCurrencyDetector
{
    public function currencyFor(Request $request): ?string
    {
        if (! config('shop.currency.detect_from_ip', true)) {
            return null;
        }

        $country = $this->countryCode($request);

        if ($country === null) {
            return null;
        }

        $mapped = config('currencies.country_map.'.$country);

        return is_string($mapped) ? strtoupper($mapped) : null;
    }

    public function countryCode(Request $request): ?string
    {
        $fromHeader = $this->countryFromHeaders($request);

        if ($fromHeader !== null) {
            return $fromHeader;
        }

        $ip = $request->ip();

        if ($ip === null || ! $this->isPublicIp($ip)) {
            return null;
        }

        $cached = Cache::get('shop:geo-country:'.$ip);

        if (is_string($cached) && strlen($cached) === 2) {
            return $cached;
        }

        $country = $this->lookupCountry($ip);

        if ($country !== null) {
            Cache::put('shop:geo-country:'.$ip, $country, 86400);
        }

        return $country;
    }

    private function countryFromHeaders(Request $request): ?string
    {
        foreach (['CF-IPCountry', 'CloudFront-Viewer-Country'] as $header) {
            $value = $request->headers->get($header);

            if (! is_string($value)) {
                continue;
            }

            $code = strtoupper(trim($value));

            if (strlen($code) === 2 && $code !== 'XX' && $code !== 'T1') {
                return $code;
            }
        }

        return null;
    }

    private function lookupCountry(string $ip): ?string
    {
        $template = (string) config('shop.currency.geo_url', '');

        if ($template === '') {
            return null;
        }

        $url = str_replace('{ip}', urlencode($ip), $template);

        try {
            $response = Http::retry([100, 500])
                ->timeout(3)
                ->connectTimeout(2)
                ->acceptJson()
                ->get($url, ['fields' => 'success,country_code'])
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            Log::notice('Currency geo lookup failed.', ['ip' => $ip, 'message' => $exception->getMessage()]);

            return null;
        } catch (Throwable $exception) {
            Log::notice('Currency geo lookup failed.', ['ip' => $ip, 'message' => $exception->getMessage()]);

            return null;
        }

        $country = $response->json('country_code');

        if (! is_string($country) || strlen($country) !== 2) {
            return null;
        }

        return strtoupper($country);
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
}
