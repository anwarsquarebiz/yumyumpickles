<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PageTemplate;
use App\Enums\PublishStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Page\StorePageRequest;
use App\Http\Requests\Admin\Page\UpdatePageRequest;
use App\Http\Resources\PageResource;
use App\Models\Page;
use App\Services\Content\PageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function __construct(private readonly PageService $pages) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Page::class);

        $search = $request->string('search')->trim()->value();

        $pages = Page::query()
            ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->latest('id')
            ->paginate((int) config('shop.catalog.admin_per_page', 20))
            ->withQueryString();

        return Inertia::render('admin/pages/index', [
            'pages' => PageResource::collection($pages),
            'filters' => ['search' => $search ?: null],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Page::class);

        return Inertia::render('admin/pages/create', [
            'statuses' => PublishStatus::options(),
            'templates' => PageTemplate::options(),
        ]);
    }

    public function store(StorePageRequest $request): RedirectResponse
    {
        $page = $this->pages->create($request->validated());

        return to_route('admin.pages.edit', $page)
            ->with('success', "Page \"{$page->title}\" was created.");
    }

    public function edit(Page $page): Response
    {
        $this->authorize('update', $page);

        return Inertia::render('admin/pages/edit', [
            'page' => new PageResource($page),
            'statuses' => PublishStatus::options(),
            'templates' => PageTemplate::options(),
        ]);
    }

    public function update(UpdatePageRequest $request, Page $page): RedirectResponse
    {
        $this->pages->update($page, $request->validated());

        return back()->with('success', 'Page saved.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $this->authorize('delete', $page);

        $title = $page->title;
        $this->pages->delete($page);

        return to_route('admin.pages.index')->with('success', "Page \"{$title}\" was deleted.");
    }
}
