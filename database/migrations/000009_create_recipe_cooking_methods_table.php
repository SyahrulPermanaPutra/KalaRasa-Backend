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
        Schema::create('recipe_cooking_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->onDelete('cascade');
            $table->foreignId('cooking_method_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['recipe_id', 'cooking_method_id'], 'unique_recipe_method');
            $table->index('recipe_id');
            $table->index('cooking_method_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_cooking_methods');
    }
};