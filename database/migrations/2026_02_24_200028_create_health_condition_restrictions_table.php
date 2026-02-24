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
        Schema::create('health_condition_restrictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_condition_id')->constrained()->onDelete('cascade');
            $table->foreignId('ingredient_id')->constrained()->onDelete('cascade');
            $table->enum('severity', ['hindari', 'batasi', 'anjuran']);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['health_condition_id', 'ingredient_id'], 'unique_condition_ingredient');
            $table->index('health_condition_id');
            $table->index('ingredient_id');
            $table->index('severity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_condition_restrictions');
    }
};