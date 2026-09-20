<?php

namespace App\Http\Requests\Admin\Page;

use App\Enums\PageTemplate;
use App\Enums\PublishStatus;
use App\Models\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class PageFormRequest extends FormRequest
{
    abstract protected function page(): ?Page;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(PublishStatus::class)],
            'template' => ['required', Rule::enum(PageTemplate::class)],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'published_at' => ['nullable', 'date'],
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
