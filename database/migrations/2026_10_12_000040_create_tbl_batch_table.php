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
        Schema::create('tbl_batch', function (Blueprint $table) {
            $table->id('id_batch');
            $table->foreignId('id_skema')->constrained('tbl_skema', 'id_skema')->onDelete('restrict');
            $table->string('periode', 7)->comment('format YYYY-MM');
            $table->string('mode', 10)->comment('Online / Offline');
            $table->string('status', 15)->nullable()->comment('Planned / Ongoing / Completed / Cancelled');

            $table->unique(['id_skema', 'periode', 'mode'], 'uq_batch_skema_periode_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_batch');
    }
};
