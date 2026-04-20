<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('id');
        return [
            'firstname' => 'sometimes|string|max:100',
            'lastname' => 'sometimes|string|max:100',
            'username' => "sometimes|string|unique:users,username,{$userId}",
            'email' => "sometimes|email|unique:users,email,{$userId}",
            'phone' => 'sometimes|nullable|string|max:20',
            'dob' => 'sometimes|nullable|date',
            'gender' => 'sometimes|nullable|in:male,female,other',
            'role_id' => 'sometimes|exists:roles,id',
        ];
    }
}
