<?php
// database/migrations/2024_01_01_000002_add_role_id_to_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hapus kolom role enum yang lama jika ada
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
            
            // Tambahkan kolom role_id
            $table->foreignId('role_id')
                  ->after('password')
                  ->nullable()
                  ->constrained('roles')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hapus foreign key dan kolom role_id
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
            
            // Kembalikan kolom role enum seperti semula
            $table->enum('role', ['user', 'admin'])
                  ->default('user')
                  ->after('password');
        });
    }
};