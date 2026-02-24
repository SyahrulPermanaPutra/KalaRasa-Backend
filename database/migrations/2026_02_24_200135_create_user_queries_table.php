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
        Schema::create('user_queries', function (Blueprint $table) {
            $table->id();
            $table->text('query_text');
            $table->string('intent', 50)->nullable();
            $table->decimal('confidence', 3, 2)->nullable();
            $table->enum('status', ['ok', 'fallback', 'clarification']);
            $table->json('entities')->nullable();
            $table->timestamps();
            
            $table->index('intent');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_queries');
    }
};