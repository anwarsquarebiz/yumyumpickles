<?php

namespace App\Http\Requests\Admin\Product;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reorder', Product::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => [
                'integer',
                'distinct',
                Rule::exists('products', 'id')->whereNull('deleted_at'),
            ],
        ];
    }
}
