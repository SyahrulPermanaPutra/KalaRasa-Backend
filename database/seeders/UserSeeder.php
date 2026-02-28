<?php
<<<<<<< HEAD

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
=======
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
>>>>>>> 318de4113e9ec9047d5232e66bad19e1b8c840a7

class UserSeeder extends Seeder
{
    public function run(): void
    {
<<<<<<< HEAD
        DB::table('users')->insert([
            [
                'id'                => 1,
                'sso_id'            => null,
                'name'              => 'Default User',
                'email'             => 'user1@example.com',
                'points'            => 100,
                'email_verified_at' => '2026-02-28 19:41:01',
                'password'          => '$2y$12$sJrKcXd7dXwYctuMq6l4Q.mXbS7XxxiWVuZxBY88Vml8YLxvqAsFC',
                'role_id'           => 1,
                'phone'             => '08123456789',
                'gender'            => 'Pria',
                'birthdate'         => '1990-01-01',
                'created_at'        => '2026-02-28 19:41:02',
                'updated_at'        => '2026-02-28 19:41:02',
            ],
        ]);
=======
        // Seeder user default
        User::create([
            'name' => 'Default User',
            'email' => 'user1@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'phone' => '08123456789',
            'sso_id' => null,
            'role_id' => 1, // pastikan role dengan id 1 sudah ada di tabel roles
            'points' => 100,
            'gender' => 'Pria',
            'birth_date' => '1990-01-01',
            // 'avatar' => null, // sudah di-drop
            'remember_token' => null,
        ]);
        // Tambah user lain jika perlu
>>>>>>> 318de4113e9ec9047d5232e66bad19e1b8c840a7
    }
}
