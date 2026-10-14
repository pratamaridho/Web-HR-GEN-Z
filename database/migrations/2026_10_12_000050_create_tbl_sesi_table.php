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
        Schema::create('tbl_sesi', function (Blueprint $table) {
            $table->id('id_sesi');
            $table->foreignId('id_trainer')->constrained('tbl_trainer', 'id_trainer')->onDelete('restrict');
            $table->string('nama_sesi', 100);
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->decimal('durasi_hari', 2, 1)->nullable();
            $table->string('room', 30);
            $table->string('tipe', 10)->nullable()->comment('Common / Exclusive');
            $table->string('status', 15)->nullable()->comment('Planned / Ongoing / Completed / Cancelled');

            $table->unique(['tanggal', 'jam_mulai', 'room'], 'uq_sesi_jadwal_room');
            $table->unique(['tanggal', 'jam_mulai', 'id_trainer'], 'uq_sesi_jadwal_trainer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_sesi');
    }
};
