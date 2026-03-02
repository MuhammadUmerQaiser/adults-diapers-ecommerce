<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $featuredImage = $this->images->where('is_featured', 1)->first();
        // $otherImages = $this->images->where('is_featured', 0);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'sku' => $this->sku,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'is_active' => $this->is_active,
            'featured_image' => $featuredImage ? new ImageResource($featuredImage) : null,
            'images' => ImageResource::collection($this->images),
            'variations' => ProductVariationResource::collection($this->whenLoaded('variations')),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
