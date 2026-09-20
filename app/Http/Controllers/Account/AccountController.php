<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateAccountRequest;
use App\Http\Resources\AddressResource;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function show(): Response
    {
        $user = request()->user();

        return Inertia::render('storefront/account/profile', [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'accepts_marketing' => $user->accepts_marketing,
            ],
            'addresses' => AddressResource::collection($user->addresses()->latest('id')->get()),
            'seo' => ['title' => 'Your account', 'description' => null],
        ]);
    }

    public function update(UpdateAccountRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return back()->with('success', 'Account updated.');
    }
}
