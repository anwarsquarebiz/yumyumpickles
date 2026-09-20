<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ShippingMethodType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Shipping\ShippingMethodFormRequest;
use App\Http\Resources\ShippingMethodResource;
use App\Models\ShippingMethod;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ShippingMethodController extends Controller
{
    public function index(): Response
    {
        abort_unless(request()->user()?->isAdmin(), 403);

        return Inertia::render('admin/shipping-methods/index', [
            'shipping_methods' => ShippingMethodResource::collection(
                ShippingMethod::query()->orderBy('position')->orderBy('id')->get()
            ),
            'shipping_types' => ShippingMethodType::options(),
        ]);
    }

    public function store(ShippingMethodFormRequest $request): RedirectResponse
    {
        ShippingMethod::query()->create($this->shippingAttributes($request->validated()));

        return back()->with('success', 'Shipping method added.');
    }

    public function update(ShippingMethodFormRequest $request, ShippingMethod $method): RedirectResponse
    {
        $method->update($this->shippingAttributes($request->validated()));

        return back()->with('success', 'Shipping method saved.');
    }

    public function destroy(ShippingMethod $method): RedirectResponse
    {
        abort_unless(request()->user()?->isAdmin(), 403);

        $method->delete();

        return back()->with('success', 'Shipping method removed.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function shippingAttributes(array $data): array
    {
        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'rate_amount' => Money::fromDecimal($data['rate']),
            'free_over_amount' => ($data['free_over'] ?? null) === null || $data['free_over'] === ''
                ? null
                : Money::fromDecimal($data['free_over']),
            'is_active' => $data['is_active'] ?? true,
            'position' => $data['position'] ?? 0,
        ];
    }
}
