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
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            $table->enum('kategori', ['protein Hewani', 'protein Nabati', 'sayuran','buah-buahan', 'karbohidrat', 'bumbu', 'lemak', 'penyedap','pelengkap','minuman']);
            $table->string('sub_kategori', 100)->nullable();
            $table->timestamps();
            
            $table->index('nama');
            $table->index('kategori');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};