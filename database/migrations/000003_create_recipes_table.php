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
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 255);
            $table->enum('tingkat_kesulitan', ['mudah', 'sedang', 'sulit']);
            $table->integer('waktu_masak')->comment('Waktu dalam menit');
            $table->integer('kalori_per_porsi');
            $table->string('region', 100)->nullable();
            $table->timestamps();
            
            $table->index('nama');
            $table->index('tingkat_kesulitan');
            $table->index('waktu_masak');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};