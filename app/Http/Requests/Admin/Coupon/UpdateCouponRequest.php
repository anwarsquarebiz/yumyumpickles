<?php

namespace App\Http\Requests\Admin\Coupon;

use App\Models\Coupon;

class UpdateCouponRequest extends CouponFormRequest
{
    public function authorize(): bool
    {
        $coupon = $this->coupon();

        return $coupon !== null && ($this->user()?->can('update', $coupon) ?? false);
    }

    protected function coupon(): ?Coupon
    {
        $coupon = $this->route('coupon');

        return $coupon instanceof Coupon ? $coupon : null;
    }
}
