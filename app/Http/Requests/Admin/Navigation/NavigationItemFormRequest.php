<?php

namespace App\Http\Requests\Admin\Navigation;

use App\Enums\NavigationLinkType;
use App\Models\Blog;
use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class NavigationItemFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('navigationItem');

        if ($item instanceof NavigationItem) {
            return $this->user()?->can('update', $item) ?? false;
        }

        return $this->user()?->can('create', NavigationItem::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:80'],
            'type' => ['required', Rule::enum(NavigationLinkType::class)],
            'resource_id' => ['nullable', 'integer', 'min:1'],
            'url' => ['nullable', 'string', 'max:2048'],
            'position' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $type = NavigationLinkType::from((string) $this->input('type'));

                if ($type->requiresResource() && blank($this->input('resource_id'))) {
                    $validator->errors()->add('resource_id', 'Choose a '.$type->label().' to link to.');

                    return;
                }

                if ($type->requiresUrl()) {
                    $url = (string) $this->input('url');

                    if ($url === '') {
                        $validator->errors()->add('url', 'Enter a URL or path.');

                        return;
                    }

                    if (! $this->isSafeUrl($url)) {
                        $validator->errors()->add('url', 'Use a site path like /products or a full http(s) URL.');
                    }

                    return;
                }

                if ($type->requiresResource() && ! $this->resourceExists($type, (int) $this->input('resource_id'))) {
                    $validator->errors()->add('resource_id', 'That '.$type->label().' could not be found.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['url', 'resource_id', 'position'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    private function isSafeUrl(string $url): bool
    {
        if (str_starts_with($url, '/')) {
            return ! str_starts_with($url, '//');
        }

        return (bool) preg_match('/^https?:\/\//i', $url);
    }

    private function resourceExists(NavigationLinkType $type, int $id): bool
    {
        return match ($type) {
            NavigationLinkType::Collection => Collection::query()->whereKey($id)->exists(),
            NavigationLinkType::Page => Page::query()->whereKey($id)->exists(),
            NavigationLinkType::Blog => Blog::query()->whereKey($id)->exists(),
            default => false,
        };
    }
}
