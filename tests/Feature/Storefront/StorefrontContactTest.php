<?php

use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use App\Models\Page;
use Illuminate\Support\Facades\Mail;

it('shows a contact form on contact template pages', function () {
    $page = Page::factory()->contact()->create(['slug' => 'contact-us']);

    $this->get('/pages/contact-us')
        ->assertOk()
        ->assertInertia(fn ($inertia) => $inertia
            ->component('storefront/pages/show')
            ->where('page.data.template', 'contact')
            ->where('page.data.slug', 'contact-us')
        );
});

it('does not show a contact form on default pages', function () {
    Page::factory()->create(['slug' => 'about-us', 'template' => 'default']);

    $this->get('/pages/about-us')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('page.data.template', 'default'));
});

it('accepts a contact form submission', function () {
    Mail::fake();

    $page = Page::factory()->contact()->create(['slug' => 'contact-us']);

    $this->from("/pages/{$page->slug}")
        ->post("/pages/{$page->slug}/contact", [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '555-0100',
            'message' => 'I have a question about an order.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $message = ContactMessage::query()->firstOrFail();

    expect($message->name)->toBe('Jane Doe')
        ->and($message->email)->toBe('jane@example.com')
        ->and($message->page_id)->toBe($page->id);

    Mail::assertQueued(ContactMessageMail::class, function (ContactMessageMail $mail) use ($message): bool {
        return $mail->contactMessage->is($message);
    });
});

it('ignores honeypot contact submissions', function () {
    Mail::fake();

    $page = Page::factory()->contact()->create(['slug' => 'contact-us']);

    $this->from("/pages/{$page->slug}")
        ->post("/pages/{$page->slug}/contact", [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'message' => 'Spam',
            'website' => 'https://spam.test',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(ContactMessage::query()->count())->toBe(0);
    Mail::assertNothingQueued();
});

it('rejects contact submissions on non-contact pages', function () {
    $page = Page::factory()->create(['slug' => 'about-us']);

    $this->post("/pages/{$page->slug}/contact", [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'message' => 'Hello',
    ])->assertNotFound();
});
