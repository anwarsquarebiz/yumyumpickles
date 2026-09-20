<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Storefront\StorefrontContentService;
use Illuminate\Http\JsonResponse;

class StorefrontController extends Controller
{
    public function __invoke(StorefrontContentService $content): JsonResponse
    {
        return response()->json([
            'data' => $content->bundle(),
        ]);
    }
}
