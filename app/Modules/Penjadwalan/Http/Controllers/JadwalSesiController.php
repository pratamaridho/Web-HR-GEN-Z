<?php

namespace App\Modules\Penjadwalan\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Katalog\Models\Skema;
use App\Modules\Katalog\Models\Trainer;
use App\Modules\Penjadwalan\Models\Sesi;
use App\Modules\Penjadwalan\Queries\KalenderBulan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * FR 07: jadwal sesi, dalam bentuk kalender bulanan atau daftar per hari.
 */
class JadwalSesiController extends Controller
{
    public function index(Request $request, KalenderBulan $kalender): View
    {
        $skema = $request->integer('skema') ?: null;
        $trainer = $request->integer('trainer') ?: null;
        $tampil = $request->query('tampil') === 'daftar' ? 'daftar' : 'kalender';

        // Parallel: dua sesi atau lebih pada tanggal dan jam mulai yang sama.
        $paralel = DB::table('tbl_sesi')->select('tanggal', 'jam_mulai')
            ->where(fn ($q) => $q->whereNull('status')->orWhere('status', '<>', 'Cancelled'))
            ->groupBy('tanggal', 'jam_mulai')->havingRaw('COUNT(*) > 1')->get()
            ->mapWithKeys(fn ($r) => [substr((string) $r->tanggal, 0, 10).'|'.$r->jam_mulai => true]);

        $data = [
            'tampil' => $tampil,
            'paralel' => $paralel,
            'daftarSkema' => Skema::orderBy('id_skema')->get(),
            'daftarTrainer' => Trainer::orderBy('id_trainer')->get(),
            'filterSkema' => $skema,
            'filterTrainer' => $trainer,
        ];

        if ($tampil === 'kalender') {
            $bulan = $kalender->bulanAwal($request->query('bulan'));
            $data['kalender'] = $kalender->susun($bulan, $skema, $trainer);
            $data['bulan'] = $bulan;
        } else {
            $data['perHari'] = Sesi::with(['trainer', 'batches.skema'])
                ->when($skema, fn ($q) => $q->whereHas('batches', fn ($b) => $b->where('tbl_batch.id_skema', $skema)))
                ->when($trainer, fn ($q) => $q->where('id_trainer', $trainer))
                ->orderBy('tanggal')->orderBy('jam_mulai')->orderBy('id_sesi')
                ->get()
                ->groupBy(fn (Sesi $s) => $s->tanggal->toDateString());
        }

        return view('jadwal-sesi', $data);
    }
}
