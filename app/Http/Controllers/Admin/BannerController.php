<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PublishStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Banner\StoreBannerRequest;
use App\Http\Requests\Admin\Banner\UpdateBannerRequest;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use App\Services\Content\BannerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BannerController extends Controller
{
    public function __construct(private readonly BannerService $banners) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Banner::class);

        $search = $request->string('search')->trim()->value();

        $banners = Banner::query()
            ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->orderBy('position')
            ->orderBy('id')
            ->paginate((int) config('shop.catalog.admin_per_page', 20))
            ->withQueryString();

        return Inertia::render('admin/banners/index', [
            'banners' => BannerResource::collection($banners),
            'filters' => ['search' => $search ?: null],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Banner::class);

        return Inertia::render('admin/banners/create', [
            'statuses' => PublishStatus::options(),
        ]);
    }

    public function store(StoreBannerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['image'] = $request->file('image');

        $banner = $this->banners->create($data);

        return to_route('admin.banners.edit', $banner)
            ->with('success', "Banner \"{$banner->title}\" was created.");
    }

    public function edit(Banner $banner): Response
    {
        $this->authorize('update', $banner);

        return Inertia::render('admin/banners/edit', [
            'banner' => new BannerResource($banner),
            'statuses' => PublishStatus::options(),
        ]);
    }

    public function update(UpdateBannerRequest $request, Banner $banner): RedirectResponse
    {
        $data = $request->validated();
        $data['image'] = $request->file('image');

        $this->banners->update($banner, $data);

        return back()->with('success', 'Banner saved.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $this->authorize('delete', $banner);

        $title = $banner->title;
        $this->banners->delete($banner);

        return to_route('admin.banners.index')
            ->with('success', "Banner \"{$title}\" was deleted.");
    }
}
