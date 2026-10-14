<?php

namespace App\Modules\Kehadiran\Actions;

use Illuminate\Support\Facades\DB;

/**
 * FR 12: Admin mengoreksi catatan kehadiran seorang peserta pada sesi-sesi
 * batchnya. Status penyelesaian (FR 13) dihitung ulang dalam transaksi yang sama.
 */
class KoreksiKehadiran
{
    public function __construct(private readonly HitungKelulusan $kelulusan) {}

    /**
     * @param  array<int, string>  $statusPerSesi  map [id_sesi => status_hadir]
     */
    public function execute(int $idPeserta, int $idBatch, array $statusPerSesi): void
    {
        $baris = [];
        foreach ($statusPerSesi as $idSesi => $status) {
            $baris[] = ['id_peserta' => $idPeserta, 'id_sesi' => (int) $idSesi, 'status_hadir' => $status];
        }

        DB::transaction(function () use ($baris, $idBatch) {
            DB::table('tbl_kehadiran')->upsert($baris, ['id_peserta', 'id_sesi'], ['status_hadir']);
            $this->kelulusan->execute($idBatch);
        });
    }
}
