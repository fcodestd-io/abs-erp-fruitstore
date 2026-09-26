<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'username' => 'owner',
            'password' => Hash::make('password'),
            'role' => 'owner',
        ]);

        User::create([
            'username' => 'admin',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
    }
}