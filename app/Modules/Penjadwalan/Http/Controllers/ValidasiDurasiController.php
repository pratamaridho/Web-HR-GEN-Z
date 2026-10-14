<?php

namespace App\Modules\Penjadwalan\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Katalog\Models\Batch;
use App\Modules\Penjadwalan\Queries\AturanIntegrasi;
use App\Modules\Penjadwalan\Queries\DurasiSkema;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * FR 09: durasi wajib dibanding terjadwal per skema dan mode.
 */
class ValidasiDurasiController extends Controller
{
    public function index(Request $request, DurasiSkema $durasi, AturanIntegrasi $aturan): View
    {
        $periode = (string) ($request->query('periode') ?: $durasi->periodeAktif());
        $baris = $periode === '' ? collect() : $durasi->untukPeriode($periode);

        return view('validasi-durasi', [
            'baris' => $baris,
            'periode' => $periode,
            'daftarPeriode' => $durasi->daftarPeriode(),
            'jumlahOk' => $baris->where('ok', true)->count(),
            'integrasi' => $periode === '' ? [] : Batch::where('periode', $periode)->distinct()->orderBy('mode')->pluck('mode')
                ->mapWithKeys(fn ($m) => [$m => $aturan->untuk($periode, $m)])->all(),
        ]);
    }
}
