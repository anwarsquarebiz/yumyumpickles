<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $status = $request->string('status')->toString();

        $reviews = ProductReview::query()
            ->with(['product:id,title,slug', 'user:id,name,email'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('reviewed_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/reviews/index', [
            'reviews' => $reviews,
            'filters' => ['status' => $status !== '' ? $status : null],
            'statuses' => ReviewStatus::options(),
        ]);
    }

    public function update(Request $request, ProductReview $review): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate([
            'status' => ['required', 'in:pending,approved,hidden'],
        ]);

        $review->forceFill(['status' => $data['status']])->save();

        return back()->with('success', 'Review updated.');
    }
}
