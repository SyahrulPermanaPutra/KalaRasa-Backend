<?php
// database/migrations/2024_01_01_000005_create_recipes_table_squashed.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 255);
            $table->integer('waktu_masak')->comment('Waktu dalam menit');
            $table->string('region', 100)->nullable();
            
            // Dari migrasi kedua dan ketiga
            $table->text('deskripsi')->nullable();
            $table->longText('langkah_langkah')->nullable(); // dari migrasi terbaru
            $table->string('gambar')->nullable();
            $table->string('kategori')->nullable();
            
            // Status dan approval
            $table->enum('status', ['pending', 'approved', 'rejected'])
                  ->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Rating dan views
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->unsignedInteger('total_ratings')->default(0);
            $table->unsignedBigInteger('view_count')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index('nama');
            $table->index('waktu_masak');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};