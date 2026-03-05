<?php

namespace App\Models;

use App\Http\Resources\ShippingMethodResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

     public function getAllShippingMethods()
    {
        try {
            $shippingMethods = $this;
            $shippingMethods = $shippingMethods->get();
            $collection = ShippingMethodResource::collection($shippingMethods);
            return api_success($collection, 'Shipping methods fetched successfully', 200);
        }
        catch (\Exception $e) {
            return api_error('Something went wrong while fetching data.', 500, $e->getMessage());
        }
    }
}
