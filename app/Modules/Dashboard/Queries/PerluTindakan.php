<?php

namespace App\Modules\Dashboard\Queries;

use App\Modules\Katalog\Models\Batch;
use Illuminate\Support\Collection;

/**
 * Hal yang perlu ditindaklanjuti pada periode aktif, supaya dashboard
 * langsung menjawab "apa yang harus dikerjakan". Hanya membaca.
 */
class PerluTindakan
{
    /**
     * @param  Collection<int, array<string, mixed>>  $progress  baris durasi per skema (dari DurasiSkema)
     * @return array{durasi: list<array<string, mixed>>, batchKosong: list<string>, jumlahBatch: int, jumlah: int}
     */
    public function susun(string $periode, Collection $progress): array
    {
        $durasi = $progress
            ->filter(fn (array $b) => $b['selisih'] < 0 && ! $b['ok'])
            ->map(fn (array $b) => [
                'skema' => $b['skema'], 'mode' => $b['mode'],
                'wajib' => $b['wajib'], 'terjadwal' => $b['terjadwal'], 'kurang' => abs($b['selisih']),
            ])
            ->values()
            ->all();

        $batch = Batch::with('skema')->withCount('peserta')->where('periode', $periode)->get();
        $kosong = $batch->filter(fn (Batch $b) => $b->peserta_count === 0)
            ->map(fn (Batch $b) => $b->skema->nama_skema)
            ->values()
            ->all();

        return [
            'durasi' => $durasi,
            'batchKosong' => $kosong,
            'jumlahBatch' => $batch->count(),
            'jumlah' => ($durasi !== [] ? 1 : 0) + ($kosong !== [] ? 1 : 0),
        ];
    }
}
