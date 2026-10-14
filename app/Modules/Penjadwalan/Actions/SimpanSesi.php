<?php

namespace App\Modules\Penjadwalan\Actions;

use App\Modules\Penjadwalan\Models\Sesi;
use App\Shared\Enums\StatusSesi;
use App\Shared\Enums\TipeSesi;
use Illuminate\Support\Facades\DB;

class SimpanSesi
{
    /**
     * Simpan sesi baru beserta kaitan batch-nya dalam satu transaksi database.
     * Validasi konflik (FR 08) dilakukan sebelum method ini dipanggil
     * melalui FormRequest di controller.
     *
     * @param  array<int>  $idBatches
     */
    public function execute(
        int $idTrainer,
        string $namaSesi,
        string $tanggal,
        string $jamMulai,
        string $jamSelesai,
        float $durasiHari,
        string $room,
        array $idBatches,
        ?string $status = null,
        ?string $catatan = null,
    ): Sesi {
        return DB::transaction(function () use (
            $idTrainer, $namaSesi, $tanggal, $jamMulai, $jamSelesai,
            $durasiHari, $room, $idBatches, $status, $catatan
        ) {
            $tipe = count($idBatches) > 1
                ? TipeSesi::Common->value
                : TipeSesi::Exclusive->value;

            $sesi = Sesi::create([
                'id_trainer' => $idTrainer,
                'nama_sesi' => $namaSesi,
                'tanggal' => $tanggal,
                'jam_mulai' => $jamMulai,
                'jam_selesai' => $jamSelesai,
                'durasi_hari' => $durasiHari,
                'room' => $room,
                'tipe' => $tipe,
                'status' => $status ?? StatusSesi::Planned->value,
                'catatan' => $catatan,
            ]);

            $sesi->batches()->attach($idBatches);

            return $sesi;
        });
    }
}
