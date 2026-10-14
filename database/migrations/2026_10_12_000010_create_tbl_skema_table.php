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
        Schema::create('tbl_skema', function (Blueprint $table) {
            $table->id('id_skema');
            $table->string('nama_skema', 50);
            $table->decimal('durasi_online', 2, 1)->nullable()->comment('dalam hari');
            $table->decimal('durasi_offline', 2, 1)->nullable()->comment('dalam hari, bisa 0.5');

            $table->unique('nama_skema', 'uq_skema_nama_skema');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_skema');
    }
};
