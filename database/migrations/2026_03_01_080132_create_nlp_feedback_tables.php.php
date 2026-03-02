<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * php artisan migrate
 * File: database/migrations/2026_03_01_000001_create_nlp_feedback_tables.php
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. nlp_feedback ───────────────────────────────────────────────
        Schema::create('nlp_feedback', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->string('session_id', 100)->index();
            $table->foreignId('user_query_id')
                  ->nullable()
                  ->constrained('user_queries')
                  ->nullOnDelete();

            $table->foreignId('recipe_id')
                  ->constrained('recipes')
                  ->cascadeOnDelete();
            $table->unsignedTinyInteger('rank_shown')->default(1);

            $table->tinyInteger('rating');               // 1 atau -1
            $table->enum('feedback_type', ['explicit', 'implicit'])->default('explicit');

            $table->text('query_text')->nullable();
            $table->string('query_hash', 32)->nullable()->index();
            $table->decimal('matched_score', 5, 2)->nullable();

            $table->timestamps();

            $table->index('recipe_id');
            $table->index('user_id');
            $table->index('rating');
            $table->index('created_at');
        });

        // ── 2. cbr_weight_snapshots ───────────────────────────────────────
        Schema::create('cbr_weight_snapshots', function (Blueprint $table) {
            $table->id();

            $table->enum('snapshot_type', ['grid_search', 'manual', 'rollback'])
                  ->default('grid_search');
            $table->json('weights');

            $table->decimal('ndcg_at_5', 6, 4)->nullable();
            $table->decimal('precision_at_3', 6, 4)->nullable();
            $table->unsignedInteger('n_feedback_used')->nullable();

            $table->boolean('is_active')->default(false)->index();
            $table->text('notes')->nullable();
            $table->string('created_by', 100)->default('system');

            $table->timestamps();
        });

        // ── 3. Tambah kolom feedback ke matched_recipes ───────────────────
        Schema::table('matched_recipes', function (Blueprint $table) {
            if (! Schema::hasColumn('matched_recipes', 'feedback_given')) {
                $table->boolean('feedback_given')->default(false)->after('rank_position');
            }
            if (! Schema::hasColumn('matched_recipes', 'feedback_rating')) {
                $table->tinyInteger('feedback_rating')->nullable()->after('feedback_given');
            }
        });
    }

    public function down(): void
    {
        Schema::table('matched_recipes', function (Blueprint $table) {
            $table->dropColumnIfExists('feedback_given');
            $table->dropColumnIfExists('feedback_rating');
        });
        Schema::dropIfExists('cbr_weight_snapshots');
        Schema::dropIfExists('nlp_feedback');
    }
};