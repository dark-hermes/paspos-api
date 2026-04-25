<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'cashier_id' => $this->cashier_id,
            'amount' => $this->amount,
            'payment_method' => $this->payment_method,
            'note' => $this->note,
            'created_at' => $this->created_at,
            'cashier' => new UserResource($this->whenLoaded('cashier')),
        ];
    }
}
