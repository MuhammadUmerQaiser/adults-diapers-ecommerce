<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariationResource extends JsonResource
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
            'product_id' => $this->product_id,
            'size' => new SizeResource($this->whenLoaded('size')),
            'sku' => $this->sku,
            'price' => (float) $this->price,
            'quantity_per_pack' => $this->quantity_per_pack,
            'stock' => $this->stock,
            'absorbency_level' => $this->absorbency_level,
            'is_active' => $this->is_active,
            'price_per_piece' => $this->price_per_piece,
            'total_pieces' => $this->total_pieces,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
