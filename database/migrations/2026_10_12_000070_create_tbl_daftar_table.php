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
        Schema::create('tbl_daftar', function (Blueprint $table) {
            $table->foreignId('id_batch')->constrained('tbl_batch', 'id_batch')->onDelete('restrict');
            $table->foreignId('id_peserta')->constrained('tbl_peserta', 'id_peserta')->onDelete('restrict');
            $table->string('status', 15)->nullable()->comment('Registered / Ongoing / Completed / Dropped');

            $table->primary(['id_batch', 'id_peserta']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_daftar');
    }
};
