<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'customer_id' => $this->customer_id,
            'cashier_id' => $this->cashier_id,
            'total_amount' => $this->total_amount,
            'shipping_fee' => $this->shipping_fee,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'status' => $this->status,
            'shipping_name' => $this->shipping_name,
            'courier_name' => $this->courier_name,
            'shipping_receiver_name' => $this->shipping_receiver_name,
            'shipping_receiver_phone' => $this->shipping_receiver_phone,
            'shipping_address' => $this->shipping_address,
            'shipping_notes' => $this->shipping_notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'store' => new StoreResource($this->whenLoaded('store')),
            'customer' => new UserResource($this->whenLoaded('customer')),
            'cashier' => new UserResource($this->whenLoaded('cashier')),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}
