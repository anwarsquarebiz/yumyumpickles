<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gateway' => $this->gateway,
            'gateway_reference' => $this->gateway_reference,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'amount' => $this->amount,
            'failure_reason' => $this->failure_reason,
            'paid_at' => $this->paid_at?->toDateTimeString(),
        ];
    }
}
