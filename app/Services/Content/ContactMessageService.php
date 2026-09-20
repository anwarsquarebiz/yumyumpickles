<?php

namespace App\Services\Content;

use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use App\Models\Page;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Mail;

class ContactMessageService
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * @param  array{name: string, email: string, phone?: string|null, message: string, website?: string|null}  $data
     */
    public function submit(Page $page, array $data, ?string $ipAddress): void
    {
        if (filled($data['website'] ?? null)) {
            return;
        }

        $message = ContactMessage::query()->create([
            'page_id' => $page->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'message' => $data['message'],
            'ip_address' => $ipAddress,
        ]);

        $to = (string) $this->settings->get('store.email');

        if ($to !== '') {
            Mail::to($to)->queue(new ContactMessageMail($message));
        }
    }
}
