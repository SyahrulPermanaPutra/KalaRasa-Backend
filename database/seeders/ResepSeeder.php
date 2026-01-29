<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Resep;
use App\Models\User;

class ResepSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        $reseps = [
            [
                'nama_resep' => 'Nasi Goreng Spesial',
                'deskripsi' => 'Nasi goreng dengan bumbu spesial yang gurih dan lezat',
                'bahan_makanan' => [
                    ['nama' => 'Nasi putih', 'jumlah' => '500', 'satuan' => 'gram'],
                    ['nama' => 'Telur ayam', 'jumlah' => '2', 'satuan' => 'butir'],
                    ['nama' => 'Bawang merah', 'jumlah' => '5', 'satuan' => 'siung'],
                    ['nama' => 'Bawang putih', 'jumlah' => '3', 'satuan' => 'siung'],
                    ['nama' => 'Kecap manis', 'jumlah' => '3', 'satuan' => 'sdm'],
                    ['nama' => 'Minyak goreng', 'jumlah' => '3', 'satuan' => 'sdm'],
                    ['nama' => 'Garam', 'jumlah' => '1', 'satuan' => 'sdt'],
                    ['nama' => 'Ayam fillet', 'jumlah' => '100', 'satuan' => 'gram'],
                ],
                'cara_memasak' => "1. Panaskan minyak, tumis bawang merah dan bawang putih hingga harum\n2. Masukkan ayam, masak hingga berubah warna\n3. Masukkan telur, orak-arik\n4. Masukkan nasi, aduk rata\n5. Tambahkan kecap manis dan garam, aduk hingga rata\n6. Masak hingga matang dan bumbu meresap\n7. Sajikan dengan kerupuk dan acar",
                'porsi' => 2,
                'waktu_memasak' => 20,
                'tingkat_kesulitan' => 'mudah',
                'kategori' => 'makanan_utama',
                'status' => 'approved',
                'created_by' => $admin->id,
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ],
            [
                'nama_resep' => 'Soto Ayam',
                'deskripsi' => 'Soto ayam khas Jawa Timur yang hangat dan nikmat',
                'bahan_makanan' => [
                    ['nama' => 'Ayam kampung', 'jumlah' => '500', 'satuan' => 'gram'],
                    ['nama' => 'Kunyit', 'jumlah' => '2', 'satuan' => 'cm'],
                    ['nama' => 'Jahe', 'jumlah' => '2', 'satuan' => 'cm'],
                    ['nama' => 'Serai', 'jumlah' => '2', 'satuan' => 'batang'],
                    ['nama' => 'Daun jeruk', 'jumlah' => '3', 'satuan' => 'lembar'],
                    ['nama' => 'Bawang merah', 'jumlah' => '8', 'satuan' => 'siung'],
                    ['nama' => 'Bawang putih', 'jumlah' => '5', 'satuan' => 'siung'],
                    ['nama' => 'Kemiri', 'jumlah' => '3', 'satuan' => 'butir'],
                    ['nama' => 'Air', 'jumlah' => '1.5', 'satuan' => 'liter'],
                ],
                'cara_memasak' => "1. Rebus ayam dengan kunyit, jahe, dan serai hingga empuk\n2. Angkat ayam, suwir-suwir dagingnya\n3. Haluskan bumbu: bawang merah, bawang putih, kemiri\n4. Tumis bumbu halus hingga harum\n5. Masukkan bumbu tumis ke dalam kaldu ayam\n6. Tambahkan daun jeruk, masak hingga mendidih\n7. Sajikan dengan suwiran ayam, tauge, telur, dan pelengkap lainnya",
                'porsi' => 4,
                'waktu_memasak' => 60,
                'tingkat_kesulitan' => 'sedang',
                'kategori' => 'makanan_utama',
                'status' => 'approved',
                'created_by' => $admin->id,
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ],
            [
                'nama_resep' => 'Rawon Daging Sapi',
                'deskripsi' => 'Rawon khas Jawa Timur dengan kuah hitam yang kaya rempah',
                'bahan_makanan' => [
                    ['nama' => 'Daging sapi', 'jumlah' => '500', 'satuan' => 'gram'],
                    ['nama' => 'Kluwek', 'jumlah' => '5', 'satuan' => 'butir'],
                    ['nama' => 'Kemiri', 'jumlah' => '5', 'satuan' => 'butir'],
                    ['nama' => 'Bawang merah', 'jumlah' => '10', 'satuan' => 'siung'],
                    ['nama' => 'Bawang putih', 'jumlah' => '6', 'satuan' => 'siung'],
                    ['nama' => 'Jahe', 'jumlah' => '3', 'satuan' => 'cm'],
                    ['nama' => 'Lengkuas', 'jumlah' => '3', 'satuan' => 'cm'],
                    ['nama' => 'Serai', 'jumlah' => '2', 'satuan' => 'batang'],
                    ['nama' => 'Daun jeruk', 'jumlah' => '4', 'satuan' => 'lembar'],
                    ['nama' => 'Daun salam', 'jumlah' => '3', 'satuan' => 'lembar'],
                ],
                'cara_memasak' => "1. Rebus daging sapi hingga empuk\n2. Haluskan bumbu: kluwek, kemiri, bawang merah, bawang putih, jahe\n3. Tumis bumbu halus hingga harum\n4. Masukkan bumbu tumis ke dalam kaldu daging\n5. Tambahkan lengkuas, serai, daun jeruk, dan daun salam\n6. Masak hingga bumbu meresap sempurna\n7. Sajikan dengan nasi, tauge, telur asin, dan sambal",
                'porsi' => 5,
                'waktu_memasak' => 90,
                'tingkat_kesulitan' => 'sulit',
                'kategori' => 'makanan_utama',
                'status' => 'approved',
                'created_by' => $admin->id,
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ],
            [
                'nama_resep' => 'Pecel Lele',
                'deskripsi' => 'Lele goreng dengan sambal pecel yang pedas',
                'bahan_makanan' => [
                    ['nama' => 'Ikan lele', 'jumlah' => '4', 'satuan' => 'ekor'],
                    ['nama' => 'Kunyit', 'jumlah' => '2', 'satuan' => 'cm'],
                    ['nama' => 'Bawang putih', 'jumlah' => '3', 'satuan' => 'siung'],
                    ['nama' => 'Garam', 'jumlah' => '1', 'satuan' => 'sdt'],
                    ['nama' => 'Kacang tanah', 'jumlah' => '200', 'satuan' => 'gram'],
                    ['nama' => 'Cabai merah', 'jumlah' => '10', 'satuan' => 'buah'],
                    ['nama' => 'Gula merah', 'jumlah' => '50', 'satuan' => 'gram'],
                    ['nama' => 'Asam jawa', 'jumlah' => '1', 'satuan' => 'sdm'],
                ],
                'cara_memasak' => "1. Haluskan kunyit dan bawang putih, campur dengan garam\n2. Lumuri ikan lele dengan bumbu, diamkan 30 menit\n3. Goreng lele hingga kering dan garing\n4. Untuk sambal: sangrai kacang tanah hingga matang\n5. Haluskan kacang dengan cabai, gula merah, dan asam jawa\n6. Tambahkan air secukupnya hingga sambal tidak terlalu kental\n7. Sajikan lele goreng dengan sambal pecel dan lalapan",
                'porsi' => 4,
                'waktu_memasak' => 45,
                'tingkat_kesulitan' => 'mudah',
                'kategori' => 'makanan_utama',
                'status' => 'approved',
                'created_by' => $admin->id,
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ],
            [
                'nama_resep' => 'Es Campur Segar',
                'deskripsi' => 'Minuman es campur dengan berbagai topping segar',
                'bahan_makanan' => [
                    ['nama' => 'Cincau hitam', 'jumlah' => '100', 'satuan' => 'gram'],
                    ['nama' => 'Kolang-kaling', 'jumlah' => '100', 'satuan' => 'gram'],
                    ['nama' => 'Nangka', 'jumlah' => '100', 'satuan' => 'gram'],
                    ['nama' => 'Alpukat', 'jumlah' => '1', 'satuan' => 'buah'],
                    ['nama' => 'Sirup cocopandan', 'jumlah' => '5', 'satuan' => 'sdm'],
                    ['nama' => 'Susu kental manis', 'jumlah' => '5', 'satuan' => 'sdm'],
                    ['nama' => 'Es batu', 'jumlah' => '500', 'satuan' => 'gram'],
                ],
                'cara_memasak' => "1. Potong-potong cincau hitam, kolang-kaling, nangka, dan alpukat\n2. Siapkan gelas saji\n3. Masukkan semua potongan buah dan bahan ke dalam gelas\n4. Tambahkan sirup cocopandan\n5. Tuang susu kental manis\n6. Tambahkan es batu secukupnya\n7. Aduk rata dan sajikan segera",
                'porsi' => 2,
                'waktu_memasak' => 15,
                'tingkat_kesulitan' => 'mudah',
                'kategori' => 'minuman',
                'status' => 'approved',
                'created_by' => $admin->id,
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ],
            [
                'nama_resep' => 'Lumpia Semarang',
                'deskripsi' => 'Lumpia basah isi rebung dan telur',
                'bahan_makanan' => [
                    ['nama' => 'Kulit lumpia', 'jumlah' => '10', 'satuan' => 'lembar'],
                    ['nama' => 'Rebung', 'jumlah' => '200', 'satuan' => 'gram'],
                    ['nama' => 'Udang', 'jumlah' => '100', 'satuan' => 'gram'],
                    ['nama' => 'Ayam fillet', 'jumlah' => '100', 'satuan' => 'gram'],
                    ['nama' => 'Telur ayam', 'jumlah' => '3', 'satuan' => 'butir'],
                    ['nama' => 'Bawang putih', 'jumlah' => '4', 'satuan' => 'siung'],
                    ['nama' => 'Wortel', 'jumlah' => '1', 'satuan' => 'buah'],
                    ['nama' => 'Daun bawang', 'jumlah' => '2', 'satuan' => 'batang'],
                ],
                'cara_memasak' => "1. Tumis bawang putih hingga harum\n2. Masukkan ayam dan udang, masak hingga matang\n3. Tambahkan rebung dan wortel yang sudah dipotong\n4. Buat dadar telur tipis, potong memanjang\n5. Ambil kulit lumpia, isi dengan tumisan dan telur\n6. Gulung dan lipat ujungnya\n7. Sajikan dengan saus sambal",
                'porsi' => 10,
                'waktu_memasak' => 40,
                'tingkat_kesulitan' => 'sedang',
                'kategori' => 'makanan_ringan',
                'status' => 'pending',
                'created_by' => $admin->id,
            ],
        ];

        foreach ($reseps as $resep) {
            Resep::create($resep);
        }
    }
}
