<?php

namespace App\Services\Content;

use App\Enums\NavigationLinkType;
use App\Models\NavigationItem;
use App\Support\CacheKeys;
use Illuminate\Support\Facades\DB;

class NavigationMenuService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): NavigationItem
    {
        return NavigationItem::query()->create($this->attributes($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(NavigationItem $item, array $data): NavigationItem
    {
        $item->update($this->attributes($data, $item));

        return $item;
    }

    public function delete(NavigationItem $item): void
    {
        $item->delete();
    }

    /**
     * Apply a new header order. Unknown ids are ignored; omitted items stay at the end.
     *
     * @param  array<int, int>  $orderedIds
     */
    public function reorder(array $orderedIds, string $menu = NavigationItem::MenuHeader): void
    {
        $existingIds = NavigationItem::query()
            ->forMenu($menu)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $ordered = collect($orderedIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->filter(fn (int $id): bool => in_array($id, $existingIds, true))
            ->values();

        $final = $ordered->concat(collect($existingIds)->diff($ordered))->values();

        DB::transaction(function () use ($final, $menu): void {
            foreach ($final as $index => $id) {
                NavigationItem::query()
                    ->where('menu', $menu)
                    ->whereKey($id)
                    ->update(['position' => $index + 1]);
            }
        });

        CacheKeys::bump(CacheKeys::Navigation);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data, ?NavigationItem $item = null): array
    {
        $type = $data['type'] instanceof NavigationLinkType
            ? $data['type']
            : NavigationLinkType::from($data['type']);

        $position = $data['position'] ?? null;

        if ($position === null || $position === '') {
            $position = $item?->position ?? $this->nextPosition($data['menu'] ?? NavigationItem::MenuHeader);
        }

        return [
            'menu' => $data['menu'] ?? NavigationItem::MenuHeader,
            'title' => $data['title'],
            'type' => $type,
            'resource_id' => $type->requiresResource() ? $data['resource_id'] : null,
            'url' => $type->requiresUrl() ? $data['url'] : null,
            'position' => (int) $position,
        ];
    }

    private function nextPosition(string $menu): int
    {
        return ((int) NavigationItem::query()->where('menu', $menu)->max('position')) + 1;
    }
}
