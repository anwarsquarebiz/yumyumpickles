<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Order {$this->order->order_number} is now {$this->order->status->label()}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.orders.status-changed',
            with: [
                'order' => $this->order,
                'url' => $this->order->user_id
                    ? url('/account/orders/'.$this->order->id)
                    : url('/checkout/'.$this->order->id.'/complete'),
            ],
        );
    }
}
