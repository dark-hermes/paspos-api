<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class InventoryResource extends JsonResource
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
            'store_id' => $this->store_id,
            'product_id' => $this->product_id,
            'stock' => (float) $this->stock,
            'purchase_price' => (float) $this->purchase_price,
            'selling_price' => (float) $this->selling_price,
            'discount_percentage' => (int) $this->discount_percentage,
            'min_stock' => (float) $this->min_stock,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'store' => $this->whenLoaded('store', function (): ?array {
                if (! $this->store) {
                    return null;
                }

                return [
                    'id' => $this->store->id,
                    'name' => $this->store->name,
                    'type' => $this->store->type,
                ];
            }),
            'product' => $this->whenLoaded('product', function (): ?array {
                if (! $this->product) {
                    return null;
                }

                return [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'sku' => $this->product->sku,
                    'unit' => $this->product->unit,
                    'image_url' => $this->product->image_path ? Storage::disk('public')->url($this->product->image_path) : null,
                ];
            }),
        ];
    }
}
