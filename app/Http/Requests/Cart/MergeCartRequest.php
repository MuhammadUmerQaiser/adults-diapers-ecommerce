<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class MergeCartRequest extends FormRequest
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
            'guest_cart_token' => 'required|string|uuid',
        ];
    }

    public function messages(): array
    {
        return [
            'guest_cart_token.required' => 'Guest cart token is required.',
            'guest_cart_token.uuid' => 'Guest cart token must be a valid UUID.',
        ];
    }
}
