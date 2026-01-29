<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin
        User::create([
            'name' => 'Admin JTV',
            'email' => 'admin@jtv.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'phone' => '081234567890',
            'address' => 'Surabaya, Jawa Timur',
        ]);

        // Create Regular Users
        User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'phone' => '081234567891',
            'address' => 'Surabaya, Jawa Timur',
        ]);

        User::create([
            'name' => 'Siti Nurhaliza',
            'email' => 'siti@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'phone' => '081234567892',
            'address' => 'Sidoarjo, Jawa Timur',
        ]);

        User::create([
            'name' => 'Andi Wijaya',
            'email' => 'andi@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'phone' => '081234567893',
            'address' => 'Gresik, Jawa Timur',
        ]);
    }
}
