<?php

namespace App\Services\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderNumberGenerator
{
    public function next(): string
    {
        $prefix = (string) config('shop.orders.number_prefix', '#');
        $start = (int) config('shop.orders.number_start', 1001);

        return DB::transaction(function () use ($prefix, $start): string {
            $last = Order::query()
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('order_number');

            $sequence = $start;

            if (is_string($last) && preg_match('/(\d+)$/', $last, $matches) === 1) {
                $sequence = max($start, ((int) $matches[1]) + 1);
            }

            return $prefix.$sequence;
        });
    }
}
