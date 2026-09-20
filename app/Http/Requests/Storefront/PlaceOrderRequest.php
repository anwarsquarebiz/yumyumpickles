<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'shipping_method_id' => ['required', 'integer', Rule::exists('shipping_methods', 'id')->where('is_active', true)],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'save_address' => ['boolean'],
            'billing_same_as_shipping' => ['boolean'],
            ...$this->addressRules('shipping'),
            ...$this->addressRules('billing', required: ! $this->boolean('billing_same_as_shipping', true)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function addressRules(string $prefix, bool $required = true): array
    {
        $rule = $required ? 'required' : 'nullable';

        return [
            "{$prefix}.first_name" => [$rule, 'string', 'max:100'],
            "{$prefix}.last_name" => [$rule, 'string', 'max:100'],
            "{$prefix}.company" => ['nullable', 'string', 'max:150'],
            "{$prefix}.address_line1" => [$rule, 'string', 'max:255'],
            "{$prefix}.address_line2" => ['nullable', 'string', 'max:255'],
            "{$prefix}.city" => [$rule, 'string', 'max:100'],
            "{$prefix}.province" => ['nullable', 'string', 'max:100'],
            "{$prefix}.postal_code" => [$rule, 'string', 'max:32'],
            "{$prefix}.country_code" => [$rule, 'string', 'size:2'],
            "{$prefix}.phone" => ['nullable', 'string', 'max:32'],
        ];
    }
}
