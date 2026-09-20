<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    public function index(): Response
    {
        $orders = $this->orders->paginateForCustomer(request()->user()->getKey());

        return Inertia::render('storefront/account/orders/index', [
            'orders' => OrderResource::collection($orders),
            'seo' => ['title' => 'Order history', 'description' => null],
        ]);
    }

    public function show(Order $order): Response
    {
        $this->authorize('view', $order);

        $order->load(['items', 'payments', 'refunds', 'statusEvents.user']);

        return Inertia::render('storefront/account/orders/show', [
            'order' => new OrderResource($order),
            'seo' => ['title' => "Order {$order->order_number}", 'description' => null],
        ]);
    }
}
