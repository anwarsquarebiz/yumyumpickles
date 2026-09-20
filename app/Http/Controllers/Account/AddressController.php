<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\AddressFormRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use App\Services\Checkout\AddressService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AddressController extends Controller
{
    public function __construct(private readonly AddressService $addresses) {}

    public function index(): Response
    {
        $user = request()->user();

        return Inertia::render('storefront/account/addresses', [
            'addresses' => AddressResource::collection($user->addresses()->latest('id')->get()),
            'seo' => ['title' => 'Address book', 'description' => null],
        ]);
    }

    public function store(AddressFormRequest $request): RedirectResponse
    {
        $this->addresses->create($request->user(), $request->validated());

        return back()->with('success', 'Address saved.');
    }

    public function update(AddressFormRequest $request, Address $address): RedirectResponse
    {
        $this->authorize('update', $address);

        $this->addresses->update($address, $request->validated());

        return back()->with('success', 'Address updated.');
    }

    public function destroy(Address $address): RedirectResponse
    {
        $this->authorize('delete', $address);

        $this->addresses->delete($address);

        return back()->with('success', 'Address removed.');
    }
}
