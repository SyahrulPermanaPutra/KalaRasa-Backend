<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // database/migrations/xxxx_update_users_table.php

public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        // Tambah sso_id untuk menyimpan ID user dari SSO server
        $table->string('sso_id')->nullable()->unique()->after('id');

        // Password jadi nullable karena user SSO tidak punya password lokal
        $table->string('password')->nullable()->change();
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('sso_id');

        // Kembalikan password jadi not nullable
        $table->string('password')->nullable(false)->change();
    });
}
};
