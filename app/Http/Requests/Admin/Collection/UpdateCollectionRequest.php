<?php

namespace App\Http\Requests\Admin\Collection;

use App\Models\Collection;

class UpdateCollectionRequest extends CollectionFormRequest
{
    public function authorize(): bool
    {
        $collection = $this->collection();

        return $collection !== null && ($this->user()?->can('update', $collection) ?? false);
    }

    protected function collection(): ?Collection
    {
        $collection = $this->route('collection');

        return $collection instanceof Collection ? $collection : null;
    }
}
