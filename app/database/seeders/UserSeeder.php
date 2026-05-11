<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@flextft.com'],
            [
                'nickname' => 'admin',
                'password' => Hash::make('password'),
                'role' => 'a',
            ]
        );

        User::updateOrCreate(
            ['email' => 'user@flextft.com'],
            [
                'nickname' => 'user',
                'password' => Hash::make('password'),
                'role' => 'u',
            ]
        );
    }
}
