<?php

namespace App\Http\Resources\Concerns;

use App\Services\Currency\CurrencyConverter;
use App\Support\Money;

trait PresentsDisplayMoney
{
    protected function displayMoney(?Money $money): ?Money
    {
        if ($money === null) {
            return null;
        }

        return app(CurrencyConverter::class)->forDisplay($money);
    }
}
