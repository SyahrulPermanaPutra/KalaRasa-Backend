<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Database: kala_rasa_jtv
     */
    public function up(): void
    {
        // 1. roles
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('display_name')->nullable();
            $table->timestamps();
        });

        // 2. users
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('sso_id')->nullable();
            $table->json('sso_raw')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->integer('points')->default(0);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->string('phone')->nullable();
            $table->enum('gender', ['Pria', 'Wanita'])->nullable();
            $table->date('birthdate')->nullable();
            $table->timestamps();
        });

        // 3. health_conditions
        Schema::create('health_conditions', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 4. ingredients
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->enum('kategori', ['protein', 'sayuran', 'karbohidrat', 'bumbu', 'lemak', 'penyedap']);
            $table->string('sub_kategori', 100)->nullable();
            $table->timestamps();
        });

        // 5. health_condition_restrictions
        Schema::create('health_condition_restrictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_condition_id')->constrained('health_conditions')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->enum('severity', ['hindari', 'batasi', 'anjuran']);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 6. recipes
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->integer('waktu_masak')->comment('Waktu dalam menit');
            $table->string('region', 100)->nullable();
            $table->text('deskripsi')->nullable();
            $table->longText('langkah_langkah')->nullable();
            $table->string('gambar')->nullable();
            $table->string('kategori')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->decimal('avg_rating', 3, 2)->default(0.00);
            $table->unsignedInteger('total_ratings')->default(0);
            $table->unsignedBigInteger('view_count')->default(0);
            $table->timestamps();
        });

        // 7. recipe_ingredients
        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->tinyInteger('is_main')->default(0)->comment('TRUE untuk bahan utama, FALSE untuk tambahan');
            $table->decimal('jumlah', 8, 2)->nullable();
            $table->string('satuan', 50)->nullable();
            $table->timestamps();
        });

        // 8. recipe_ratings
        Schema::create('recipe_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating')->comment('1-5 stars');
            $table->text('review')->nullable();
            $table->timestamps();
        });

        // 9. recipe_suitability
        Schema::create('recipe_suitability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->foreignId('health_condition_id')->constrained('health_conditions')->cascadeOnDelete();
            $table->tinyInteger('is_suitable')->comment('TRUE = cocok, FALSE = tidak cocok');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 10. bookmarks
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->timestamps();
        });

        // 11. shopping_lists
        Schema::create('shopping_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipe_id')->nullable()->constrained('recipes')->nullOnDelete();
            $table->string('nama_list');
            $table->date('shopping_date')->nullable();
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->decimal('total_estimated_price', 12, 2)->default(0.00);
            $table->decimal('total_actual_price', 12, 2)->default(0.00);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        // 12. shopping_list_items
        Schema::create('shopping_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shopping_list_id')->constrained('shopping_lists')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->nullable()->constrained('ingredients')->nullOnDelete();
            $table->string('nama_item');
            $table->decimal('jumlah', 10, 2)->default(1.00);
            $table->string('satuan')->default('pcs');
            $table->decimal('estimated_price', 12, 2)->default(0.00);
            $table->decimal('actual_price', 12, 2)->nullable();
            $table->boolean('is_purchased')->default(false);
            $table->timestamp('purchased_at')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        // 13. expenses
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shopping_list_id')->nullable()->constrained('shopping_lists')->nullOnDelete();
            $table->foreignId('shopping_list_item_id')->nullable()->constrained('shopping_list_items')->nullOnDelete();
            $table->decimal('actual_price', 12, 2);
            $table->date('purchase_date');
            $table->string('store_name')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        // 14. user_queries
        Schema::create('user_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('query_text');
            $table->string('intent', 50)->nullable();
            $table->decimal('confidence', 3, 2)->nullable();
            $table->enum('status', ['ok', 'fallback', 'clarification']);
            $table->json('entities')->nullable();
            $table->timestamps();
        });

        // 15. matched_recipes
        Schema::create('matched_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_query_id')->constrained('user_queries')->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->decimal('match_score', 5, 2);
            $table->integer('rank_position');
            $table->timestamps();
        });

        // 16. cache
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        // 17. cache_locks
        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matched_recipes');
        Schema::dropIfExists('user_queries');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('shopping_list_items');
        Schema::dropIfExists('shopping_lists');
        Schema::dropIfExists('bookmarks');
        Schema::dropIfExists('recipe_suitability');
        Schema::dropIfExists('recipe_ratings');
        Schema::dropIfExists('recipe_ingredients');
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('health_condition_restrictions');
        Schema::dropIfExists('ingredients');
        Schema::dropIfExists('health_conditions');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
    }
};
