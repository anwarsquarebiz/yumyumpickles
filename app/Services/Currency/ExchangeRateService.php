<?php

namespace App\Services\Currency;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * USD-based exchange rates for storefront display conversion.
 *
 * Live rates are fetched from a public endpoint and cached. When fetching is
 * disabled (tests) or the remote call fails, configured fallback rates are used
 * so prices still render.
 */
class ExchangeRateService
{
    /** @var array<string, float>|null */
    private ?array $forcedRates = null;

    public function base(): string
    {
        return strtoupper((string) config('shop.currency.code', 'USD'));
    }

    /**
     * Units of $currency per 1 unit of the base currency.
     */
    public function rate(string $currency): float
    {
        $currency = strtoupper($currency);

        if ($currency === $this->base()) {
            return 1.0;
        }

        $rate = $this->rates()[$currency] ?? $this->fallbackRates()[$currency] ?? 1.0;

        return $rate > 0 ? (float) $rate : 1.0;
    }

    /**
     * @return array<string, float>
     */
    public function rates(): array
    {
        if ($this->forcedRates !== null) {
            return $this->forcedRates;
        }

        $cached = Cache::get($this->cacheKey());

        if (is_array($cached) && $cached !== []) {
            return $this->normalizeRates($cached);
        }

        if (config('shop.currency.fetch_rates', true)) {
            $fetched = $this->fetchRates();

            if ($fetched !== []) {
                Cache::put($this->cacheKey(), $fetched, $this->ttl());
                Cache::forever($this->staleKey(), $fetched);

                return $fetched;
            }
        }

        $stale = Cache::get($this->staleKey());

        if (is_array($stale) && $stale !== []) {
            return $this->normalizeRates($stale);
        }

        return $this->fallbackRates();
    }

    /**
     * Replace cached rates with a fresh fetch. Used by the scheduled command.
     *
     * @return array<string, float>
     */
    public function refresh(): array
    {
        $this->forcedRates = null;

        $fetched = $this->fetchRates();

        if ($fetched === []) {
            return $this->rates();
        }

        Cache::put($this->cacheKey(), $fetched, $this->ttl());
        Cache::forever($this->staleKey(), $fetched);

        return $fetched;
    }

    /**
     * Pin rates for the rest of the request, used by tests.
     *
     * @param  array<string, float|int|string>  $rates
     */
    public function useRates(array $rates): void
    {
        $this->forcedRates = $this->normalizeRates($rates);
    }

    /**
     * @return array<string, float>
     */
    private function fetchRates(): array
    {
        $url = (string) config('shop.currency.rates_url');

        if ($url === '') {
            return [];
        }

        try {
            $response = Http::retry([100, 500])
                ->timeout(5)
                ->connectTimeout(3)
                ->acceptJson()
                ->get($url)
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('Exchange rate fetch failed.', ['message' => $exception->getMessage()]);

            return [];
        } catch (Throwable $exception) {
            Log::warning('Exchange rate fetch failed.', ['message' => $exception->getMessage()]);

            return [];
        }

        $payload = $response->json();
        $rates = is_array($payload) ? ($payload['rates'] ?? []) : [];

        if (! is_array($rates) || $rates === []) {
            return [];
        }

        return $this->normalizeRates($rates);
    }

    /**
     * @param  array<string, mixed>  $rates
     * @return array<string, float>
     */
    private function normalizeRates(array $rates): array
    {
        $normalized = [$this->base() => 1.0];

        foreach ($rates as $code => $rate) {
            if (! is_numeric($rate)) {
                continue;
            }

            $value = (float) $rate;

            if ($value <= 0) {
                continue;
            }

            $normalized[strtoupper((string) $code)] = $value;
        }

        return $normalized;
    }

    /**
     * @return array<string, float>
     */
    private function fallbackRates(): array
    {
        /** @var array<string, float|int|string> $rates */
        $rates = config('shop.currency.fallback_rates', ['USD' => 1]);

        return $this->normalizeRates($rates);
    }

    private function cacheKey(): string
    {
        return 'shop:exchange-rates:'.$this->base();
    }

    private function staleKey(): string
    {
        return 'shop:exchange-rates:last:'.$this->base();
    }

    private function ttl(): int
    {
        return (int) config('shop.currency.rates_ttl', 43200);
    }
}
