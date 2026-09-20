<?php

namespace App\Services\Currency;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

/**
 * The visitor's display currency. Catalog prices stay in the store's base
 * currency (USD); this only decides how those amounts are presented.
 */
class CurrencyService
{
    private string $current;

    public function __construct(private readonly GeoCurrencyDetector $geo)
    {
        $this->current = $this->base();
    }

    public function base(): string
    {
        return strtoupper((string) config('shop.currency.code', 'USD'));
    }

    public function current(): string
    {
        return $this->current;
    }

    public function isBase(): bool
    {
        return $this->current === $this->base();
    }

    public function isSupported(string $code): bool
    {
        return array_key_exists(strtoupper($code), $this->supported());
    }

    /**
     * @return array<string, string>
     */
    public function supported(): array
    {
        /** @var array<string, string> $supported */
        $supported = config('shop.currency.supported', ['USD' => 'US Dollar']);

        return $supported;
    }

    /**
     * @return array<int, array{code: string, name: string, symbol: string}>
     */
    public function options(): array
    {
        $symbols = config('shop.currency.symbols', []);

        return collect($this->supported())
            ->map(fn (string $name, string $code): array => [
                'code' => $code,
                'name' => $name,
                'symbol' => $symbols[$code] ?? $code,
            ])
            ->values()
            ->all();
    }

    /**
     * Force the base currency, used on the admin surface so editors never see
     * converted catalog amounts.
     */
    public function useBase(): void
    {
        $this->current = $this->base();
    }

    /**
     * Apply a display currency for this request without persisting it.
     */
    public function setCurrent(string $code): void
    {
        $this->current = $this->normalize($code) ?? $this->base();
    }

    /**
     * Persist an explicit choice from the header switcher.
     */
    public function choose(string $code): void
    {
        $this->setCurrent($code);

        session()->put($this->sessionKey(), $this->current);
        Cookie::queue($this->preferenceCookie($this->current));
    }

    /**
     * Resolve the display currency for this request: explicit session or cookie
     * first, then IP country, then the base currency.
     */
    public function resolve(Request $request): void
    {
        $fromSession = $this->normalize((string) $request->session()->get($this->sessionKey(), ''));

        if ($fromSession !== null) {
            $this->current = $fromSession;

            return;
        }

        $fromCookie = $this->normalize((string) $request->cookie($this->cookieName(), ''));

        if ($fromCookie !== null) {
            $this->current = $fromCookie;
            $request->session()->put($this->sessionKey(), $fromCookie);

            return;
        }

        $detected = $this->normalize($this->geo->currencyFor($request) ?? '');
        $this->current = $detected ?? $this->base();

        $request->session()->put($this->sessionKey(), $this->current);
        Cookie::queue($this->preferenceCookie($this->current));
    }

    public function symbol(?string $code = null): string
    {
        $code ??= $this->current;
        $symbols = config('shop.currency.symbols', []);

        return $symbols[$code] ?? $code;
    }

    private function normalize(string $code): ?string
    {
        $code = strtoupper(trim($code));

        if ($code === '' || ! $this->isSupported($code)) {
            return null;
        }

        return $code;
    }

    private function sessionKey(): string
    {
        return (string) config('shop.currency.session_key', 'display_currency');
    }

    private function cookieName(): string
    {
        return (string) config('shop.currency.cookie', 'currency');
    }

    private function preferenceCookie(string $code): SymfonyCookie
    {
        return cookie(
            $this->cookieName(),
            $code,
            (int) config('shop.currency.cookie_minutes', 525600),
        );
    }
}
