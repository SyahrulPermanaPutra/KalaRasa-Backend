<?php
// database/migrations/2024_01_01_000001_create_users_table_squashed.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // Default Laravel columns
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            
            // Points (dari 2026_02_23)
            $table->integer('points')->default(0)->nullable();
            
            // Two Factor Authentication (dari 2025_08_14)
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            
            // Profile fields gabungan (dari berbagai migrasi)
            $table->string('phone', 20)->nullable();
            $table->enum('gender', ['pria', 'wanita'])->nullable(); // Gabungan kedua enum
            $table->date('birth_date')->nullable(); // dari migrasi lama (optional, bisa dihapus jika tidak dipakai)
            
            // Role (dari 000001)
            $table->enum('role', ['user', 'admin'])->default('user');
            
            // Avatar TIDAK ADA karena sudah di-drop
            
            $table->rememberToken();
            $table->timestamps();
        });

        // Buat tabel pendukung Laravel
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};