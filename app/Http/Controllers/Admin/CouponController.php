<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CouponType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Coupon\StoreCouponRequest;
use App\Http\Requests\Admin\Coupon\UpdateCouponRequest;
use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use App\Services\Cart\CouponService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CouponController extends Controller
{
    public function __construct(private readonly CouponService $coupons) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Coupon::class);

        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->trim()->value();

        $coupons = Coupon::query()
            ->when($search !== '', fn ($query) => $query->where('code', 'like', '%'.mb_strtoupper($search).'%'))
            ->when($status === 'active', fn ($query) => $query->redeemable())
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest('id')
            ->paginate((int) config('shop.catalog.admin_per_page', 20))
            ->withQueryString();

        return Inertia::render('admin/coupons/index', [
            'coupons' => CouponResource::collection($coupons),
            'filters' => [
                'search' => $search ?: null,
                'status' => $status ?: null,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Coupon::class);

        return Inertia::render('admin/coupons/create', [
            'types' => CouponType::options(),
        ]);
    }

    public function store(StoreCouponRequest $request): RedirectResponse
    {
        $coupon = $this->coupons->create($request->validated());

        return to_route('admin.coupons.edit', $coupon)
            ->with('success', "Discount code {$coupon->code} was created.");
    }

    public function edit(Coupon $coupon): Response
    {
        $this->authorize('update', $coupon);

        return Inertia::render('admin/coupons/edit', [
            'coupon' => new CouponResource($coupon),
            'types' => CouponType::options(),
        ]);
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $this->coupons->update($coupon, $request->validated());

        return back()->with('success', 'Discount code saved.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $this->authorize('delete', $coupon);

        $code = $coupon->code;

        $this->coupons->delete($coupon);

        return to_route('admin.coupons.index')
            ->with('success', "Discount code {$code} was deleted.");
    }
}
