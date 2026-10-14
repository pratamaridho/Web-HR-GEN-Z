<?php

namespace App\Modules\Katalog\Actions;

use App\Modules\Katalog\Models\Batch;
use App\Modules\Katalog\Models\Skema;
use Illuminate\Support\Facades\DB;

/**
 * Buat satu batch per skema untuk satu periode dan mode sekaligus
 * (PRD 6.1, satu batch per skema per bulan). Yang sudah ada dilewati,
 * sehingga aman dijalankan ulang.
 */
class BuatBatchBulanan
{
    public function __construct(private readonly BuatBatch $buatBatch) {}

    /**
     * @return array{dibuat:int, sudah_ada:int}
     */
    public function execute(string $periode, string $mode): array
    {
        return DB::transaction(function () use ($periode, $mode) {
            $dibuat = 0;
            $sudahAda = 0;

            foreach (Skema::orderBy('id_skema')->get() as $skema) {
                $ada = Batch::where('id_skema', $skema->id_skema)
                    ->where('periode', $periode)->where('mode', $mode)->exists();

                if ($ada) {
                    $sudahAda++;

                    continue;
                }

                $this->buatBatch->execute($skema, $periode, $mode);
                $dibuat++;
            }

            return ['dibuat' => $dibuat, 'sudah_ada' => $sudahAda];
        });
    }
}
