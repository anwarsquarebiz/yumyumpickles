<?php

namespace App\Http\Resources;

use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Refund
 */
class RefundResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'reason' => $this->reason,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'restock' => $this->restock,
            'processed_at' => $this->processed_at?->toDateTimeString(),
        ];
    }
}
