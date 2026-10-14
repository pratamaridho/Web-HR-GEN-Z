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
        Schema::create('tbl_sesi_batch', function (Blueprint $table) {
            $table->foreignId('id_batch')->constrained('tbl_batch', 'id_batch')->onDelete('restrict');
            $table->foreignId('id_sesi')->constrained('tbl_sesi', 'id_sesi')->onDelete('restrict');

            $table->primary(['id_batch', 'id_sesi']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_sesi_batch');
    }
};
