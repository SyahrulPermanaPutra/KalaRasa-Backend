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
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['tingkat_kesulitan', 'kalori_per_porsi']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->enum('tingkat_kesulitan', ['mudah', 'sedang', 'sulit'])->after('nama');
            $table->integer('kalori_per_porsi')->after('waktu_masak');
        });
    }
};