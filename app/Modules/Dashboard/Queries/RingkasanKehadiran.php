<?php

namespace App\Modules\Dashboard\Queries;

use App\Modules\Penjadwalan\Models\Sesi;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Persentase Hadir, Izin, dan Alpa pada sesi yang sudah berjalan
 * (tanggal sesi sama dengan atau sebelum hari ini).
 */
class RingkasanKehadiran
{
    /**
     * @param  Collection<int, Sesi>  $sesi
     * @return array<string, int>
     */
    public function hitung(Collection $sesi, Carbon $hariIni): array
    {
        $sudahBerjalan = $sesi->filter(fn (Sesi $s) => $s->tanggal->lessThanOrEqualTo($hariIni))->pluck('id_sesi');

        $jumlah = DB::table('tbl_kehadiran')
            ->whereIn('id_sesi', $sudahBerjalan)
            ->selectRaw('status_hadir, COUNT(*) as jumlah')
            ->groupBy('status_hadir')
            ->pluck('jumlah', 'status_hadir');

        $hadir = (int) ($jumlah['Hadir'] ?? 0);
        $izin = (int) ($jumlah['Izin'] ?? 0);
        $alpa = (int) ($jumlah['Alpa'] ?? 0);
        $total = $hadir + $izin + $alpa;
        $persen = fn (int $n) => $total > 0 ? (int) round($n / $total * 100) : 0;

        return [
            'total' => $total, 'hadir' => $hadir, 'izin' => $izin, 'alpa' => $alpa,
            'persen_hadir' => $persen($hadir), 'persen_izin' => $persen($izin), 'persen_alpa' => $persen($alpa),
        ];
    }
}
