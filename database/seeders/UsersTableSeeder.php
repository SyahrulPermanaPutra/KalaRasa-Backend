<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        $users = [
            ['name' => 'Ahmad Rizki', 'email' => 'ahmad.rizki@email.com', 'role' => 'admin', 'phone' => '081234567890', 'gender' => 'L', 'birth_date' => '1990-05-15'],
            ['name' => 'Siti Nurhaliza', 'email' => 'siti.nur@email.com', 'role' => 'user', 'phone' => '081234567891', 'gender' => 'P', 'birth_date' => '1992-08-20'],
            ['name' => 'Budi Santoso', 'email' => 'budi.santoso@email.com', 'role' => 'user', 'phone' => '081234567892', 'gender' => 'L', 'birth_date' => '1988-03-10'],
            ['name' => 'Dewi Lestari', 'email' => 'dewi.lestari@email.com', 'role' => 'user', 'phone' => '081234567893', 'gender' => 'P', 'birth_date' => '1995-12-05'],
            ['name' => 'Eko Prasetyo', 'email' => 'eko.prasetyo@email.com', 'role' => 'user', 'phone' => '081234567894', 'gender' => 'L', 'birth_date' => '1987-07-22'],
            ['name' => 'Fitri Handayani', 'email' => 'fitri.handayani@email.com', 'role' => 'user', 'phone' => '081234567895', 'gender' => 'P', 'birth_date' => '1993-04-18'],
            ['name' => 'Gunawan Wijaya', 'email' => 'gunawan.wijaya@email.com', 'role' => 'user', 'phone' => '081234567896', 'gender' => 'L', 'birth_date' => '1991-09-30'],
            ['name' => 'Hana Permata', 'email' => 'hana.permata@email.com', 'role' => 'user', 'phone' => '081234567897', 'gender' => 'P', 'birth_date' => '1994-01-25'],
            ['name' => 'Indra Kusuma', 'email' => 'indra.kusuma@email.com', 'role' => 'user', 'phone' => '081234567898', 'gender' => 'L', 'birth_date' => '1989-11-12'],
            ['name' => 'Jasmine Putri', 'email' => 'jasmine.putri@email.com', 'role' => 'user', 'phone' => '081234567899', 'gender' => 'P', 'birth_date' => '1996-06-08'],
            ['name' => 'Kevin Alamsyah', 'email' => 'kevin.alamsyah@email.com', 'role' => 'user', 'phone' => '081234567900', 'gender' => 'L', 'birth_date' => '1990-02-14'],
            ['name' => 'Lina Marlina', 'email' => 'lina.marlina@email.com', 'role' => 'user', 'phone' => '081234567901', 'gender' => 'P', 'birth_date' => '1992-10-03'],
            ['name' => 'Muhammad Fikri', 'email' => 'muhammad.fikri@email.com', 'role' => 'user', 'phone' => '081234567902', 'gender' => 'L', 'birth_date' => '1988-05-27'],
            ['name' => 'Nadia Safitri', 'email' => 'nadia.safitri@email.com', 'role' => 'user', 'phone' => '081234567903', 'gender' => 'P', 'birth_date' => '1995-08-16'],
            ['name' => 'Omar Abdullah', 'email' => 'omar.abdullah@email.com', 'role' => 'user', 'phone' => '081234567904', 'gender' => 'L', 'birth_date' => '1987-12-21'],
            ['name' => 'Putri Rahayu', 'email' => 'putri.rahayu@email.com', 'role' => 'user', 'phone' => '081234567905', 'gender' => 'P', 'birth_date' => '1993-03-09'],
            ['name' => 'Rudi Hartono', 'email' => 'rudi.hartono@email.com', 'role' => 'user', 'phone' => '081234567906', 'gender' => 'L', 'birth_date' => '1991-07-14'],
            ['name' => 'Sari Wulandari', 'email' => 'sari.wulandari@email.com', 'role' => 'user', 'phone' => '081234567907', 'gender' => 'P', 'birth_date' => '1994-11-28'],
            ['name' => 'Taufik Hidayat', 'email' => 'taufik.hidayat@email.com', 'role' => 'user', 'phone' => '081234567908', 'gender' => 'L', 'birth_date' => '1989-04-05'],
            ['name' => 'Uma Mahendra', 'email' => 'uma.mahendra@email.com', 'role' => 'user', 'phone' => '081234567909', 'gender' => 'P', 'birth_date' => '1996-09-17'],
        ];

        foreach ($users as $user) {
            \App\Models\User::create([
                'name' => $user['name'],
                'email' => $user['email'],
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password123'),
                'role' => $user['role'],
                'phone' => $user['phone'],
                'gender' => $user['gender'],
                'birth_date' => $user['birth_date'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}