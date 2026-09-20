<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Exceptions\RefundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Order\StoreRefundRequest;
use App\Http\Requests\Admin\Order\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\Orders\OrderService;
use App\Services\Orders\RefundService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly OrderService $orderService,
        private readonly RefundService $refunds,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Order::class);

        $filters = [
            'search' => $request->string('search')->trim()->value() ?: null,
            'status' => $request->string('status')->trim()->value() ?: null,
        ];

        return Inertia::render('admin/orders/index', [
            'orders' => OrderResource::collection($this->orders->paginateForAdmin($filters)),
            'filters' => $filters,
            'statuses' => OrderStatus::options(),
        ]);
    }

    public function show(Order $order): Response
    {
        $this->authorize('view', $order);

        $order->load(['items', 'payments', 'refunds.user', 'statusEvents.user', 'user']);

        return Inertia::render('admin/orders/show', [
            'order' => new OrderResource($order),
        ]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        try {
            $this->orderService->transition(
                $order,
                OrderStatus::from($request->validated('status')),
                $request->user(),
                $request->validated('note'),
            );
        } catch (InvalidOrderTransitionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Order status updated.');
    }

    public function refund(StoreRefundRequest $request, Order $order): RedirectResponse
    {
        try {
            $this->refunds->issue(
                order: $order,
                amount: Money::fromDecimal($request->validated('amount')),
                actor: $request->user(),
                reason: $request->validated('reason'),
                restock: $request->boolean('restock'),
            );
        } catch (RefundException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Refund issued.');
    }
}
