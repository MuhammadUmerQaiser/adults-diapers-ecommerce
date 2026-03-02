<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Role::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $roles = [
            [
                'name' => 'Admin'
            ],
            [
                'name' => 'Manager'
            ],
            [
                'name' => 'Customer'
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }

    }
}
