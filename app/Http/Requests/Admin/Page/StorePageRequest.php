<?php

namespace App\Http\Requests\Admin\Page;

use App\Models\Page;

class StorePageRequest extends PageFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Page::class) ?? false;
    }

    protected function page(): ?Page
    {
        return null;
    }
}
