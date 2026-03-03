<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'category_id' => 'sometimes|exists:categories,id',
            'is_active' => 'sometimes|boolean',
            'replace_images' => 'sometimes|boolean',
            'featured_image_index' => 'sometimes|integer',
            'images' => 'sometimes|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
            'delete_image_ids' => 'sometimes|array',
            'delete_image_ids.*' => 'exists:images,id',
            'variations' => 'sometimes|array',
            'variations.*.id' => 'sometimes|exists:product_variations,id',
            'variations.*.size_id' => 'sometimes|exists:sizes,id',
            'variations.*.price' => 'sometimes|numeric|min:0',
            'variations.*.quantity_per_pack' => 'sometimes|integer|min:1',
            'variations.*.stock' => 'sometimes|integer|min:0',
            'variations.*.absorbency_level' => 'sometimes|nullable|in:Light,Moderate,Heavy,Overnight',
            'variations.*.is_active' => 'sometimes|boolean',
        ];
    }
}
