<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        User::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $users = [
            [
                'firstname' => 'Muhammad',
                'lastname' => 'Umer',
                'email' => 'admin@gmail.com',
                'username' => 'muhammad.umer',
                'password' => Hash::make('Admin@123'),
                'role_id' => 1,
                'email_verified_at' => now()
            ]
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
