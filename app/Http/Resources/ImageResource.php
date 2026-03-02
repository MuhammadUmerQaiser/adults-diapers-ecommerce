<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
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
            'image_path' => $this->image_path,
            'image_url' => asset($this->image_path),
            'is_featured' => $this->is_featured,
            'alt_text' => $this->alt_text,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
