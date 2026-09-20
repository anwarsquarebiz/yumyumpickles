<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /*
             | Zero is allowed and means "remove this line", which is how a
             | quantity stepper behaves when it is decremented to nothing.
             */
            'quantity' => ['required', 'integer', 'min:0', 'max:'.config('shop.cart.max_quantity_per_line', 99)],
        ];
    }

    public function quantity(): int
    {
        return (int) $this->validated('quantity');
    }
}
