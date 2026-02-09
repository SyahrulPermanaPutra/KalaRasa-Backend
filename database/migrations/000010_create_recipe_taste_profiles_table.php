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
        Schema::create('recipe_taste_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->onDelete('cascade');
            $table->foreignId('taste_profile_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['recipe_id', 'taste_profile_id'], 'unique_recipe_taste');
            $table->index('recipe_id');
            $table->index('taste_profile_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_taste_profiles');
    }
};