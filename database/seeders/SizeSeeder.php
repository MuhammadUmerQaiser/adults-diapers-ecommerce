<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Size;
use Illuminate\Support\Facades\DB;

class SizeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Size::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $sizes = [
            ['name' => 'Small', 'code' => 'S', 'is_active' => 1],
            ['name' => 'Medium', 'code' => 'M', 'is_active' => 1],
            ['name' => 'Large', 'code' => 'L', 'is_active' => 1],
            ['name' => 'Extra Large', 'code' => 'XL', 'is_active' => 1],
            ['name' => 'XXL', 'code' => 'XXL', 'is_active' => 1],
        ];

        foreach ($sizes as $size) {
            Size::create($size);
        }
    }
}
