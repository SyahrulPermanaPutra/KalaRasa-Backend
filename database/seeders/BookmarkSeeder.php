<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookmarkSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('bookmarks')->insert([
            ['id' => 1, 'user_id' => 1, 'recipe_id' => 2, 'created_at' => '2026-02-28 19:41:03', 'updated_at' => '2026-02-28 19:41:03'],
            ['id' => 2, 'user_id' => 1, 'recipe_id' => 4, 'created_at' => '2026-02-28 19:41:03', 'updated_at' => '2026-02-28 19:41:03'],
            ['id' => 3, 'user_id' => 1, 'recipe_id' => 5, 'created_at' => '2026-02-28 19:41:03', 'updated_at' => '2026-02-28 19:41:03'],
        ]);
    }
}
