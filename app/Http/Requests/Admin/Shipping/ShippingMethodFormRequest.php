<?php

namespace App\Http\Requests\Admin\Shipping;

use App\Enums\ShippingMethodType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShippingMethodFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::enum(ShippingMethodType::class)],
            'rate' => ['required', 'numeric', 'min:0'],
            'free_over' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
