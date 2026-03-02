<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Category::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $categories = [
            [
                'name' => 'Adult Diapers',
                'slug' => 'adult-diapers',
                'description' => 'Premium quality adult diapers',
                'is_active' => 1,
            ]
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
