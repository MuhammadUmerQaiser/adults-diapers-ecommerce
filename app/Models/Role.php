<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\RoleResource;

class Role extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function getAllRoles()
    {
        try {
            $roles = $this;
            $roles = $roles->get();
            $collection = RoleResource::collection($roles);
            return api_success($collection, 'Roles fetched successfully', 200);
        }
        catch (\Exception $e) {
            return api_error('Something went wrong while fetching data.', 500, $e->getMessage());
        }
    }

}
