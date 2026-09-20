<?php

namespace App\Repositories;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderRepository implements OrderRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginateForAdmin(array $filters): LengthAwarePaginator
    {
        return Order::query()
            ->with(['user', 'latestPayment'])
            ->withCount('items')
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate((int) config('shop.catalog.admin_per_page', 20))
            ->withQueryString();
    }

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginateForCustomer(int $userId): LengthAwarePaginator
    {
        return Order::query()
            ->with(['items', 'latestPayment'])
            ->where('user_id', $userId)
            ->latest('id')
            ->paginate(10)
            ->withQueryString();
    }
}
