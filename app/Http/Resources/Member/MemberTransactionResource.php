<?php

namespace App\Http\Resources\Member;

use App\Http\Resources\StoreResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberTransactionResource extends JsonResource
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
            'order_number' => $this->order_number,
            'type' => $this->type,
            'store_id' => $this->store_id,
            'total_amount' => (float) $this->total_amount,
            'shipping_fee' => (float) $this->shipping_fee,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'status' => $this->status,
            'shipping_receiver_name' => $this->shipping_receiver_name,
            'shipping_receiver_phone' => $this->shipping_receiver_phone,
            'shipping_address' => $this->shipping_address,
            'courier_name' => $this->courier_name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'store' => new StoreResource($this->whenLoaded('store')),
            'items' => MemberTransactionItemResource::collection($this->whenLoaded('items')),
            'payments' => MemberTransactionPaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}
