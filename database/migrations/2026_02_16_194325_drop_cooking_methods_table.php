<?php

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
        // Nonaktifkan foreign key checks sementara
        Schema::disableForeignKeyConstraints();
        
        // Hapus tabel jika ada
        Schema::dropIfExists('cooking_methods');
        
        // Aktifkan kembali foreign key checks
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        
        Schema::create('cooking_methods', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            $table->enum('kategori', ['panas_kering', 'panas_basah', 'kombinasi', 'tanpa_panas']);
            $table->timestamps();
            
            $table->index('nama');
            $table->index('kategori');
        });
        
        Schema::enableForeignKeyConstraints();
    }
};