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
        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->onDelete('cascade');
            $table->foreignId('ingredient_id')->constrained()->onDelete('cascade');
            $table->boolean('is_main')->default(false)->comment('TRUE untuk bahan utama, FALSE untuk tambahan');
            $table->string('jumlah', 50)->nullable()->comment('Contoh: 500 gram, 2 butir, secukupnya');
            $table->timestamps();
            
            $table->unique(['recipe_id', 'ingredient_id'], 'unique_recipe_ingredient');
            $table->index('recipe_id');
            $table->index('ingredient_id');
            $table->index('is_main');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_ingredients');
    }
};