<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->insert([
            [
                'id' => 0,
                'name' => 'admin',
                'description' => 'Administrator',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 1,
                'name' => 'user',
                'description' => 'User Biasa',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
