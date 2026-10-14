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
        Schema::create('tbl_kehadiran', function (Blueprint $table) {
            $table->foreignId('id_peserta')->constrained('tbl_peserta', 'id_peserta')->onDelete('restrict');
            $table->foreignId('id_sesi')->constrained('tbl_sesi', 'id_sesi')->onDelete('restrict');
            $table->string('status_hadir', 10)->nullable()->comment('Hadir / Izin / Alpa');

            $table->primary(['id_peserta', 'id_sesi']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_kehadiran');
    }
};
