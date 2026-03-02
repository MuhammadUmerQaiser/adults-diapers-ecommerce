<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Size;
use App\Models\Role;
use App\Models\Category;
use Illuminate\Http\Request;

class GlobalController extends Controller
{
    protected $size, $role, $category;

    public function __construct(Size $size, Role $role, Category $category)
    {
        $this->size = $size;
        $this->role = $role;
        $this->category = $category;
    }

    public function getAllSizes(Request $request)
    {
        return $this->size->getAllSizes();
    }

    public function getAllRoles(Request $request)
    {
        return $this->role->getAllRoles();
    }

    public function getAllCategories(Request $request)
    {
        return $this->category->getAllCategories();
    }
}
