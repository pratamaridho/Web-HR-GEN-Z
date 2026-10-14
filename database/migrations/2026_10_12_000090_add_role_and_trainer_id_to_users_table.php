<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Nilai role: admin, trainer, management (string agar portabel antar DB).
     * Default memakai peran paling sempit (hanya baca) sehingga akun tanpa
     * peran eksplisit tidak pernah menjadi admin.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('management')->after('email');
            $table->foreignId('trainer_id')->nullable()->after('role')
                ->constrained('tbl_trainer', 'id_trainer')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['trainer_id']);
            $table->dropColumn(['role', 'trainer_id']);
        });
    }
};
