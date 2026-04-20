<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'username' => $this->username ?? null,
            'email' => $this->email,
            'dob' => $this->dob ?? null,
            'phone' => $this->phone ?? null,
            'gender' => $this->gender,
            'social_id' => $this->social_id ?? null,
            'social_type' => $this->social_type ?? null,
            'role' => $this->role ? new RoleResource($this->role) : null,
            'email_verifed_at' => $this->email_verified_at ? $this->email_verified_at->toDateTimeString() : null,
            'image' => $this->image ? asset('images/users/' . $this->image) : null,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
