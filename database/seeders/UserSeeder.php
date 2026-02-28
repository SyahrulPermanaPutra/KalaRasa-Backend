<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
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
    }
}
