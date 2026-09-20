<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Models\Page;
use App\Services\Content\ContactMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FormApiController extends Controller
{
    public function contact(Request $request, ContactMessageService $messages): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $page = Page::query()->where('slug', 'contact')->first();

        if ($page !== null) {
            $messages->submit($page, $data, $request->ip());
        }

        return response()->json(['ok' => true]);
    }

    public function newsletter(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        NewsletterSubscriber::query()->updateOrCreate(
            ['email' => $data['email']],
            ['subscribed_at' => now()],
        );

        return response()->json(['ok' => true]);
    }
}
