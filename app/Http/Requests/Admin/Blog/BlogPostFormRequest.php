<?php

namespace App\Http\Requests\Admin\Blog;

use App\Enums\PublishStatus;
use App\Models\BlogPost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BlogPostFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $post = $this->route('post');

        if ($post instanceof BlogPost) {
            return $this->user()?->can('update', $post) ?? false;
        }

        return $this->user()?->can('create', BlogPost::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'blog_id' => ['required', 'integer', 'exists:blogs,id'],
            'blog_category_id' => ['nullable', 'integer', 'exists:blog_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(PublishStatus::class)],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'published_at' => ['nullable', 'date'],
            'featured_image' => ['nullable', 'image', 'max:4096'],
            'remove_featured_image' => ['boolean'],
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
