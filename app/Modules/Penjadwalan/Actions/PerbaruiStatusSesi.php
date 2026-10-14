<?php

namespace App\Modules\Penjadwalan\Actions;

use App\Modules\Kehadiran\Actions\HitungKelulusan;
use App\Modules\Penjadwalan\Models\Sesi;
use App\Shared\Enums\StatusDaftar;
use App\Shared\Enums\StatusSesi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Status sesi mengikuti waktu:
 * - sebelum jam mulai: Planned
 * - saat berlangsung: Ongoing
 * - setelah jam selesai: Completed
 * - saat jam mulai tiba dengan peserta di bawah kuota minimum: Cancelled
 *
 * Sesi yang dibatalkan manual (Cancelled) tidak diubah. Kelulusan peserta
 * dihitung ulang untuk batch pada sesi yang baru Completed.
 */
class PerbaruiStatusSesi
{
    public function __construct(private readonly HitungKelulusan $kelulusan) {}

    /**
     * @return int jumlah sesi yang statusnya berubah
     */
    public function execute(?Carbon $sekarang = null): int
    {
        $sekarang ??= now();
        $kuota = (int) config('hrgz.kuota_minimum');
        $berubah = 0;
        $batchSelesai = [];

        $sesi = Sesi::with('batches')
            ->whereIn('status', [StatusSesi::Planned->value, StatusSesi::Ongoing->value])
            ->whereDate('tanggal', '<=', $sekarang->toDateString())
            ->get();

        foreach ($sesi as $s) {
            if ($sekarang->lt($s->mulai)) {
                continue;
            }

            $status = $sekarang->gte($s->selesai) ? StatusSesi::Completed : StatusSesi::Ongoing;
            $catatan = $s->catatan;

            if ($s->status === StatusSesi::Planned->value && $kuota > 0) {
                $jumlah = $this->jumlahPeserta($s);

                if ($jumlah < $kuota) {
                    $status = StatusSesi::Cancelled;
                    $catatan = Str::limit("Dibatalkan otomatis: peserta {$jumlah} dari minimal {$kuota}. ".$catatan, 255, '');
                }
            }

            if ($status->value === $s->status) {
                continue;
            }

            $s->update(['status' => $status->value, 'catatan' => $catatan]);
            $berubah++;

            if ($status === StatusSesi::Completed) {
                $batchSelesai = array_merge($batchSelesai, $s->batches->pluck('id_batch')->all());
            }
        }

        foreach (array_unique($batchSelesai) as $idBatch) {
            $this->kelulusan->execute((int) $idBatch);
        }

        return $berubah;
    }

    /** Peserta berbeda pada seluruh batch yang dilayani sesi. */
    private function jumlahPeserta(Sesi $sesi): int
    {
        return (int) DB::table('tbl_daftar')
            ->whereIn('id_batch', $sesi->batches->pluck('id_batch'))
            ->where(fn ($q) => $q->whereNull('status')->orWhere('status', '<>', StatusDaftar::Registered->value))
            ->distinct()
            ->count('id_peserta');
    }
}
