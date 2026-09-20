<?php

namespace App\Repositories\Contracts;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginateForAdmin(array $filters): LengthAwarePaginator;

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginateForCustomer(int $userId): LengthAwarePaginator;
}
