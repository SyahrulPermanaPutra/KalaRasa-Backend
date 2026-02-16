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
        Schema::dropIfExists('recipe_taste_profiles');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
Schema::create('recipe_taste_profiles', function ($table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('taste_profile_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['recipe_id', 'taste_profile_id'], 'unique_recipe_taste');
        });       
    }
};
