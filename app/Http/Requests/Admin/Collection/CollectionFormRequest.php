<?php

namespace App\Http\Requests\Admin\Collection;

use App\Enums\PublishStatus;
use App\Models\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class CollectionFormRequest extends FormRequest
{
    abstract protected function collection(): ?Collection;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(PublishStatus::class)],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'position' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'published_at' => ['nullable', 'date'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['boolean'],
            'product_ids' => ['array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $status = $this->input('status');

        if ($status === PublishStatus::Published->value && blank($this->input('published_at'))) {
            $this->merge(['published_at' => now()->toDateTimeString()]);
        }

        if ($status === PublishStatus::Draft->value) {
            $this->merge(['published_at' => null]);
        }
    }
}
