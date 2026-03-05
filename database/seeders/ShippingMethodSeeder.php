<?php

namespace Database\Seeders;

use App\Models\ShippingMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShippingMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ShippingMethod::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $methods = [
            [
                'name'          => 'Standard Shipping',
                'price'         => 5.99,
                'business_days' => 5,
                'is_active'     => 1,
            ],
            [
                'name'          => 'Express Shipping',
                'price'         => 12.99,
                'business_days' => 2,
                'is_active'     => 1,
            ],
            [
                'name'          => 'Overnight Shipping',
                'price'         => 24.99,
                'business_days' => 1,
                'is_active'     => 1,
            ],
            [
                'name'          => 'Free Shipping',
                'price'         => 0.00,
                'business_days' => 7,
                'is_active'     => 1,
            ],
        ];

        foreach ($methods as $method) {
            ShippingMethod::create($method);
        }
    }
}
