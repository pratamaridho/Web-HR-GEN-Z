<?php

namespace App\Modules\Penjadwalan\Actions;

use App\Modules\Kehadiran\Actions\HitungKelulusan;
use App\Modules\Penjadwalan\Models\Sesi;
use App\Shared\Enums\TipeSesi;
use Illuminate\Support\Facades\DB;

class UbahSesi
{
    public function __construct(private readonly HitungKelulusan $kelulusan) {}

    /**
     * Ubah sesi beserta kaitan batch-nya dalam satu transaksi. Jenis sesi
     * dihitung ulang dari jumlah batch yang dilayani. Status penyelesaian
     * peserta (FR 13) dihitung ulang karena status sesi bisa berubah ke
     * atau dari Completed.
     *
     * @param  array<string, mixed>  $atribut  kolom tbl_sesi (tanpa tipe)
     * @param  array<int>  $idBatches
     */
    public function execute(Sesi $sesi, array $atribut, array $idBatches): Sesi
    {
        return DB::transaction(function () use ($sesi, $atribut, $idBatches) {
            $batchLama = $sesi->batches()->pluck('tbl_batch.id_batch')->all();

            $atribut['tipe'] = count($idBatches) > 1
                ? TipeSesi::Common->value
                : TipeSesi::Exclusive->value;

            $sesi->update($atribut);
            $sesi->batches()->sync($idBatches);

            foreach (array_unique(array_merge($batchLama, $idBatches)) as $idBatch) {
                $this->kelulusan->execute((int) $idBatch);
            }

            return $sesi;
        });
    }
}
