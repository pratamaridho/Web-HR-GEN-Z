<?php

namespace App\Modules\Dashboard\Queries;

use App\Modules\Katalog\Models\Batch;
use App\Modules\Penjadwalan\Models\Sesi;
use App\Modules\Penjadwalan\Queries\DurasiSkema;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Angka untuk dashboard (sembilan indikator). Semua dihitung dari data,
 * hanya membaca.
 *
 * Periode aktif = periode terawal yang masih punya batch Planned atau Ongoing.
 * Peserta dan sesi dihitung untuk batch pada periode itu.
 */
class RingkasanOperasional
{
    public function __construct(
        private readonly DurasiSkema $durasi,
        private readonly KonflikJadwal $konflik,
        private readonly RingkasanKehadiran $kehadiran,
        private readonly PerluTindakan $tindakan,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function hitung(?Carbon $sekarang = null): array
    {
        $hariIni = ($sekarang ?? now())->copy()->startOfDay();
        $periode = $this->durasi->periodeAktif();

        if ($periode === null) {
            return $this->kosong();
        }

        $idBatch = Batch::where('periode', $periode)->pluck('id_batch');

        $sesi = Sesi::with(['trainer', 'batches.skema'])
            ->whereHas('batches', fn ($b) => $b->whereIn('tbl_batch.id_batch', $idBatch))
            ->where(fn ($q) => $q->whereNull('status')->orWhere('status', '<>', 'Cancelled'))
            ->orderBy('tanggal')->orderBy('jam_mulai')->orderBy('id_sesi')
            ->get();

        $progress = $this->progress($periode);

        return [
            'periode' => $periode,
            'batch_aktif' => Batch::where('periode', $periode)->whereIn('status', ['Planned', 'Ongoing'])->count(),
            'peserta' => (int) DB::table('tbl_daftar')->whereIn('id_batch', $idBatch)->distinct()->count('id_peserta'),
            'sesi' => $sesi->count(),
            'mendatang' => $sesi->filter(fn (Sesi $s) => $s->tanggal->greaterThanOrEqualTo($hariIni))->values(),
            'alokasi' => $this->alokasi($sesi),
            'progress' => $progress,
            'tindakan' => $this->tindakan->susun($periode, $progress),
            'konflik' => $this->konflik->hitung($sesi),
            'kehadiran' => $this->kehadiran->hitung($sesi, $hariIni),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function kosong(): array
    {
        return [
            'periode' => null, 'batch_aktif' => 0, 'peserta' => 0, 'sesi' => 0,
            'mendatang' => collect(), 'alokasi' => collect(), 'progress' => collect(), 'konflik' => 0,
            'tindakan' => ['durasi' => [], 'batchKosong' => [], 'jumlahBatch' => 0, 'jumlah' => 0],
            'kehadiran' => ['total' => 0, 'hadir' => 0, 'izin' => 0, 'alpa' => 0,
                'persen_hadir' => 0, 'persen_izin' => 0, 'persen_alpa' => 0],
        ];
    }

    /** Jumlah sesi dan hari mengajar per trainer. */
    private function alokasi(Collection $sesi): Collection
    {
        return $sesi->groupBy('id_trainer')
            ->map(fn (Collection $milik) => [
                'trainer' => $milik->first()->trainer->nama,
                'sesi' => $milik->count(),
                'hari' => $milik->map(fn (Sesi $s) => $s->tanggal->toDateString())->unique()->count(),
            ])
            ->sortBy('trainer')
            ->values();
    }

    /** Persen durasi terjadwal dan selesai terhadap durasi wajib, per skema dan mode. */
    private function progress(string $periode): Collection
    {
        return $this->durasi->untukPeriode($periode)->map(function (array $baris) {
            $persen = fn (float $nilai) => $baris['wajib'] > 0 ? (int) round($nilai / $baris['wajib'] * 100) : 0;

            return $baris + [
                'persen_terjadwal' => $persen($baris['terjadwal']),
                'persen_selesai' => $persen($baris['selesai']),
            ];
        });
    }
}
