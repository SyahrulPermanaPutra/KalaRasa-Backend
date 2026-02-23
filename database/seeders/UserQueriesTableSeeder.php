<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\User;

class UserQueriesTableSeeder extends Seeder
{
    public function run()
    {
        // Ambil semua user_id yang valid (khusus role user)
        $userIds = User::where('role', 'user')->pluck('id')->toArray();
        
        // Atau jika ingin termasuk admin:
        // $userIds = User::pluck('id')->toArray();
        
        if (empty($userIds)) {
            $this->command->error('Tidak ada user ditemukan! Jalankan UserSeeder dulu.');
            return;
        }
        
        $queries = [
            'Resep nasi goreng enak',
            'Masakan untuk diabetes',
            'Resep ayam bakar',
            'Makanan rendah kalori',
            'Resep soto ayam',
            'Masakan sehat untuk jantung',
            'Resep rendang daging',
            'Makanan untuk ibu hamil',
            'Resep ikan bakar',
            'Masakan tanpa santan',
            'Resep sayur asem',
            'Makanan untuk maag',
            'Resep gado-gado',
            'Masakan rendah garam',
            'Resep pepes ikan',
            'Makanan untuk kolesterol',
            'Resep mie goreng',
            'Masakan untuk asam urat',
            'Resep opor ayam',
            'Makanan untuk hipertensi',
        ];

        $totalQueries = count($queries);
        $totalUsers = count($userIds);
        
        for ($i = 0; $i < $totalQueries; $i++) {
            // Pilih user_id secara acak dari array yang valid
            $randomUserId = $userIds[array_rand($userIds)];
            
            \App\Models\UserQuery::create([
                'user_id' => $randomUserId,
                'query_text' => $queries[$i],
                'intent' => 'search_recipe',
                'confidence' => rand(70, 99) / 100,
                'status' => 'ok',
                'entities' => json_encode(['type' => 'recipe', 'category' => 'main']),
                'created_at' => Carbon::now()->subDays($i),
                'updated_at' => Carbon::now(),
            ]);
        }
        
        $this->command->info("Berhasil membuat {$totalQueries} data user queries untuk " . $totalUsers . " user!");
    }
}