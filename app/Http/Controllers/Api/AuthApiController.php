<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Cart\CartResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthApiController extends Controller
{
    public function register(Request $request, CartResolver $resolver): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'phone' => $data['phone'] ?? null,
            'role' => UserRole::Customer,
            'email_verified_at' => now(),
            'loyalty_points' => 0,
            'referral_code' => strtoupper(Str::random(8)),
        ]);

        Auth::login($user);
        $resolver->claimFor($user);

        return response()->json(['data' => $this->profile($user, $user->createToken('storefront')->plainTextToken)]);
    }

    public function login(Request $request, CartResolver $resolver): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, true)) {
            return response()->json(['message' => 'Those details do not match our records.'], 422);
        }

        $user = $request->user();
        $user->tokens()->where('name', 'storefront')->delete();
        $resolver->claimFor($user);

        return response()->json(['data' => $this->profile($user, $user->createToken('storefront')->plainTextToken)]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user('sanctum')?->currentAccessToken()?->delete();
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['ok' => true]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        if ($user === null) {
            return response()->json(['data' => null]);
        }

        return response()->json(['data' => $this->profile($user)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function profile(User $user, ?string $token = null): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? '',
            'loggedIn' => true,
            'points' => $user->loyalty_points,
            'referralCode' => $user->referral_code,
            'token' => $token,
        ];
    }
}
