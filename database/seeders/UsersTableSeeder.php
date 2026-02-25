<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Truncate users table
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Get role IDs
        $roles = DB::table('roles')->pluck('id', 'name')->toArray();

        // Create admin users
        DB::table('users')->insert([
            [
                'name' => 'Admin Utama',
                'email' => 'admin@kalarasa.com',
                'password' => Hash::make('admin123'),
                'role_id' => $roles['admin'],
                'points' => 0,
                'phone' => '081234567891',
                'gender' => 'Pria',
                'birth_date' => '1992-03-15',
                'email_verified_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);

        // Create regular users
        $users = [
            [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'password' => Hash::make('user123'),
                'role_id' => $roles['user'],
                'points' => 150,
                'phone' => '081234567893',
                'gender' => 'Pria',
                'birth_date' => '1995-05-20',
                'email_verified_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane.smith@example.com',
                'password' => Hash::make('user123'),
                'role_id' => $roles['user'],
                'points' => 275,
                'phone' => '081234567894',
                'gender' => 'Pria',
                'birth_date' => '1993-08-10',
                'email_verified_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@example.com',
                'password' => Hash::make('user123'),
                'role_id' => $roles['user'],
                'points' => 80,
                'phone' => '081234567895',
                'gender' => 'Pria',
                'birth_date' => '1998-11-02',
                'email_verified_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Siti Rahayu',
                'email' => 'siti.rahayu@example.com',
                'password' => Hash::make('user123'),
                'role_id' => $roles['user'],
                'points' => 420,
                'phone' => '081234567896',
                'gender' => 'Wanita',
                'birth_date' => '1991-07-25',
                'email_verified_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Ahmad Hidayat',
                'email' => 'ahmad.hidayat@example.com',
                'password' => Hash::make('user123'),
                'role_id' => $roles['user'],
                'points' => 190,
                'phone' => '081234567897',
                'gender' => 'Pria',
                'birth_date' => '1996-02-18',
                'email_verified_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Putri Wulandari',
                'email' => 'putri.wulandari@example.com',
                'password' => Hash::make('user123'),
                'role_id' => $roles['user'],
                'points' => 310,
                'phone' => '081234567898',
                'gender' => 'Wanita',
                'birth_date' => '1994-09-30',
                'email_verified_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('users')->insert($users);

    }
}