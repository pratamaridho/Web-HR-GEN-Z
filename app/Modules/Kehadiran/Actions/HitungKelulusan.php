<?php

namespace App\Modules\Kehadiran\Actions;

use App\Shared\Enums\StatusDaftar;
use App\Shared\Enums\StatusKehadiran;
use App\Shared\Enums\StatusSesi;
use Illuminate\Support\Facades\DB;

/**
 * FR 13: status penyelesaian peserta.
 *
 * Peserta ditandai Completed pada pendaftarannya bila persentase Hadir
 * terhadap sesi berstatus Completed yang dilayani batchnya memenuhi ambang.
 * Nilai awal (usulan, PRD OP 02): ambang 80% dan hanya sesi Completed.
 *
 * - Peserta tanpa satu pun kehadiran Hadir tidak pernah Completed.
 * - Peserta Completed yang turun di bawah ambang kembali ke Ongoing.
 * - Pendaftaran Dropped tidak diubah.
 */
class HitungKelulusan
{
    public const AMBANG = 0.8;

    /**
     * Hitung ulang status seluruh peserta pada satu batch.
     */
    public function execute(int $idBatch): void
    {
        DB::transaction(function () use ($idBatch) {
            $sesiSelesai = DB::table('tbl_sesi_batch as sb')
                ->join('tbl_sesi as s', 's.id_sesi', '=', 'sb.id_sesi')
                ->where('sb.id_batch', $idBatch)
                ->where('s.status', StatusSesi::Completed->value)
                ->pluck('sb.id_sesi');

            $total = $sesiSelesai->count();

            $hadir = DB::table('tbl_kehadiran')
                ->whereIn('id_sesi', $sesiSelesai)
                ->where('status_hadir', StatusKehadiran::Hadir->value)
                ->selectRaw('id_peserta, COUNT(*) as jumlah')
                ->groupBy('id_peserta')
                ->pluck('jumlah', 'id_peserta');

            $daftar = DB::table('tbl_daftar')
                ->where('id_batch', $idBatch)
                ->where('status', '<>', StatusDaftar::Dropped->value)
                ->get(['id_peserta', 'status']);

            foreach ($daftar as $d) {
                $jumlahHadir = (int) ($hadir[$d->id_peserta] ?? 0);
                $lulus = $total > 0 && $jumlahHadir > 0 && ($jumlahHadir / $total) >= self::AMBANG;

                $baru = match (true) {
                    $lulus => StatusDaftar::Completed->value,
                    $d->status === StatusDaftar::Completed->value => StatusDaftar::Ongoing->value,
                    default => $d->status,
                };

                if ($baru !== $d->status) {
                    DB::table('tbl_daftar')
                        ->where('id_batch', $idBatch)
                        ->where('id_peserta', $d->id_peserta)
                        ->update(['status' => $baru]);
                }
            }
        });
    }

    /**
     * Hitung ulang untuk semua batch yang dilayani sebuah sesi.
     */
    public function untukSesi(int $idSesi): void
    {
        DB::table('tbl_sesi_batch')->where('id_sesi', $idSesi)->pluck('id_batch')
            ->each(fn ($idBatch) => $this->execute((int) $idBatch));
    }
}
