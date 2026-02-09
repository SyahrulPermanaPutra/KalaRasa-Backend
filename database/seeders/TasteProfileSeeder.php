<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TasteProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tasteProfiles = [
            'pedas',
            'manis',
            'asin',
            'asam',
            'gurih',
            'segar',
        ];

        foreach ($tasteProfiles as $taste) {
            DB::table('taste_profiles')->insert([
                'nama' => $taste,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}