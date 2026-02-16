<?php
// database/migrations/2024_01_01_100001_split_jumlah_column_in_recipe_ingredients.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom baru
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->decimal('jumlah_value', 8, 2)->nullable()->after('is_main');
            $table->string('satuan', 50)->nullable()->after('jumlah_value');
        });

        // 2. Pindahkan data sederhana (tanpa parsing kompleks)
        DB::statement("
            UPDATE recipe_ingredients 
            SET 
                jumlah_value = CASE 
                    WHEN jumlah REGEXP '^[0-9]+' THEN CAST(REGEXP_SUBSTR(jumlah, '^[0-9]+') AS DECIMAL(8,2))
                    ELSE NULL 
                END,
                satuan = CASE 
                    WHEN jumlah LIKE '%gram%' THEN 'gram'
                    WHEN jumlah LIKE '%kg%' THEN 'kg'
                    WHEN jumlah LIKE '%ml%' THEN 'ml'
                    WHEN jumlah LIKE '%liter%' THEN 'liter'
                    WHEN jumlah LIKE '%sendok%' THEN 'sendok'
                    WHEN jumlah LIKE '%gelas%' THEN 'gelas'
                    WHEN jumlah LIKE '%butir%' THEN 'butir'
                    WHEN jumlah LIKE '%siung%' THEN 'siung'
                    WHEN jumlah LIKE '%buah%' THEN 'buah'
                    WHEN jumlah = 'secukupnya' THEN NULL
                    ELSE NULL
                END
        ");

        // 3. Hapus kolom lama
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->dropColumn('jumlah');
        });

        // 4. Rename kolom baru
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->renameColumn('jumlah_value', 'jumlah');
        });
    }

    public function down(): void
    {
        // 1. Tambah kolom lama
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->string('jumlah_old', 50)->nullable()->after('satuan');
        });

        // 2. Gabungkan data kembali
        DB::statement("
            UPDATE recipe_ingredients 
            SET jumlah_old = CONCAT(COALESCE(jumlah, ''), ' ', COALESCE(satuan, ''))
        ");

        // 3. Hapus kolom baru
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->dropColumn(['jumlah', 'satuan']);
        });

        // 4. Rename kolom lama
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->renameColumn('jumlah_old', 'jumlah');
        });
    }
};