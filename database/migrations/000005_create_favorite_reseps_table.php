<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorite_reseps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('resep_id')->constrained('reseps')->onDelete('cascade');
            $table->timestamps();
            
            // Prevent duplicate favorites
            $table->unique(['user_id', 'resep_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_reseps');
    }
};
