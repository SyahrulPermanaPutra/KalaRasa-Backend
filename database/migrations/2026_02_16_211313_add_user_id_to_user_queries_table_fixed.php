<?php
// database/migrations/2026_02_16_220000_add_user_id_to_user_queries_table_fixed.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_queries', function (Blueprint $table) {
            // Cek apakah kolom sudah ada
            if (!Schema::hasColumn('user_queries', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
                $table->foreign('user_id')
                      ->references('id')
                      ->on('users')
                      ->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_queries', function (Blueprint $table) {
            // Hapus foreign key dengan pengecekan
            try {
                $table->dropForeign(['user_id']);
            } catch (\Exception $e) {
                // Abaikan
            }
            
            // Hapus kolom dengan pengecekan
            if (Schema::hasColumn('user_queries', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });
    }
};