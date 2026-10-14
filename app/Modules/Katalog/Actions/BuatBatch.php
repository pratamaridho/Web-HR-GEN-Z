<?php

namespace App\Modules\Katalog\Actions;

use App\Modules\Katalog\Models\Batch;
use App\Modules\Katalog\Models\Skema;
use App\Shared\Enums\StatusBatch;
use Illuminate\Database\QueryException;

class BuatBatch
{
    /**
     * Buat satu batch baru untuk skema dan periode tertentu.
     * Constraint uq_batch_skema_periode_mode mencegah duplikat.
     *
     * @throws QueryException jika batch ganda
     */
    public function execute(Skema $skema, string $periode, string $mode): Batch
    {
        return Batch::create([
            'id_skema' => $skema->id_skema,
            'periode' => $periode,
            'mode' => $mode,
            'status' => StatusBatch::Planned->value,
        ]);
    }
}
