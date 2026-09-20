<?php

namespace App\Http\Requests\Admin\Blog;

use App\Models\Blog;
use Illuminate\Foundation\Http\FormRequest;

class BlogFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $blog = $this->route('blog');

        if ($blog instanceof Blog) {
            return $this->user()?->can('update', $blog) ?? false;
        }

        return $this->user()?->can('create', Blog::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
