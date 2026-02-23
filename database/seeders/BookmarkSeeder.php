<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Bookmark;
use App\Models\User;
use App\Models\Recipe;

class BookmarkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil semua user dan recipe
        $users = User::all();
        $recipes = Recipe::all();

        if ($users->isEmpty() || $recipes->isEmpty()) {
            $this->command->info('Tidak ada user atau recipe ditemukan. Pastikan untuk menjalankan UserSeeder dan RecipeSeeder terlebih dahulu.');
            return;
        }

        // Buat beberapa bookmark untuk setiap user secara acak
        foreach ($users as $user) {
            // Setiap user akan mem-bookmark 3-8 resep secara acak
            $numberOfBookmarks = rand(3, min(8, $recipes->count()));
            
            // Ambil resep secara acak untuk di-bookmark
            $randomRecipes = $recipes->random($numberOfBookmarks);
            
            foreach ($randomRecipes as $recipe) {
                Bookmark::create([
                    'user_id' => $user->id,
                    'recipe_id' => $recipe->id,
                ]);
            }
        }

        // Alternatif: Buat bookmark dengan data spesifik
        $this->createSpecificBookmarks();
    }

    /**
     * Membuat bookmark spesifik untuk user tertentu
     */
    private function createSpecificBookmarks(): void
    {
        // Cari user dengan email tertentu (jika ada)
        $user1 = User::where('email', 'user@example.com')->first();
        $user2 = User::where('email', 'admin@example.com')->first();

        if ($user1) {
            // Bookmark untuk resep dengan ID tertentu
            $bookmarkData = [
                ['user_id' => $user1->id, 'recipe_id' => 1],
                ['user_id' => $user1->id, 'recipe_id' => 3],
                ['user_id' => $user1->id, 'recipe_id' => 5],
            ];

            foreach ($bookmarkData as $data) {
                // Cek apakah bookmark sudah ada
                $exists = Bookmark::where('user_id', $data['user_id'])
                    ->where('recipe_id', $data['recipe_id'])
                    ->exists();

                if (!$exists) {
                    Bookmark::create($data);
                }
            }
        }

        if ($user2) {
            $bookmarkData = [
                ['user_id' => $user2->id, 'recipe_id' => 2],
                ['user_id' => $user2->id, 'recipe_id' => 4],
                ['user_id' => $user2->id, 'recipe_id' => 6],
                ['user_id' => $user2->id, 'recipe_id' => 8],
            ];

            foreach ($bookmarkData as $data) {
                $exists = Bookmark::where('user_id', $data['user_id'])
                    ->where('recipe_id', $data['recipe_id'])
                    ->exists();

                if (!$exists) {
                    Bookmark::create($data);
                }
            }
        }
    }
}