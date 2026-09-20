<?php

namespace App\Http\Requests\Admin\Order;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class StoreRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $order instanceof Order && ($this->user()?->can('refund', $order) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => ['nullable', 'string', 'max:255'],
            'restock' => ['boolean'],
        ];
    }
}
