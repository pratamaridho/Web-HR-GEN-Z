<?php

namespace App\Modules\Peserta\Queries;

use App\Modules\Katalog\Models\Batch;
use App\Modules\Peserta\Models\Peserta;
use Illuminate\Support\Collection;

/**
 * Data Participant Mapping: peserta dikelompokkan per batch pada satu periode,
 * ditambah peserta yang belum masuk batch. Mencari atau memilih satu batch
 * menjangkau semua periode. Hanya membaca.
 */
class PesertaPerBatch
{
    /**
     * @return array{kartu: Collection<int, Batch>, tanpaBatch: Collection<int, Peserta>, daftarPeriode: Collection<int, string>, periode: string|null}
     */
    public function susun(string $q, ?int $idBatch, ?string $periodeDipilih): array
    {
        $daftarPeriode = Batch::query()->distinct()->orderBy('periode')->pluck('periode');
        $periodeAktif = Batch::whereIn('status', ['Planned', 'Ongoing'])->min('periode') ?? $daftarPeriode->last();

        $periode = $daftarPeriode->contains($periodeDipilih) ? $periodeDipilih : $periodeAktif;
        $semuaPeriode = $q !== '' || $idBatch !== null;
        $periodeTerpakai = $semuaPeriode ? null : $periode;

        $cocok = fn ($query) => $query->when($q !== '', fn ($w) => $w->where(
            fn ($x) => $x->where('nama', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")
        ));

        $kartu = Batch::with(['skema', 'peserta' => fn ($p) => $cocok($p)->orderBy('nama')])
            ->withCount('peserta')
            ->when($periodeTerpakai, fn ($b) => $b->where('periode', $periodeTerpakai))
            ->when($idBatch, fn ($b) => $b->where('id_batch', $idBatch))
            ->orderBy('periode')->orderBy('mode')->orderBy('id_skema')->orderBy('id_batch')
            ->get()
            ->when($q !== '', fn ($semua) => $semua->filter(fn (Batch $b) => $b->peserta->isNotEmpty())->values());

        $tanpaBatch = Peserta::query()
            ->whereDoesntHave('batches', fn ($b) => $b->when($periodeTerpakai, fn ($x) => $x->where('periode', $periodeTerpakai)))
            ->tap($cocok)
            ->when($idBatch, fn ($p) => $p->whereRaw('1 = 0'))
            ->orderBy('nama')
            ->get();

        return [
            'kartu' => $kartu,
            'tanpaBatch' => $tanpaBatch,
            'daftarPeriode' => $daftarPeriode,
            'periode' => $periodeTerpakai,
        ];
    }
}
