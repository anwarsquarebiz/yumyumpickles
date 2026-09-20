<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NavigationLinkType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Navigation\NavigationItemFormRequest;
use App\Http\Requests\Admin\Navigation\ReorderNavigationItemsRequest;
use App\Http\Resources\NavigationItemResource;
use App\Models\Blog;
use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\Page;
use App\Services\Content\NavigationMenuService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NavigationItemController extends Controller
{
    public function __construct(private readonly NavigationMenuService $menus) {}

    public function index(): Response
    {
        $this->authorize('viewAny', NavigationItem::class);

        return Inertia::render('admin/navigation/index', [
            'items' => NavigationItemResource::collection(
                NavigationItem::query()->forMenu()->get()
            ),
            'link_types' => NavigationLinkType::options(),
            'collections' => Collection::query()->orderBy('title')->get(['id', 'title'])
                ->map(fn (Collection $collection): array => [
                    'value' => $collection->id,
                    'label' => $collection->title,
                ])
                ->all(),
            'pages' => Page::query()->orderBy('title')->get(['id', 'title'])
                ->map(fn (Page $page): array => [
                    'value' => $page->id,
                    'label' => $page->title,
                ])
                ->all(),
            'blogs' => Blog::query()->orderBy('title')->get(['id', 'title'])
                ->map(fn (Blog $blog): array => [
                    'value' => $blog->id,
                    'label' => $blog->title,
                ])
                ->all(),
        ]);
    }

    public function store(NavigationItemFormRequest $request): RedirectResponse
    {
        $this->menus->create($request->validated());

        return back()->with('success', 'Menu item added.');
    }

    public function update(NavigationItemFormRequest $request, NavigationItem $navigationItem): RedirectResponse
    {
        $this->menus->update($navigationItem, $request->validated());

        return back()->with('success', 'Menu item saved.');
    }

    public function destroy(NavigationItem $navigationItem): RedirectResponse
    {
        $this->authorize('delete', $navigationItem);

        $this->menus->delete($navigationItem);

        return back()->with('success', 'Menu item removed.');
    }

    public function reorder(ReorderNavigationItemsRequest $request): RedirectResponse
    {
        $this->menus->reorder($request->validated('ids'));

        return back();
    }
}
