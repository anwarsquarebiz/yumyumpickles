<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Refund;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RefundIssuedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public Refund $refund) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Refund issued for order {$this->order->order_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.orders.refunded',
            with: [
                'order' => $this->order,
                'refund' => $this->refund,
            ],
        );
    }
}
