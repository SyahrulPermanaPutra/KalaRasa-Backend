<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
        {
        \DB::table('bookmarks')->delete();  // Hapus data bookmarks dulu
        \DB::table('users')->delete();      // Baru hapus users
        
        // Atau nonaktifkan sementara foreign key checks
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \DB::table('users')->truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Insert data contoh
        $users = [
            [
                'name' => 'Admin Utama',
                'email' => 'admin@example.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password123'),
                'points' => 1000,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'phone' => '081234567890',
                'gender' => 'Pria',
                'birth_date' => '1990-01-01',
                'role' => 'admin',
                'remember_token' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password123'),
                'points' => 500,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'phone' => '081234567891',
                'gender' => 'Pria',
                'birth_date' => '1995-05-15',
                'role' => 'user',
                'remember_token' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password123'),
                'points' => 750,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'phone' => '081234567892',
                'gender' => 'Wanita',
                'birth_date' => '1993-08-20',
                'role' => 'user',
                'remember_token' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Bob Johnson',
                'email' => 'bob@example.com',
                'email_verified_at' => null,
                'password' => Hash::make('password123'),
                'points' => 200,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'phone' => '081234567893',
                'gender' => 'Pria',
                'birth_date' => '1998-12-10',
                'role' => 'user',
                'remember_token' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Alice Williams',
                'email' => 'alice@example.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password123'),
                'points' => 300,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'phone' => '081234567894',
                'gender' => 'Wanita',
                'birth_date' => '1996-03-25',
                'role' => 'user',
                'remember_token' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        // Insert semua data
        foreach ($users as $user) {
            User::create($user);
        }

        // Atau bisa juga menggunakan insert langsung
        // User::insert($users);
    }
}