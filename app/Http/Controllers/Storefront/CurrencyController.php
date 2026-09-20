<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\SwitchCurrencyRequest;
use App\Services\Currency\CurrencyService;
use Illuminate\Http\RedirectResponse;

class CurrencyController extends Controller
{
    public function __construct(private readonly CurrencyService $currencies) {}

    public function __invoke(SwitchCurrencyRequest $request): RedirectResponse
    {
        $this->currencies->choose($request->currency());

        return back();
    }
}
