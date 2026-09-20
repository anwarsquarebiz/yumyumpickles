<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $paidStatuses = [
            OrderStatus::Paid,
            OrderStatus::Processing,
            OrderStatus::Shipped,
            OrderStatus::Delivered,
        ];

        $revenue = (int) Order::query()->whereIn('status', $paidStatuses)->sum('grand_total_amount');
        $todayRevenue = (int) Order::query()
            ->whereIn('status', $paidStatuses)
            ->whereDate('placed_at', today())
            ->sum('grand_total_amount');

        $recent = Order::query()
            ->with('user')
            ->latest('id')
            ->limit(8)
            ->get();

        return Inertia::render('admin/dashboard', [
            'metrics' => [
                'orders' => Order::query()->count(),
                'open_orders' => Order::query()->whereIn('status', [
                    OrderStatus::Pending,
                    OrderStatus::Paid,
                    OrderStatus::Processing,
                    OrderStatus::Shipped,
                ])->count(),
                'customers' => User::query()->customers()->count(),
                'products' => Product::query()->count(),
                'revenue' => Money::fromMinor($revenue)->toArray(),
                'today_revenue' => Money::fromMinor($todayRevenue)->toArray(),
            ],
            'recent_orders' => OrderResource::collection($recent),
        ]);
    }
}
