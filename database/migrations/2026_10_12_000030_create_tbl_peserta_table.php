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
        Schema::create('tbl_peserta', function (Blueprint $table) {
            $table->id('id_peserta');
            $table->string('nama', 50);
            $table->string('email', 100);

            $table->unique('email', 'uq_peserta_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_peserta');
    }
};
