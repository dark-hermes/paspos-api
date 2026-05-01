<?php

namespace App\Http\Resources\Member;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MemberProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $inventory = $this->inventories->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'unit' => $this->unit,
            'weight' => $this->weight !== null ? (float) $this->weight : null,
            'description' => $this->description,
            'image_url' => $this->image_path ? Storage::disk('public')->url($this->image_path) : null,
            'stock' => $inventory?->stock !== null ? (float) $inventory->stock : null,
            'selling_price' => $inventory?->selling_price !== null ? (float) $inventory->selling_price : null,
            'discount_percentage' => $inventory?->discount_percentage !== null ? (int) $inventory->discount_percentage : null,
            'final_price' => $inventory ? $this->finalPrice($inventory) : null,
            'category' => $this->whenLoaded('category', function (): ?array {
                if (! $this->category) {
                    return null;
                }

                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),
            'brand' => $this->whenLoaded('brand', function (): ?array {
                if (! $this->brand) {
                    return null;
                }

                return [
                    'id' => $this->brand->id,
                    'name' => $this->brand->name,
                    'logo_url' => $this->brand->logo_path ? Storage::disk('public')->url($this->brand->logo_path) : null,
                ];
            }),
        ];
    }

    private function finalPrice(object $inventory): float
    {
        $unitPrice = (float) $inventory->selling_price;
        $discountPercentage = (int) $inventory->discount_percentage;

        if ($discountPercentage > 0) {
            $unitPrice -= $unitPrice * ($discountPercentage / 100);
        }

        return round($unitPrice, 2);
    }
}
