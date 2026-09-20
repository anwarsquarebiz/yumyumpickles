<?php

namespace App\Mail;

use App\Models\ContactMessage;
use App\Services\Settings\SettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $contactMessage)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Contact form: {$this->contactMessage->name}",
            replyTo: [
                new Address($this->contactMessage->email, $this->contactMessage->name),
            ],
        );
    }

    public function content(): Content
    {
        $settings = app(SettingsService::class);

        return new Content(
            html: 'mail.contact.message',
            text: 'mail.contact.message-text',
            with: [
                'contact' => $this->contactMessage,
                'shopName' => (string) $settings->get('store.name'),
            ],
        );
    }
}
