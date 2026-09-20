<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\AddressResource;
use App\Http\Resources\OrderResource;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $search = $request->string('search')->trim()->value();

        $customers = User::query()
            ->customers()
            ->withCount('orders')
            ->when($search !== '', function ($query) use ($search): void {
                $like = "%{$search}%";
                $query->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->latest('id')
            ->paginate((int) config('shop.catalog.admin_per_page', 20))
            ->withQueryString();

        return Inertia::render('admin/customers/index', [
            'customers' => $customers,
            'filters' => ['search' => $search ?: null],
        ]);
    }

    public function show(User $customer): Response
    {
        abort_unless(request()->user()?->isAdmin(), 403);
        abort_unless($customer->role === UserRole::Customer, 404);

        $customer->load(['addresses', 'orders' => fn ($query) => $query->with('items')->latest('id')->limit(20)]);

        return Inertia::render('admin/customers/show', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'created_at' => $customer->created_at?->toDateTimeString(),
                'orders_count' => $customer->orders()->count(),
            ],
            'addresses' => AddressResource::collection($customer->addresses),
            'orders' => OrderResource::collection($customer->orders),
        ]);
    }
}
