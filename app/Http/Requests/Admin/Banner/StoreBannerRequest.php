<?php

namespace App\Http\Requests\Admin\Banner;

use App\Models\Banner;

class StoreBannerRequest extends BannerFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Banner::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'image' => ['required', 'image', 'max:4096'],
        ]);
    }

    protected function banner(): ?Banner
    {
        return null;
    }
}
