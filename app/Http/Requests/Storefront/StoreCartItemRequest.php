<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class StoreCartItemRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $max = config('shop.cart.max_quantity_per_line', 99);

        return [
            'product_variant_id' => ['required_without:items', 'integer', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:'.$max],
            'items' => ['required_without:product_variant_id', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:'.$max],
        ];
    }

    public function quantity(): int
    {
        return max(1, (int) ($this->validated('quantity') ?? 1));
    }

    public function variantId(): int
    {
        return (int) $this->validated('product_variant_id');
    }

    /**
     * @return list<array{id: int, qty: int}>
     */
    public function lines(): array
    {
        if ($this->filled('items')) {
            return collect($this->validated('items'))
                ->map(fn (array $item): array => [
                    'id' => (int) $item['product_variant_id'],
                    'qty' => max(1, (int) ($item['quantity'] ?? 1)),
                ])
                ->values()
                ->all();
        }

        return [
            ['id' => $this->variantId(), 'qty' => $this->quantity()],
        ];
    }
}
