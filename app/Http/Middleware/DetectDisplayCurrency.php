<?php

namespace App\Http\Middleware;

use App\Services\Currency\CurrencyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectDisplayCurrency
{
    public function __construct(private readonly CurrencyService $currencies) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isAdmin($request)) {
            $this->currencies->useBase();

            return $next($request);
        }

        $this->currencies->resolve($request);

        return $next($request);
    }

    private function isAdmin(Request $request): bool
    {
        return $request->is('admin') || $request->is('admin/*');
    }
}
