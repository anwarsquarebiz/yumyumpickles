<?php

namespace App\Http\Requests\Admin\Page;

use App\Models\Page;

class UpdatePageRequest extends PageFormRequest
{
    public function authorize(): bool
    {
        $page = $this->page();

        return $page !== null && ($this->user()?->can('update', $page) ?? false);
    }

    protected function page(): ?Page
    {
        $page = $this->route('page');

        return $page instanceof Page ? $page : null;
    }
}
