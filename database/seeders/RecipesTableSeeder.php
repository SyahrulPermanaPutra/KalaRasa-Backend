<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class RecipesTableSeeder extends Seeder
{
    public function run()
    {
        $recipes = [
            ['nama' => 'Nasi Goreng Spesial', 'waktu_masak' => 20, 'region' => 'Nasional', 'deskripsi' => 'Nasi goreng dengan bumbu khas Indonesia', 'kategori' => 'Makanan Utama', 'status' => 'approved', 'avg_rating' => 4.5, 'total_ratings' => 150, 'view_count' => 5000],
            ['nama' => 'Ayam Bakar Madu', 'waktu_masak' => 45, 'region' => 'Jawa', 'deskripsi' => 'Ayam bakar dengan olesan madu', 'kategori' => 'Makanan Utama', 'status' => 'approved', 'avg_rating' => 4.7, 'total_ratings' => 200, 'view_count' => 6500],
            ['nama' => 'Gado-Gado', 'waktu_masak' => 30, 'region' => 'Jakarta', 'deskripsi' => 'Salad sayuran dengan saus kacang', 'kategori' => 'Makanan Utama', 'status' => 'approved', 'avg_rating' => 4.3, 'total_ratings' => 120, 'view_count' => 4000],
            ['nama' => 'Soto Ayam', 'waktu_masak' => 60, 'region' => 'Surabaya', 'deskripsi' => 'Sup ayam dengan kuah kuning', 'kategori' => 'Sup', 'status' => 'approved', 'avg_rating' => 4.6, 'total_ratings' => 180, 'view_count' => 5500],
            ['nama' => 'Rendang Daging', 'waktu_masak' => 180, 'region' => 'Padang', 'deskripsi' => 'Daging sapi dimasak dengan santan dan rempah', 'kategori' => 'Makanan Utama', 'status' => 'approved', 'avg_rating' => 4.9, 'total_ratings' => 300, 'view_count' => 8000],
            ['nama' => 'Pepes Ikan', 'waktu_masak' => 50, 'region' => 'Sunda', 'deskripsi' => 'Ikan dibungkus daun pisang dan dikukus', 'kategori' => 'Makanan Utama', 'status' => 'approved', 'avg_rating' => 4.4, 'total_ratings' => 100, 'view_count' => 3500],
            ['nama' => 'Gulai Kambing', 'waktu_masak' => 90, 'region' => 'Padang', 'deskripsi' => 'Kari kambing dengan kuah kental', 'kategori' => 'Makanan Utama', 'status' => 'approved', 'avg_rating' => 4.5, 'total_ratings' => 140, 'view_count' => 4500],
            ['nama' => 'Mie Goreng Jawa', 'waktu_masak' => 25, 'region' => 'Jawa', 'deskripsi' => 'Mie goreng dengan bumbu khas Jawa', 'kategori' => 'Makanan Utama', 'status' => 'approved', 'avg_rating' => 4.4, 'total_ratings' => 160, 'view_count' => 5200],
            ['nama' => 'Sayur Asem', 'waktu_masak' => 35, 'region' => 'Betawi', 'deskripsi' => 'Sup sayuran dengan rasa asam', 'kategori' => 'Sup', 'status' => 'approved', 'avg_rating' => 4.2, 'total_ratings' => 110, 'view_count' => 3800],
            ['nama' => 'Ikan Bakar', 'waktu_masak' => 40, 'region' => 'Nusantara', 'deskripsi' => 'Ikan dibakar dengan bumbu rempah', 'kategori' => 'Makanan Utama', 'status' => 'approved', 'avg_rating' => 4.6, 'total_ratings' => 190, 'view_count' => 6000],
            ['nama' => 'Tumis Kangkung', 'waktu_masak' => 15, 'region' => 'Nasional', 'deskripsi' => 'Kangkung ditumis dengan bawang', 'kategori' => 'Sayuran', 'status' => 'approved', 'avg_rating' => 4.1, 'total_ratings' => 90, 'view_count' => 3000],
            ['nama' => 'Ayam Goreng Kremes', 'waktu_masak' => 50, 'region' => 'Yogyakarta', 'deskripsi' => 'Ayam goreng dengan kremes renyah', 'kategori' => 'Makanan Utama', 'status' => 'approved', 'avg_rating' => 4.8, 'total_ratings' => 250, 'view_count' => 7000],
            ['nama' => 'Laksa Bogor', 'waktu_masak' => 45, 'region' => 'Bogor', 'deskripsi' => 'Mie dengan kuah santan pedas', 'kategori' => 'Makanan Utama', 'status' => 'approved', 'avg_rating' => 4.3, 'total_ratings' => 130, 'view_count' => 4200],
            ['nama' => 'Perkedel Kentang', 'waktu_masak' => 30, 'region' => 'Nasional', 'deskripsi' => 'Kentang goreng berbentuk bulat', 'kategori' => 'Camilan', 'status' => 'approved', 'avg_rating' => 4.2, 'total_ratings' => 100, 'view_count' => 3500],
            ['nama' => 'Semur Daging', 'waktu_masak' => 70, 'region' => 'Betawi', 'deskripsi' => 'Daging dimasak dengan kecap', 'kategori' => 'Makanan Utama', 'status' => 'approved', 'avg_rating' => 4.5, 'total_ratings' => 170, 'view_count' => 5300],
            ['nama' => 'Bakwan Jagung', 'waktu_masak' => 25, 'region' => 'Nasional', 'deskripsi' => 'Gorengan jagung dengan tepung', 'kategori' => 'Camilan', 'status' => 'approved', 'avg_rating' => 4.3, 'total_ratings' => 140, 'view_count' => 4600],
            ['nama' => 'Opor Ayam', 'waktu_masak' => 55, 'region' => 'Jawa', 'deskripsi' => 'Ayam dimasak dengan santan', 'kategori' => 'Makanan Utama', 'status' => 'approved', 'avg_rating' => 4.6, 'total_ratings' => 200, 'view_count' => 6200],
            ['nama' => 'Pecel Lele', 'waktu_masak' => 35, 'region' => 'Jawa Timur', 'deskripsi' => 'Lele goreng dengan sambal terasi', 'kategori' => 'Makanan Utama', 'status' => 'approved', 'avg_rating' => 4.4, 'total_ratings' => 160, 'view_count' => 5100],
            ['nama' => 'Karedok', 'waktu_masak' => 20, 'region' => 'Sunda', 'deskripsi' => 'Salad sayuran mentah dengan saus kacang', 'kategori' => 'Sayuran', 'status' => 'approved', 'avg_rating' => 4.2, 'total_ratings' => 95, 'view_count' => 3200],
            ['nama' => 'Sate Ayam', 'waktu_masak' => 60, 'region' => 'Madura', 'deskripsi' => 'Daging ayam tusuk dengan bumbu kacang', 'kategori' => 'Makanan Utama', 'status' => 'approved', 'avg_rating' => 4.7, 'total_ratings' => 220, 'view_count' => 6800],
        ];

        foreach ($recipes as $recipe) {
            \App\Models\Recipe::create([
                'nama' => $recipe['nama'],
                'waktu_masak' => $recipe['waktu_masak'],
                'region' => $recipe['region'],
                'deskripsi' => $recipe['deskripsi'],
                'langkah_langkah' => '1. Siapkan bahan\n2. Masak sesuai resep\n3. Sajikan',
                'kategori' => $recipe['kategori'],
                'status' => $recipe['status'],
                'created_by' => 1,
                'approved_by' => 1,
                'approved_at' => Carbon::now(),
                'avg_rating' => $recipe['avg_rating'],
                'total_ratings' => $recipe['total_ratings'],
                'view_count' => $recipe['view_count'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}