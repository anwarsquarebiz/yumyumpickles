<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['boolean'],
            'favicon' => ['nullable', 'file', 'mimes:png,ico,svg', 'max:512'],
            'remove_favicon' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['remove_logo', 'remove_favicon'] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN)]);
            }
        }
    }
}
