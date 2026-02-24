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
    Schema::create('user_devices', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();

        $table->string('device_uuid');
        $table->string('device_name')->nullable();
        $table->string('platform'); // web / android / ios

        $table->timestamp('last_login_at')->nullable();

        $table->timestamps();

        $table->unique(['user_id', 'device_uuid']);
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};