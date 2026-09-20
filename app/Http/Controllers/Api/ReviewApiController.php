<?php

namespace App\Http\Controllers\Api;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewApiController extends Controller
{
    public function index(string $productId): JsonResponse
    {
        $product = Product::query()->where('slug', $productId)->orWhere('id', $productId)->firstOrFail();

        $reviews = $product->reviews()
            ->approved()
            ->latest('reviewed_at')
            ->get()
            ->map(fn (ProductReview $review): array => [
                'productId' => $product->slug,
                'name' => $review->author_name,
                'city' => $review->author_city,
                'rating' => $review->rating,
                'date' => optional($review->reviewed_at)->format('j M Y') ?? $review->created_at?->format('j M Y'),
                'text' => $review->body,
            ]);

        return response()->json(['data' => $reviews]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'string'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'text' => ['required', 'string', 'max:2000'],
            'city' => ['nullable', 'string', 'max:100'],
        ]);

        $product = Product::query()->where('slug', $data['product_id'])->orWhere('id', $data['product_id'])->firstOrFail();
        $user = $request->user();

        $review = ProductReview::query()->create([
            'product_id' => $product->id,
            'user_id' => $user?->id,
            'author_name' => $user?->name ?? 'Guest',
            'author_city' => $data['city'] ?? null,
            'rating' => $data['rating'],
            'body' => $data['text'],
            'status' => ReviewStatus::Approved,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'productId' => $product->slug,
                'rating' => $review->rating,
                'text' => $review->body,
                'date' => $review->reviewed_at?->format('j M Y'),
            ],
        ], 201);
    }
}
