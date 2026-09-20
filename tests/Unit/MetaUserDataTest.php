<?php

use App\Services\Ads\MetaUserData;

it('hashes a trimmed lowercase email the way Meta expects', function () {
    expect(MetaUserData::hashEmail('  Buyer@Example.COM  '))
        ->toBe(hash('sha256', 'buyer@example.com'))
        ->and(MetaUserData::hashEmail(''))->toBeNull()
        ->and(MetaUserData::hashEmail(null))->toBeNull();
});

it('hashes a phone number using digits only', function () {
    expect(MetaUserData::hashPhone('+1 (555) 010-0999'))
        ->toBe(hash('sha256', '15550100999'))
        ->and(MetaUserData::hashPhone(''))->toBeNull()
        ->and(MetaUserData::hashPhone(null))->toBeNull();
});
