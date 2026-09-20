<?php

namespace App\Http\Resources;

use App\Models\OrderStatusEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderStatusEvent
 */
class OrderStatusEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_status' => $this->from_status?->value,
            'to_status' => $this->to_status,
            'note' => $this->note,
            'actor' => $this->whenLoaded('user', fn () => $this->user === null ? null : $this->user->name),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
