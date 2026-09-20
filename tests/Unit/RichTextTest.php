<?php

use App\Support\RichText;

it('keeps formatted html and drops scripts', function () {
    $html = RichText::sanitize('<p>Hello <strong>there</strong></p><script>alert(1)</script>');

    expect($html)->toContain('<p>Hello <strong>there</strong></p>')
        ->and($html)->not->toContain('script')
        ->and($html)->not->toContain('alert');
});

it('allows safe links and rejects javascript urls', function () {
    $html = RichText::sanitize('<p><a href="https://example.com">Safe</a> <a href="javascript:alert(1)">Bad</a></p>');

    expect($html)->toContain('href="https://example.com"')
        ->and($html)->not->toContain('javascript:');
});

it('returns null for empty markup', function () {
    expect(RichText::sanitize('<p></p>'))->toBeNull()
        ->and(RichText::sanitize('   '))->toBeNull();
});
