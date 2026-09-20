<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\Settings\BrandingService;
use App\Services\Settings\SettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class OrderConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Order {$this->order->order_number} confirmed",
        );
    }

    public function content(): Content
    {
        $this->order->loadMissing([
            'items.product.images',
            'items.variant.images',
            'latestPayment',
        ]);

        $settings = app(SettingsService::class);
        $logoUrl = app(BrandingService::class)->logoUrl();

        return new Content(
            html: 'mail.orders.confirmed',
            text: 'mail.orders.confirmed-text',
            with: [
                'order' => $this->order,
                'url' => $this->viewUrl(),
                'shopName' => (string) $settings->get('store.name'),
                'shopEmail' => (string) $settings->get('store.email'),
                'shopUrl' => route('home'),
                'logoUrl' => $this->absoluteUrl($logoUrl),
                'paymentMethod' => $this->paymentMethodLabel(),
            ],
        );
    }

    private function viewUrl(): string
    {
        if ($this->order->user_id) {
            return route('account.orders.show', $this->order);
        }

        return URL::signedRoute('checkout.complete', $this->order);
    }

    private function paymentMethodLabel(): string
    {
        return match ($this->order->latestPayment?->gateway) {
            'manual' => 'Manual payment',
            default => 'Paid',
        };
    }

    private function absoluteUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return url($url);
    }
}
