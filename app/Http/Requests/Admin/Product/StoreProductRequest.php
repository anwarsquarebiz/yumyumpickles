<?php

namespace App\Http\Requests\Admin\Product;

use App\Models\Product;

class StoreProductRequest extends ProductFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    protected function product(): ?Product
    {
        return null;
    }
}
