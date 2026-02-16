<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus foreign key di tabel recipe_taste_profiles terlebih dahulu
        Schema::table('recipe_taste_profiles', function (Blueprint $table) {
            $table->dropForeign(['taste_profile_id']);
        });

        // Hapus tabel taste_profiles
        Schema::dropIfExists('taste_profiles');
    }

    public function down(): void
    {
        // Buat ulang tabel taste_profiles
        Schema::create('taste_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50)->unique();
            $table->timestamps();
            $table->index('nama');
        });

        // Buat ulang foreign key di tabel recipe_taste_profiles
        Schema::table('recipe_taste_profiles', function (Blueprint $table) {
            $table->foreign('taste_profile_id')
                  ->references('id')
                  ->on('taste_profiles')
                  ->onDelete('cascade');
        });
    }
};