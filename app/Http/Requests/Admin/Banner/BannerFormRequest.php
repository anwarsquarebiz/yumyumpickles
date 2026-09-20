<?php

namespace App\Http\Requests\Admin\Banner;

use App\Enums\PublishStatus;
use App\Models\Banner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class BannerFormRequest extends FormRequest
{
    abstract protected function banner(): ?Banner;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'button_label' => ['nullable', 'string', 'max:80', 'required_with:button_url'],
            'button_url' => ['nullable', 'string', 'max:2048', 'required_with:button_label'],
            'alt' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'status' => ['required', Rule::enum(PublishStatus::class)],
            'published_at' => ['nullable', 'date'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['boolean'],
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

        if ($this->has('remove_image')) {
            $this->merge(['remove_image' => filter_var($this->input('remove_image'), FILTER_VALIDATE_BOOLEAN)]);
        }

        foreach (['subtitle', 'button_label', 'button_url', 'alt', 'starts_at', 'ends_at'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }
}
