<?php

namespace App\Services\Checkout;

use App\Enums\AddressType;
use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AddressService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Address
    {
        return DB::transaction(function () use ($user, $data): Address {
            $address = $user->addresses()->create($this->attributes($data));

            if ($address->is_default) {
                $this->makeDefault($address);
            }

            return $address;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Address $address, array $data): Address
    {
        return DB::transaction(function () use ($address, $data): Address {
            $address->update($this->attributes($data));

            if ($address->is_default) {
                $this->makeDefault($address);
            }

            return $address->refresh();
        });
    }

    public function delete(Address $address): void
    {
        $address->delete();
    }

    public function makeDefault(Address $address): void
    {
        Address::query()
            ->where('user_id', $address->user_id)
            ->where('type', $address->type)
            ->whereKeyNot($address->getKey())
            ->update(['is_default' => false]);

        if (! $address->is_default) {
            $address->forceFill(['is_default' => true])->save();
        }
    }

    /**
     * Persist a checkout address onto the customer's address book.
     *
     * @param  array<string, mixed>  $snapshot
     */
    public function remember(User $user, array $snapshot, AddressType $type, bool $asDefault = true): Address
    {
        $existing = $user->addresses()
            ->where('type', $type)
            ->where('address_line1', $snapshot['address_line1'] ?? '')
            ->where('postal_code', $snapshot['postal_code'] ?? '')
            ->first();

        if ($existing !== null) {
            if ($asDefault) {
                $this->makeDefault($existing);
            }

            return $existing;
        }

        return $this->create($user, array_merge($snapshot, [
            'type' => $type->value,
            'is_default' => $asDefault,
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return [
            'type' => $data['type'] ?? AddressType::Shipping->value,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'company' => $data['company'] ?? null,
            'address_line1' => $data['address_line1'],
            'address_line2' => $data['address_line2'] ?? null,
            'city' => $data['city'],
            'province' => $data['province'] ?? null,
            'postal_code' => $data['postal_code'],
            'country_code' => strtoupper((string) $data['country_code']),
            'phone' => $data['phone'] ?? null,
            'is_default' => (bool) ($data['is_default'] ?? false),
        ];
    }
}
