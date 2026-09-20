<?php

use App\Models\Address;
use App\Models\User;

it('lets a customer manage their address book', function () {
    $user = User::factory()->customer()->create();

    $this->actingAs($user)
        ->post('/account/addresses', [
            'type' => 'shipping',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'address_line1' => '1 Computing Lane',
            'city' => 'London',
            'postal_code' => 'SW1A 1AA',
            'country_code' => 'GB',
            'is_default' => true,
        ])
        ->assertRedirect();

    expect($user->addresses()->count())->toBe(1);

    $address = $user->addresses()->first();

    $this->actingAs($user)
        ->delete("/account/addresses/{$address->id}")
        ->assertRedirect();

    expect($user->addresses()->count())->toBe(0);
});

it('forbids editing someone else\'s address', function () {
    $owner = User::factory()->customer()->create();
    $address = Address::factory()->for($owner)->create();
    $stranger = User::factory()->customer()->create();

    $this->actingAs($stranger)
        ->delete("/account/addresses/{$address->id}")
        ->assertForbidden();
});
