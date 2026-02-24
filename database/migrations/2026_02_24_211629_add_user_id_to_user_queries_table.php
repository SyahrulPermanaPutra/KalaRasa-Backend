<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_queries', function (Blueprint $table) {
            $table->foreignId('user_id')->after('id')->nullable()->constrained()->onDelete('cascade');
            // atau jika tidak mau foreign key:
            // $table->unsignedBigInteger('user_id')->nullable()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('user_queries', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};