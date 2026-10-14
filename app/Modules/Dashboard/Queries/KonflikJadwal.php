<?php

namespace App\Modules\Dashboard\Queries;

use App\Modules\Penjadwalan\Models\Sesi;
use Illuminate\Support\Collection;

/**
 * Menghitung sesi bermasalah dari data, walau constraint UNIQUE dan validasi
 * aplikasi seharusnya sudah mencegahnya. Hasil normal: 0.
 */
class KonflikJadwal
{
    /**
     * Sesi bermasalah: trainer atau room ganda pada tanggal dan jam mulai
     * yang sama, atau melayani batch dengan mode berbeda.
     *
     * @param  Collection<int, Sesi>  $sesi  harus memuat relasi batches
     */
    public function hitung(Collection $sesi): int
    {
        $ganda = fn (string $kolom) => $sesi
            ->groupBy(fn (Sesi $s) => $s->tanggal->toDateString().'|'.$s->jam_mulai.'|'.$s->{$kolom})
            ->filter(fn (Collection $g) => $g->count() > 1)
            ->flatten(1)->pluck('id_sesi');

        $campurMode = $sesi
            ->filter(fn (Sesi $s) => $s->batches->pluck('mode')->unique()->count() > 1)
            ->pluck('id_sesi');

        return $ganda('id_trainer')->merge($ganda('room'))->merge($campurMode)->unique()->count();
    }
}
