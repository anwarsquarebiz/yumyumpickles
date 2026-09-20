<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Models\Page;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function show(string $slug): Response
    {
        $page = Page::query()->published()->where('slug', $slug)->firstOrFail();

        $view = match ($slug) {
            'our-story' => 'storefront/story',
            'faq' => 'storefront/faq',
            'contact' => 'storefront/contact',
            'shipping', 'refunds', 'privacy', 'terms' => 'storefront/policy',
            default => 'storefront/pages/show',
        };

        return Inertia::render($view, [
            'page' => new PageResource($page),
            'policyKey' => $slug,
            'seo' => [
                'title' => $page->metaTitle(),
                'description' => $page->metaDescription(),
            ],
        ]);
    }
}
