<?php

namespace App\Modules\Katalog\Queries;

use App\Modules\Katalog\Models\Batch;
use App\Shared\Enums\StatusDaftar;
use App\Shared\Enums\StatusSesi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Ringkasan kesiapan tiap batch untuk halaman Batch Management: peserta
 * tervalidasi dan menunggu, durasi terjadwal dibanding durasi wajib, dan
 * penanda siap atau perlu tindakan. Hanya membaca; dihitung dari data.
 *
 * Aturan durasi mengikuti halaman Validasi durasi: sesi Cancelled tidak
 * dihitung dan sesi bersama dihitung sekali per batch.
 */
class RingkasanBatch
{
    private const TOLERANSI = 0.001;

    /**
     * @param  Collection<int, Batch>  $batch  batch dengan relasi skema sudah dimuat
     * @return array<int, array{peserta: int, valid: int, menunggu: int, sesi: int, terjadwal: float, wajib: float, masalah: list<string>, siap: bool}>
     */
    public function untuk(Collection $batch): array
    {
        $idBatch = $batch->pluck('id_batch');

        $peserta = DB::table('tbl_daftar')
            ->whereIn('id_batch', $idBatch)
            ->where(fn ($q) => $q->whereNull('status')->orWhere('status', '<>', StatusDaftar::Dropped->value))
            ->groupBy('id_batch')
            ->selectRaw('id_batch, COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as menunggu', [StatusDaftar::Registered->value])
            ->get()
            ->keyBy('id_batch');

        $sesi = DB::table('tbl_sesi_batch as sb')
            ->join('tbl_sesi as s', 's.id_sesi', '=', 'sb.id_sesi')
            ->whereIn('sb.id_batch', $idBatch)
            ->where(fn ($q) => $q->whereNull('s.status')->orWhere('s.status', '<>', StatusSesi::Cancelled->value))
            ->groupBy('sb.id_batch')
            ->selectRaw('sb.id_batch as id_batch, COUNT(*) as jumlah, SUM(s.durasi_hari) as durasi')
            ->get()
            ->keyBy('id_batch');

        return $batch->mapWithKeys(function (Batch $b) use ($peserta, $sesi) {
            $total = (int) ($peserta[$b->id_batch]->total ?? 0);
            $menunggu = (int) ($peserta[$b->id_batch]->menunggu ?? 0);
            $jumlahSesi = (int) ($sesi[$b->id_batch]->jumlah ?? 0);
            $terjadwal = (float) ($sesi[$b->id_batch]->durasi ?? 0);
            $wajib = (float) (strtolower($b->mode) === 'online' ? $b->skema->durasi_online : $b->skema->durasi_offline);

            $masalah = [];
            if ($total === 0) {
                $masalah[] = 'Belum ada peserta';
            } elseif ($menunggu > 0) {
                $masalah[] = "{$menunggu} menunggu validasi";
            }
            if ($jumlahSesi === 0) {
                $masalah[] = 'Belum ada sesi';
            } elseif ($terjadwal + self::TOLERANSI < $wajib) {
                $masalah[] = 'Durasi kurang '.$this->hari($wajib - $terjadwal);
            } elseif ($terjadwal - self::TOLERANSI > $wajib) {
                $masalah[] = 'Durasi lebih '.$this->hari($terjadwal - $wajib);
            }

            return [$b->id_batch => [
                'peserta' => $total,
                'valid' => $total - $menunggu,
                'menunggu' => $menunggu,
                'sesi' => $jumlahSesi,
                'terjadwal' => $terjadwal,
                'wajib' => $wajib,
                'masalah' => $masalah,
                'siap' => $masalah === [],
            ]];
        })->all();
    }

    private function hari(float $nilai): string
    {
        return rtrim(rtrim(number_format($nilai, 1, ',', ''), '0'), ',').' hari';
    }
}
