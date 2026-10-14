<?php

namespace App\Modules\Penjadwalan\Queries;

use App\Modules\Katalog\Models\Batch;
use App\Shared\Enums\StatusSesi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Durasi wajib (master skema) dibanding durasi terjadwal (jumlah durasi sesi
 * yang melayani batch skema itu). Dihitung dari data, tidak disimpan.
 *
 * Sesi bersama dihitung ke setiap batch yang dilayaninya, tetapi hanya
 * sekali per batch karena tbl_sesi_batch memiliki PK (id_batch, id_sesi).
 * Sesi berstatus Cancelled tidak dihitung.
 */
class DurasiSkema
{
    private const TOLERANSI = 0.001;

    /**
     * `selesai` menghitung hanya sesi berstatus Completed.
     *
     * @return Collection<int, array<string, mixed>> kunci: id_batch, skema, periode, mode, wajib, terjadwal, selesai, selisih, ok
     */
    public function untukPeriode(string $periode): Collection
    {
        $terjadwal = DB::table('tbl_sesi_batch as sb')
            ->join('tbl_sesi as s', 's.id_sesi', '=', 'sb.id_sesi')
            ->where(fn ($q) => $q->whereNull('s.status')->orWhere('s.status', '<>', StatusSesi::Cancelled->value))
            ->groupBy('sb.id_batch')
            ->selectRaw('sb.id_batch as id_batch, SUM(s.durasi_hari) as total')
            ->pluck('total', 'id_batch');

        $selesai = DB::table('tbl_sesi_batch as sb')
            ->join('tbl_sesi as s', 's.id_sesi', '=', 'sb.id_sesi')
            ->where('s.status', StatusSesi::Completed->value)
            ->groupBy('sb.id_batch')
            ->selectRaw('sb.id_batch as id_batch, SUM(s.durasi_hari) as total')
            ->pluck('total', 'id_batch');

        return Batch::with('skema')
            ->where('periode', $periode)
            ->orderBy('id_skema')->orderBy('mode')
            ->get()
            ->map(fn (Batch $batch) => $this->baris($batch, (float) ($terjadwal[$batch->id_batch] ?? 0), (float) ($selesai[$batch->id_batch] ?? 0)))
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function baris(Batch $batch, float $terjadwal, float $selesai): array
    {
        $skema = $batch->skema;
        $wajib = (float) (strtolower($batch->mode) === 'online' ? $skema->durasi_online : $skema->durasi_offline);

        return [
            'id_batch' => $batch->id_batch,
            'skema' => $skema->nama_skema,
            'periode' => $batch->periode,
            'mode' => $batch->mode,
            'wajib' => $wajib,
            'terjadwal' => $terjadwal,
            'selesai' => $selesai,
            'selisih' => $terjadwal - $wajib,
            'ok' => abs($terjadwal - $wajib) < self::TOLERANSI,
        ];
    }

    /**
     * Periode aktif: periode terawal yang masih memiliki batch Planned atau
     * Ongoing; bila tidak ada, periode terakhir yang ada.
     */
    public function periodeAktif(): ?string
    {
        return Batch::whereIn('status', ['Planned', 'Ongoing'])->min('periode')
            ?? Batch::max('periode');
    }

    public function daftarPeriode(): Collection
    {
        return Batch::query()->distinct()->orderBy('periode')->pluck('periode');
    }
}
