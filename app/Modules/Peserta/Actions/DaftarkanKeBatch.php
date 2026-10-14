<?php

namespace App\Modules\Peserta\Actions;

use App\Modules\Katalog\Models\Batch;
use App\Modules\Peserta\Models\Daftar;
use App\Modules\Peserta\Models\Peserta;
use App\Shared\Enums\StatusDaftar;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Daftarkan beberapa peserta ke satu batch. Semua atau tidak sama sekali.
 *
 * Aturan: satu peserta hanya boleh punya satu batch per periode dan mode.
 * Bila ada yang sudah punya batch pada periode dan mode itu (termasuk batch
 * yang sama), tidak ada yang disimpan.
 */
class DaftarkanKeBatch
{
    /**
     * @param  array<int>  $idPeserta
     * @return Collection<int, string> alasan penolakan per peserta (kosong = berhasil)
     */
    public function execute(array $idPeserta, Batch $batch): Collection
    {
        $masalah = Peserta::whereIn('id_peserta', $idPeserta)
            ->with(['batches' => fn ($q) => $q
                ->where('tbl_batch.periode', $batch->periode)
                ->where('tbl_batch.mode', $batch->mode)
                ->with('skema')])
            ->orderBy('id_peserta')
            ->get()
            ->filter(fn (Peserta $p) => $p->batches->isNotEmpty())
            ->map(fn (Peserta $p) => "{$p->nama} sudah di {$p->batches->first()->nama}.")
            ->values();

        if ($masalah->isNotEmpty()) {
            return $masalah;
        }

        DB::transaction(function () use ($idPeserta, $batch) {
            foreach ($idPeserta as $id) {
                Daftar::create([
                    'id_batch' => $batch->id_batch,
                    'id_peserta' => $id,
                    'status' => StatusDaftar::Registered->value,
                ]);
            }
        });

        return collect();
    }
}
