<?php

namespace App\Http\Requests\Admin\Coupon;

use App\Models\Coupon;

class StoreCouponRequest extends CouponFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Coupon::class) ?? false;
    }

    protected function coupon(): ?Coupon
    {
        return null;
    }
}
