<?php

namespace App\Http\Requests\Admin\Collection;

use App\Models\Collection;

class StoreCollectionRequest extends CollectionFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Collection::class) ?? false;
    }

    protected function collection(): ?Collection
    {
        return null;
    }
}
