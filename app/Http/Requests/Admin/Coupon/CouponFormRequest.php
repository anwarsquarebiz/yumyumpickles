<?php

namespace App\Http\Requests\Admin\Coupon;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class CouponFormRequest extends FormRequest
{
    abstract protected function coupon(): ?Coupon;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:64',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('coupons', 'code')->ignore($this->coupon()?->getKey()),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::enum(CouponType::class)],

            /*
             | Meaning depends on type: currency for fixed_amount, percent for
             | percentage. CouponService converts both to their stored integer.
             */
            'value' => ['required', 'numeric', 'gt:0'],

            'minimum_subtotal' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_customer' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('type') !== CouponType::Percentage->value) {
                return;
            }

            if ((float) $this->input('value') > 100) {
                $validator->errors()->add('value', 'A percentage discount cannot exceed 100.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => mb_strtoupper(trim((string) $this->input('code'))),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => 'The code may only contain letters, numbers, dashes and underscores.',
            'expires_at.after' => 'The expiry date must be later than the start date.',
        ];
    }
}
