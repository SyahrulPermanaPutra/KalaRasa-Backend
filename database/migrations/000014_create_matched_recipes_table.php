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
        Schema::create('matched_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nlp_logs_id')->constrained()->onDelete('cascade');
            $table->foreignId('recipe_id')->constrained()->onDelete('cascade');
            $table->decimal('match_score', 5, 2);
            $table->integer('rank_position');
            $table->timestamps();
            
            $table->index('nlp_logs_id');
            $table->index('recipe_id');
            $table->index('match_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matched_recipes');
    }
};