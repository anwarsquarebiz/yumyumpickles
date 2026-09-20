<?php

namespace App\Http\Requests\Admin\Product;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product && ($this->user()?->can('update', $product) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'max:4096'],
            'alt' => ['nullable', 'string', 'max:255'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
        ];
    }
}
