<?php

namespace App\Modules\Penjadwalan\Actions;

use App\Shared\Enums\Mode;

class HitungDurasiSesi
{
    /**
     * Durasi sesi dalam hari, dihitung dari mode dan jam supaya tidak
     * bergantung pada angka yang diketik admin.
     * Online: satu sesi sama dengan satu hari pelatihan.
     * Offline: sampai 4 jam dihitung setengah hari, selebihnya satu hari.
     */
    public function execute(string $mode, string $jamMulai, string $jamSelesai): float
    {
        if ($mode === Mode::Online->value) {
            return 1.0;
        }

        $jam = (strtotime($jamSelesai) - strtotime($jamMulai)) / 3600;

        return $jam <= 4 ? 0.5 : 1.0;
    }
}
