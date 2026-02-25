<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Role;

class UserQueriesTableSeeder extends Seeder
{
    public function run()
    {
        // Dapatkan role_id untuk 'user'
        $userRole = Role::where('name', 'user')->first();
        
        if (!$userRole) {
            $this->command->error('Role "user" tidak ditemukan! Jalankan RoleSeeder terlebih dahulu.');
            return;
        }
        
        // Ambil user_id berdasarkan role_id (hanya user biasa, bukan admin)
        $userIds = User::where('role_id', $userRole->id)->pluck('id')->toArray();
        
        // Jika tidak ada user dengan role 'user', fallback ke semua user
        if (empty($userIds)) {
            $this->command->warn('Tidak ada user dengan role "user" ditemukan. Mengambil semua user...');
            $userIds = User::pluck('id')->toArray();
        }
        
        // Jika masih tidak ada user, beri error
        if (empty($userIds)) {
            $this->command->error('Tidak ada user ditemukan! Jalankan UsersTableSeeder terlebih dahulu.');
            return;
        }
        
        $this->command->info('Memulai pembuatan data user queries...');
        $this->command->info('Total users tersedia: ' . count($userIds));
        
        // Data queries dengan variasi intent dan status
        $queries = [
            // Pencarian resep umum
            ['query' => 'Resep nasi goreng enak', 'intent' => 'search_recipe', 'category' => 'main'],
            ['query' => 'Resep ayam bakar', 'intent' => 'search_recipe', 'category' => 'main'],
            ['query' => 'Resep rendang daging', 'intent' => 'search_recipe', 'category' => 'main'],
            ['query' => 'Resep ikan bakar', 'intent' => 'search_recipe', 'category' => 'main'],
            ['query' => 'Resep sayur asem', 'intent' => 'search_recipe', 'category' => 'vegetable'],
            ['query' => 'Resep gado-gado', 'intent' => 'search_recipe', 'category' => 'vegetable'],
            ['query' => 'Resep pepes ikan', 'intent' => 'search_recipe', 'category' => 'fish'],
            ['query' => 'Resep mie goreng', 'intent' => 'search_recipe', 'category' => 'noodle'],
            ['query' => 'Resep opor ayam', 'intent' => 'search_recipe', 'category' => 'main'],
            ['query' => 'Resep soto ayam', 'intent' => 'search_recipe', 'category' => 'soup'],
            
            // Pencarian berdasarkan kondisi kesehatan
            ['query' => 'Masakan untuk diabetes', 'intent' => 'health_recommendation', 'health_condition' => 'diabetes'],
            ['query' => 'Makanan rendah kalori', 'intent' => 'health_recommendation', 'health_condition' => 'diet'],
            ['query' => 'Masakan sehat untuk jantung', 'intent' => 'health_recommendation', 'health_condition' => 'jantung'],
            ['query' => 'Makanan untuk ibu hamil', 'intent' => 'health_recommendation', 'health_condition' => 'pregnancy'],
            ['query' => 'Masakan tanpa santan', 'intent' => 'dietary_restriction', 'restriction' => 'no_coconut'],
            ['query' => 'Makanan untuk maag', 'intent' => 'health_recommendation', 'health_condition' => 'maag'],
            ['query' => 'Masakan rendah garam', 'intent' => 'dietary_restriction', 'restriction' => 'low_sodium'],
            ['query' => 'Makanan untuk kolesterol', 'intent' => 'health_recommendation', 'health_condition' => 'kolesterol'],
            ['query' => 'Masakan untuk asam urat', 'intent' => 'health_recommendation', 'health_condition' => 'asam_urat'],
            ['query' => 'Makanan untuk hipertensi', 'intent' => 'health_recommendation', 'health_condition' => 'hipertensi'],
            
            // Query dengan status yang berbeda
            ['query' => 'Resep apa ya yang enak?', 'intent' => null, 'status' => 'fallback', 'category' => 'general'],
            ['query' => 'Bisa rekomendasi masakan?', 'intent' => null, 'status' => 'clarification', 'category' => 'general'],
            ['query' => 'Cari resep simple', 'intent' => 'search_recipe', 'status' => 'ok', 'difficulty' => 'easy'],
            ['query' => 'Resep masakan padang', 'intent' => 'search_recipe', 'status' => 'ok', 'region' => 'padang'],
            ['query' => 'Resep masakan jawa', 'intent' => 'search_recipe', 'status' => 'ok', 'region' => 'jawa'],
            ['query' => 'Makanan cepat saji', 'intent' => 'search_recipe', 'status' => 'ok', 'time' => 'quick'],
            ['query' => 'Resep untuk 4 orang', 'intent' => 'search_recipe', 'status' => 'ok', 'portion' => '4'],
            ['query' => 'Resep dengan bahan telur', 'intent' => 'ingredient_search', 'status' => 'ok', 'ingredient' => 'telur'],
            ['query' => 'Resep tanpa msg', 'intent' => 'dietary_restriction', 'status' => 'ok', 'restriction' => 'no_msg'],
            ['query' => 'Makanan untuk anak', 'intent' => 'search_recipe', 'status' => 'ok', 'audience' => 'kids'],
        ];

        $userQueries = [];
        $totalQueries = count($queries);
        
        for ($i = 0; $i < $totalQueries; $i++) {
            // Pilih user_id secara acak dari array yang valid
            $randomUserId = $userIds[array_rand($userIds)];
            
            // Set default values
            $queryData = $queries[$i];
            $status = $queryData['status'] ?? 'ok';
            $intent = $queryData['intent'] ?? null;
            $confidence = $this->generateConfidence($status, $intent);
            
            // Generate entities berdasarkan tipe query
            $entities = $this->generateEntities($queryData);
            
            // Tentukan created_at (semakin ke belakang semakin lama)
            $createdAt = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59));
            
            $userQueries[] = [
                'user_id' => $randomUserId,
                'query_text' => $queryData['query'],
                'intent' => $intent,
                'confidence' => $confidence,
                'status' => $status,
                'entities' => json_encode($entities),
                'created_at' => $createdAt,
                'updated_at' => $createdAt->copy()->addMinutes(rand(1, 60)),
            ];
        }
        
        // Insert data
        \App\Models\UserQuery::insert($userQueries);
        
        $this->command->info('Berhasil membuat ' . count($userQueries) . ' data user queries!');
        
        // Tampilkan statistik
        $statusStats = [
            'ok' => count(array_filter($userQueries, fn($q) => $q['status'] === 'ok')),
            'fallback' => count(array_filter($userQueries, fn($q) => $q['status'] === 'fallback')),
            'clarification' => count(array_filter($userQueries, fn($q) => $q['status'] === 'clarification')),
        ];
        
        $intentStats = [];
        foreach ($userQueries as $query) {
            if ($query['intent']) {
                $intentStats[$query['intent']] = ($intentStats[$query['intent']] ?? 0) + 1;
            }
        }
        
        $this->command->table(
            ['Status', 'Jumlah'],
            [
                ['ok', $statusStats['ok']],
                ['fallback', $statusStats['fallback']],
                ['clarification', $statusStats['clarification']],
            ]
        );
        
        $this->command->info('Distribusi Intent:');
        foreach ($intentStats as $intent => $count) {
            $this->command->line("- {$intent}: {$count} query");
        }
        
        $this->command->info('User queries berhasil dibuat untuk ' . count(array_unique(array_column($userQueries, 'user_id'))) . ' user unik.');
    }
    
    /**
     * Generate confidence score berdasarkan status dan intent
     */
    private function generateConfidence($status, $intent)
    {
        if ($status === 'ok' && $intent) {
            // OK query dengan intent jelas: confidence tinggi
            return rand(75, 99) / 100;
        } elseif ($status === 'fallback') {
            // Fallback: confidence rendah karena tidak dipahami
            return rand(30, 50) / 100;
        } elseif ($status === 'clarification') {
            // Butuh klarifikasi: confidence menengah
            return rand(40, 65) / 100;
        } else {
            // Lainnya
            return rand(50, 85) / 100;
        }
    }
    
    /**
     * Generate entities berdasarkan data query
     */
    private function generateEntities($queryData)
    {
        $entities = [];
        
        // Mapping kategori
        if (isset($queryData['category'])) {
            $entities['category'] = $queryData['category'];
        }
        
        // Mapping health condition
        if (isset($queryData['health_condition'])) {
            $entities['health_condition'] = $queryData['health_condition'];
        }
        
        // Mapping dietary restriction
        if (isset($queryData['restriction'])) {
            $entities['dietary_restriction'] = $queryData['restriction'];
        }
        
        // Mapping region
        if (isset($queryData['region'])) {
            $entities['region'] = $queryData['region'];
        }
        
        // Mapping ingredient
        if (isset($queryData['ingredient'])) {
            $entities['ingredient'] = $queryData['ingredient'];
        }
        
        // Add timestamp
        $entities['query_time'] = Carbon::now()->format('H:i:s');
        
        // Add random metadata untuk variasi
        if (rand(0, 1)) {
            $entities['device'] = rand(0, 1) ? 'mobile' : 'desktop';
        }
        
        if (rand(0, 1)) {
            $entities['language'] = 'id';
        }
        
        return $entities;
    }
}