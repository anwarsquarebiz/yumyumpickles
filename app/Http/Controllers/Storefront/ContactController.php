<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ContactFormRequest;
use App\Models\Page;
use App\Services\Content\ContactMessageService;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    public function __construct(private readonly ContactMessageService $messages) {}

    public function __invoke(ContactFormRequest $request, string $slug): RedirectResponse
    {
        $page = Page::query()->published()->where('slug', $slug)->firstOrFail();

        abort_unless($page->isContact(), 404);

        $this->messages->submit($page, $request->validated(), $request->ip());

        return back()->with('success', 'Thanks, we received your message.');
    }
}
