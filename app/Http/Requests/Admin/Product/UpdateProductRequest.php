<?php

namespace App\Http\Requests\Admin\Product;

use App\Models\Product;

class UpdateProductRequest extends ProductFormRequest
{
    public function authorize(): bool
    {
        $product = $this->product();

        return $product !== null && ($this->user()?->can('update', $product) ?? false);
    }

    protected function product(): ?Product
    {
        $product = $this->route('product');

        return $product instanceof Product ? $product : null;
    }
}
