<?php

namespace App\Modules\Kehadiran\Actions;

use App\Modules\Penjadwalan\Models\Sesi;
use Illuminate\Support\Facades\DB;

class CatatKehadiran
{
    public function __construct(private readonly HitungKelulusan $kelulusan) {}

    /**
     * Catat atau perbarui status hadir sekelompok peserta untuk satu sesi
     * dalam satu transaksi database. Memakai upsert pada PK gabungan
     * (id_peserta, id_sesi) sehingga simpan ulang memperbarui, bukan merangkap.
     * Status penyelesaian (FR 13) dihitung ulang setelahnya.
     *
     * @param  array<int, string>  $kehadiranMap  map [id_peserta => status_hadir]
     */
    public function execute(Sesi $sesi, array $kehadiranMap): void
    {
        $baris = [];
        foreach ($kehadiranMap as $idPeserta => $statusHadir) {
            $baris[] = [
                'id_peserta' => (int) $idPeserta,
                'id_sesi' => $sesi->id_sesi,
                'status_hadir' => $statusHadir,
            ];
        }

        DB::transaction(function () use ($baris, $sesi) {
            DB::table('tbl_kehadiran')->upsert($baris, ['id_peserta', 'id_sesi'], ['status_hadir']);
            $this->kelulusan->untukSesi($sesi->id_sesi);
        });
    }
}
