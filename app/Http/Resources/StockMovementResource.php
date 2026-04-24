<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class StockMovementResource extends JsonResource
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
            'src_store_id' => $this->src_store_id,
            'dest_store_id' => $this->dest_store_id,
            'product_id' => $this->product_id,
            'quantity' => (float) $this->quantity,
            'type' => $this->type,
            'title' => $this->title,
            'note' => $this->note,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'source_store' => $this->whenLoaded('sourceStore', function (): ?array {
                if (! $this->sourceStore) {
                    return null;
                }

                return [
                    'id' => $this->sourceStore->id,
                    'name' => $this->sourceStore->name,
                    'type' => $this->sourceStore->type,
                ];
            }),
            'destination_store' => $this->whenLoaded('destinationStore', function (): ?array {
                if (! $this->destinationStore) {
                    return null;
                }

                return [
                    'id' => $this->destinationStore->id,
                    'name' => $this->destinationStore->name,
                    'type' => $this->destinationStore->type,
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
