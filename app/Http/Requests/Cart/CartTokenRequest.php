<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class CartTokenRequest extends FormRequest
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
            'cart_token' => [auth()->check() ? 'nullable' : 'required', 'string', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
           'cart_token.required'   => 'Cart token is required for guest users.',
            'cart_token.uuid' => 'Cart token must be a valid UUID.',
        ];
    }
}
