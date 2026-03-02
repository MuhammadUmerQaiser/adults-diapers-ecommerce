<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\CategoryResource;

class Category extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function getAllCategories()
    {
        try {
            $categories = $this;
            $categories = $categories->get();
            $collection = CategoryResource::collection($categories);
            return api_success($collection, 'Categories fetched successfully', 200);
        }
        catch (\Exception $e) {
            return api_error('Something went wrong while fetching data.', 500, $e->getMessage());
        }
    }
}
