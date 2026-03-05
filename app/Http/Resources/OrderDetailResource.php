<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'product_variation_id' => $this->product_variation_id,
            'product_name'         => $this->product_name,
            'sku'                  => $this->sku,
            'size'                 => $this->size,
            'absorbency_level'     => $this->absorbency_level,
            'quantity_per_pack'    => $this->quantity_per_pack,
            'unit_price'           => (float) $this->unit_price,
            'quantity'             => $this->quantity,
            'subtotal'             => (float) $this->subtotal,
        ];
    }
}
