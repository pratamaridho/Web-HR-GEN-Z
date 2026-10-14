<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catatan sesi (prasyarat, dependency, atau catatan operasional) sesuai
     * field minimum Master Schedule pada brief bagian 5. Opsional.
     */
    public function up(): void
    {
        Schema::table('tbl_sesi', function (Blueprint $table) {
            $table->string('catatan', 255)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_sesi', function (Blueprint $table) {
            $table->dropColumn('catatan');
        });
    }
};
