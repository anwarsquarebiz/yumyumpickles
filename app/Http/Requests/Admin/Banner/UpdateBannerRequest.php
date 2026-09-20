<?php

namespace App\Http\Requests\Admin\Banner;

use App\Models\Banner;

class UpdateBannerRequest extends BannerFormRequest
{
    public function authorize(): bool
    {
        $banner = $this->banner();

        return $banner !== null && ($this->user()?->can('update', $banner) ?? false);
    }

    protected function banner(): ?Banner
    {
        $banner = $this->route('banner');

        return $banner instanceof Banner ? $banner : null;
    }
}
