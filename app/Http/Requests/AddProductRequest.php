<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddProductRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'brand' => 'nullable|string|max:100',
            'category_id' => 'nullable|exists:categories,id',
            
            'images' => 'required|array|min:1',
            'images.*' => 'required|image|mimes:jpeg,jpg,png,webp|max:2048',
            'featured_image_index' => 'nullable|integer|min:0',

            'variations' => 'required|array|min:1',
            'variations.*.size_id' => 'required|exists:sizes,id',
            'variations.*.price' => 'required|numeric|min:0',
            'variations.*.quantity_per_pack' => 'required|integer|min:1',
            'variations.*.stock' => 'required|integer|min:0',
            'variations.*.absorbency_level' => 'nullable|in:Light,Moderate,Heavy,Overnight',
            'variations.*.is_active' => 'nullable|boolean',
        ];
    }
}
