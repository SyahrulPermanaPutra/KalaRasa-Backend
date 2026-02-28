<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RecipeSeeder extends Seeder
{
    public function run(): void
    {
        // [id, nama, waktu_masak, region, deskripsi, kategori, avg_rating, total_ratings, view_count]
        $data = [
            [1,  'Nasi Goreng Spesial', 20,  'Nasional',    'Nasi goreng dengan bumbu khas Indonesia',          'Makanan Utama', 4.50, 150, 5000],
            [2,  'Ayam Bakar Madu',     45,  'Jawa',        'Ayam bakar dengan olesan madu',                    'Makanan Utama', 4.70, 200, 6500],
            [3,  'Gado-Gado',           30,  'Jakarta',     'Salad sayuran dengan saus kacang',                 'Makanan Utama', 4.30, 120, 4000],
            [4,  'Soto Ayam',           60,  'Surabaya',    'Sup ayam dengan kuah kuning',                      'Sup',           4.60, 180, 5500],
            [5,  'Rendang Daging',      180, 'Padang',      'Daging sapi dimasak dengan santan dan rempah',     'Makanan Utama', 4.90, 300, 8000],
            [6,  'Pepes Ikan',          50,  'Sunda',       'Ikan dibungkus daun pisang dan dikukus',           'Makanan Utama', 4.40, 100, 3500],
            [7,  'Gulai Kambing',       90,  'Padang',      'Kari kambing dengan kuah kental',                  'Makanan Utama', 4.50, 140, 4500],
            [8,  'Mie Goreng Jawa',     25,  'Jawa',        'Mie goreng dengan bumbu khas Jawa',                'Makanan Utama', 4.40, 160, 5200],
            [9,  'Sayur Asem',          35,  'Betawi',      'Sup sayuran dengan rasa asam',                     'Sup',           4.20, 110, 3800],
            [10, 'Ikan Bakar',          40,  'Nusantara',   'Ikan dibakar dengan bumbu rempah',                 'Makanan Utama', 4.60, 190, 6000],
            [11, 'Tumis Kangkung',      15,  'Nasional',    'Kangkung ditumis dengan bawang',                   'Sayuran',       4.10, 90,  3000],
            [12, 'Ayam Goreng Kremes',  50,  'Yogyakarta',  'Ayam goreng dengan kremes renyah',                 'Makanan Utama', 4.80, 250, 7000],
            [13, 'Laksa Bogor',         45,  'Bogor',       'Mie dengan kuah santan pedas',                     'Makanan Utama', 4.30, 130, 4200],
            [14, 'Perkedel Kentang',    30,  'Nasional',    'Kentang goreng berbentuk bulat',                   'Camilan',       4.20, 100, 3500],
            [15, 'Semur Daging',        70,  'Betawi',      'Daging dimasak dengan kecap',                      'Makanan Utama', 4.50, 170, 5300],
            [16, 'Bakwan Jagung',       25,  'Nasional',    'Gorengan jagung dengan tepung',                    'Camilan',       4.30, 140, 4600],
            [17, 'Opor Ayam',           55,  'Jawa',        'Ayam dimasak dengan santan',                       'Makanan Utama', 4.60, 200, 6200],
            [18, 'Pecel Lele',          35,  'Jawa Timur',  'Lele goreng dengan sambal terasi',                 'Makanan Utama', 4.40, 160, 5100],
            [19, 'Karedok',             20,  'Sunda',       'Salad sayuran mentah dengan saus kacang',          'Sayuran',       4.20, 95,  3200],
            [20, 'Sate Ayam',           60,  'Madura',      'Daging ayam tusuk dengan bumbu kacang',            'Makanan Utama', 4.70, 220, 6800],
        ];

        $langkah = '1. Siapkan bahan\\n2. Masak sesuai resep\\n3. Sajikan';

        foreach ($data as [$id, $nama, $waktu, $region, $deskripsi, $kategori, $avg, $total, $views]) {
            DB::table('recipes')->insert([
                'id'              => $id,
                'nama'            => $nama,
                'waktu_masak'     => $waktu,
                'region'          => $region,
                'deskripsi'       => $deskripsi,
                'langkah_langkah' => $langkah,
                'gambar'          => null,
                'kategori'        => $kategori,
                'status'          => 'approved',
                'created_by'      => 1,
                'approved_by'     => 1,
                'approved_at'     => '2026-02-28 19:41:02',
                'rejection_reason'=> null,
                'avg_rating'      => $avg,
                'total_ratings'   => $total,
                'view_count'      => $views,
                'created_at'      => '2026-02-28 19:41:02',
                'updated_at'      => '2026-02-28 19:41:02',
            ]);
        }
    }
}
