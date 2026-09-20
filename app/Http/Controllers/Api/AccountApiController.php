<?php

namespace App\Http\Controllers\Api;

use App\Enums\AddressType;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\Wishlist;
use App\Services\Checkout\AddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountApiController extends Controller
{
    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        $request->user()->fill($data)->save();

        return response()->json(['data' => [
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'phone' => $request->user()->phone,
            'loggedIn' => true,
            'points' => $request->user()->loyalty_points,
            'referralCode' => $request->user()->referral_code,
        ]]);
    }

    public function orders(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->with(['items', 'payments'])
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->get();

        return response()->json(['data' => OrderResource::collection($orders)]);
    }

    public function addresses(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->addresses()->latest('id')->get()->map(
                fn (Address $address): array => $this->presentAddress($address)
            ),
        ]);
    }

    public function saveAddress(Request $request, AddressService $addresses): JsonResponse
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:32'],
            'line1' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'pincode' => ['required', 'string', 'max:16'],
            'isDefault' => ['boolean'],
        ]);

        $parts = preg_split('/\s+/', trim($data['name']), 2) ?: [];
        $payload = [
            'type' => AddressType::Shipping->value,
            'first_name' => $parts[0] ?? $data['name'],
            'last_name' => $parts[1] ?? '',
            'address_line1' => $data['line1'],
            'city' => $data['city'],
            'province' => $data['state'],
            'postal_code' => $data['pincode'],
            'country_code' => 'IN',
            'phone' => $data['phone'],
            'is_default' => $data['isDefault'] ?? false,
        ];

        if (! empty($data['id'])) {
            $address = $request->user()->addresses()->whereKey($data['id'])->firstOrFail();
            $address = $addresses->update($address, $payload);
        } else {
            $address = $addresses->create($request->user(), $payload);
        }

        return response()->json(['data' => $this->presentAddress($address)]);
    }

    public function deleteAddress(Request $request, Address $address): JsonResponse
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $address->delete();

        return response()->json(['ok' => true]);
    }

    public function wishlist(Request $request): JsonResponse
    {
        $slugs = $request->user()->wishlists()->with('product')->get()
            ->pluck('product.slug')
            ->filter()
            ->values();

        return response()->json(['data' => $slugs]);
    }

    public function toggleWishlist(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'string'],
        ]);

        $product = Product::query()->where('slug', $data['product_id'])->orWhere('id', $data['product_id'])->firstOrFail();
        $existing = Wishlist::query()
            ->where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existing !== null) {
            $existing->delete();
        } else {
            Wishlist::query()->create([
                'user_id' => $request->user()->id,
                'product_id' => $product->id,
            ]);
        }

        return $this->wishlist($request);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentAddress(Address $address): array
    {
        return [
            'id' => (string) $address->id,
            'name' => $address->fullName(),
            'phone' => $address->phone ?? '',
            'line1' => $address->address_line1,
            'city' => $address->city,
            'state' => $address->province ?? '',
            'pincode' => $address->postal_code,
            'isDefault' => $address->is_default,
        ];
    }
}
