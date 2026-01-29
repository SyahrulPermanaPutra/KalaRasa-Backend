<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopping_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nama_item');
            $table->integer('jumlah')->default(1);
            $table->string('satuan')->default('pcs'); // kg, gram, liter, pcs, dll
            $table->decimal('harga', 12, 2)->nullable();
            $table->boolean('sudah_dibeli')->default(false);
            $table->date('tanggal_dibeli')->nullable();
            $table->string('kategori')->nullable(); // sayuran, daging, bumbu, dll
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopping_lists');
    }
};
