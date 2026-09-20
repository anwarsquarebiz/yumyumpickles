<?php

namespace App\Http\Requests\Admin\Navigation;

use App\Models\NavigationItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderNavigationItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reorder', NavigationItem::class) ?? false;
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
                Rule::exists('navigation_items', 'id')->where('menu', NavigationItem::MenuHeader),
            ],
        ];
    }
}
