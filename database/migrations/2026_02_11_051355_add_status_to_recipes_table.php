<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            // Tambah kolom yang belum ada
            if (!Schema::hasColumn('recipes', 'deskripsi')) {
                $table->text('deskripsi')->nullable();
            }
            if (!Schema::hasColumn('recipes', 'gambar')) {
                $table->string('gambar')->nullable();
            }
            if (!Schema::hasColumn('recipes', 'kategori')) {
                $table->string('kategori')->nullable();
            }
            if (!Schema::hasColumn('recipes', 'status')) {
                $table->enum('status', ['pending', 'approved', 'rejected'])
                      ->default('pending');
            }
            if (!Schema::hasColumn('recipes', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
            }
            if (!Schema::hasColumn('recipes', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable();
            }
            if (!Schema::hasColumn('recipes', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
            if (!Schema::hasColumn('recipes', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable();
            }
            if (!Schema::hasColumn('recipes', 'avg_rating')) {
                $table->decimal('avg_rating', 3, 2)->default(0);
            }
            if (!Schema::hasColumn('recipes', 'total_ratings')) {
                $table->unsignedInteger('total_ratings')->default(0);
            }
            if (!Schema::hasColumn('recipes', 'view_count')) {
                $table->unsignedBigInteger('view_count')->default(0);
            }
        });
        // Set semua data lama jadi approved
        DB::table('recipes')->update(['status' => 'approved']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn([
                'deskripsi', 
                'gambar',
                'kategori',
                'status', 
                'created_by', 
                'approved_by',
                'approved_at', 
                'rejection_reason', 
                'avg_rating',
                'total_ratings', 
                'view_count',
            ]);        
        });
    }
};
