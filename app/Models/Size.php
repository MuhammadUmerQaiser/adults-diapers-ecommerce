<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\SizeResource;

class Size extends Model
{
    use HasFactory;
    protected $guarded = ['id'];


    public function getAllSizes()
    {
        try {
            $sizes = $this;
            $sizes = $sizes->get();
            $collection = SizeResource::collection($sizes);
            return api_success($collection, 'Sizes fetched successfully', 200);
        }
        catch (\Exception $e) {
            return api_error('Something went wrong while fetching data.', 500, $e->getMessage());
        }
    }

}
