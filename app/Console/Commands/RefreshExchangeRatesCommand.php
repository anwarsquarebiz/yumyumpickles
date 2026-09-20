<?php

namespace App\Console\Commands;

use App\Services\Currency\ExchangeRateService;
use Illuminate\Console\Command;

class RefreshExchangeRatesCommand extends Command
{
    protected $signature = 'currency:refresh-rates';

    protected $description = 'Fetch and cache USD-based exchange rates for storefront display conversion.';

    public function handle(ExchangeRateService $rates): int
    {
        $fetched = $rates->refresh();

        $this->info('Cached '.count($fetched).' exchange rates against '.$rates->base().'.');

        return self::SUCCESS;
    }
}
