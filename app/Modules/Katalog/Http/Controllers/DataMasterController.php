<?php

namespace App\Modules\Katalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Katalog\Actions\BuatBatch;
use App\Modules\Katalog\Actions\BuatBatchBulanan;
use App\Modules\Katalog\Http\Requests\BuatBatchBulananRequest;
use App\Modules\Katalog\Http\Requests\BuatBatchRequest;
use App\Modules\Katalog\Http\Requests\UbahStatusBatchRequest;
use App\Modules\Katalog\Models\Batch;
use App\Modules\Katalog\Models\Skema;
use App\Modules\Katalog\Models\Trainer;
use App\Modules\Katalog\Queries\RingkasanBatch;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * FR 02 (skema), FR 03 (trainer), FR 04 (batch). Tidak ada aksi hapus:
 * histori batch tidak boleh hilang.
 */
class DataMasterController extends Controller
{
    public function index(RingkasanBatch $ringkasan): View
    {
        $batch = Batch::with('skema')->orderBy('periode')->orderBy('id_batch')->get();

        return view('data-master', [
            'skema' => Skema::orderBy('id_skema')->get(),
            'batch' => $batch,
            'ringkasan' => $ringkasan->untuk($batch),
            'trainer' => Trainer::withCount('sesi')->orderBy('id_trainer')->get(),
            'bulanBerikutnya' => ($terakhir = Batch::max('periode'))
                ? Carbon::createFromFormat('!Y-m', $terakhir)->addMonth()->format('Y-m')
                : now()->format('Y-m'),
        ]);
    }

    public function ubahStatusBatch(UbahStatusBatchRequest $request, Batch $batch): RedirectResponse
    {
        $batch->update(['status' => $request->validated()['status']]);

        return redirect()->route('data-master')
            ->with('sukses', "Status batch {$batch->skema->nama_skema} {$batch->periode} menjadi {$batch->status}.");
    }

    public function simpanBatchBulanan(BuatBatchBulananRequest $request, BuatBatchBulanan $bulanan): RedirectResponse
    {
        $periode = $request->string('periode')->toString();
        $mode = $request->string('mode')->toString();

        $hasil = $bulanan->execute($periode, $mode);

        $pesan = "{$hasil['dibuat']} batch {$periode} ({$mode}) dibuat.";
        if ($hasil['sudah_ada'] > 0) {
            $pesan .= " {$hasil['sudah_ada']} sudah ada dan dilewati.";
        }

        return redirect()->route('data-master')->with('sukses', $pesan);
    }

    public function simpanBatch(BuatBatchRequest $request, BuatBatch $buatBatch): RedirectResponse
    {
        $skema = Skema::findOrFail($request->integer('id_skema'));
        $periode = $request->string('periode')->toString();
        $mode = $request->string('mode')->toString();

        $pesanGanda = "Batch sudah ada. {$skema->nama_skema} periode {$periode} mode {$mode} sudah terdaftar. Ganti periode atau mode.";

        $sudahAda = Batch::where('id_skema', $skema->id_skema)
            ->where('periode', $periode)->where('mode', $mode)->exists();

        if ($sudahAda) {
            return back()->withInput()->withErrors(['batch' => $pesanGanda]);
        }

        try {
            $buatBatch->execute($skema, $periode, $mode);
        } catch (UniqueConstraintViolationException) {
            // Pagar terakhir: constraint UNIQUE (klik ganda atau dua admin bersamaan).
            return back()->withInput()->withErrors(['batch' => $pesanGanda]);
        }

        return redirect()->route('data-master')
            ->with('sukses', "Batch {$skema->nama_skema} periode {$periode} mode {$mode} tersimpan.");
    }
}
