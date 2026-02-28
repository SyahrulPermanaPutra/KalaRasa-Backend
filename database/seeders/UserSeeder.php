<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Seeder user default
        User::create([
            'name' => 'Default User',
            'email' => 'user1@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'phone' => '08123456789',
            'sso_id' => null,
            'role_id' => 1, // pastikan role dengan id 1 sudah ada di tabel roles
            'points' => 100,
            'gender' => 'Pria',
            'birth_date' => '1990-01-01',
            // 'avatar' => null, // sudah di-drop
            'remember_token' => null,
        ]);
        // Tambah user lain jika perlu
    }
}
