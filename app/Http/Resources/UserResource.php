<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'full_name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'role' => $this->role,
            'store_id' => $this->store_id,
            'phone_verified_at' => $this->phone_verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'store' => $this->whenLoaded('store', function (): ?array {
                if (! $this->store) {
                    return null;
                }

                return [
                    'id' => $this->store->id,
                    'name' => $this->store->name,
                    'address' => $this->store->address,
                    'type' => $this->store->type,
                ];
            }),
        ];
    }
}
