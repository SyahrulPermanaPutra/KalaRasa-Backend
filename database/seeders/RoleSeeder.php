<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'admin', 'display_name' => 'Admin', 'created_at' => '2026-02-28 19:41:01', 'updated_at' => '2026-02-28 19:41:01'],
            ['id' => 2, 'name' => 'user',  'display_name' => 'User',  'created_at' => '2026-02-28 19:41:01', 'updated_at' => '2026-02-28 19:41:01'],
        ]);
    }
}
