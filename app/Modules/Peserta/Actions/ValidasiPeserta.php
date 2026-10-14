<?php

namespace App\Modules\Peserta\Actions;

use App\Shared\Enums\StatusDaftar;
use Illuminate\Support\Facades\DB;

/**
 * Memvalidasi pendaftaran (misalnya pembayaran sudah dikonfirmasi): status
 * Registered menjadi Validated. Hanya peserta Validated yang dihitung pada
 * kuota sesi dan muncul pada daftar kehadiran.
 */
class ValidasiPeserta
{
    /**
     * @param  list<int>|null  $idPeserta  null berarti semua peserta yang masih menunggu pada batch itu
     * @return int jumlah pendaftaran yang divalidasi
     */
    public function execute(int $idBatch, ?array $idPeserta = null): int
    {
        return DB::table('tbl_daftar')
            ->where('id_batch', $idBatch)
            ->where('status', StatusDaftar::Registered->value)
            ->when($idPeserta !== null, fn ($q) => $q->whereIn('id_peserta', $idPeserta))
            ->update(['status' => StatusDaftar::Validated->value]);
    }
}
