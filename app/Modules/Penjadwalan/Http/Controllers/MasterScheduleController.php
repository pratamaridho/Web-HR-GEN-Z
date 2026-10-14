<?php

namespace App\Modules\Penjadwalan\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Penjadwalan\Queries\AturanIntegrasi;
use App\Modules\Penjadwalan\Queries\MasterSchedule;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Master Schedule skenario Online dan Offline per periode (deliverable 1 dan 2). Baca saja.
 */
class MasterScheduleController extends Controller
{
    public function __invoke(Request $request, MasterSchedule $jadwal, AturanIntegrasi $integrasi): View
    {
        $mode = $request->query('mode') === 'Offline' ? 'Offline' : 'Online';

        $daftarPeriode = $jadwal->daftarPeriode($mode);
        $periode = $daftarPeriode->contains($request->query('periode'))
            ? $request->query('periode')
            : $daftarPeriode->first();

        $data = $jadwal->untukMode($mode, $periode);

        return view('master-schedule', [
            'mode' => $mode,
            'periode' => $periode,
            'daftarPeriode' => $daftarPeriode,
            'baris' => $data['baris'],
            'ringkasan' => $data['ringkasan'],
            'durasi' => $data['durasi'],
            'integrasi' => $periode === null ? [] : [$periode => $integrasi->untuk($periode, $mode)],
        ]);
    }
}
