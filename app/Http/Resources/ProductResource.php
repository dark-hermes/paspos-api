<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
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
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id,
            'name' => $this->name,
            'barcode' => $this->barcode,
            'sku' => $this->sku,
            'image_url' => $this->image_path ? Storage::disk('public')->url($this->image_path) : null,
            'unit' => $this->unit,
            'description' => $this->description,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
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
}
