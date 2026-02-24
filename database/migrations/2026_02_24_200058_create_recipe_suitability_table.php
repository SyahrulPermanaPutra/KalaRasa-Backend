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
        Schema::create('recipe_suitability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->onDelete('cascade');
            $table->foreignId('health_condition_id')->constrained()->onDelete('cascade');
            $table->boolean('is_suitable')->comment('TRUE = cocok, FALSE = tidak cocok');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['recipe_id', 'health_condition_id'], 'unique_recipe_condition');
            $table->index('recipe_id');
            $table->index('health_condition_id');
            $table->index('is_suitable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_suitability');
    }
};