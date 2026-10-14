<?php

namespace App\Modules\Peserta\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Katalog\Models\Batch;
use App\Modules\Peserta\Actions\DaftarkanKeBatch;
use App\Modules\Peserta\Http\Requests\DaftarkanPesertaRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Memasukkan peserta yang sudah ada ke satu batch.
 */
class PendaftaranController extends Controller
{
    public function __invoke(DaftarkanPesertaRequest $request, DaftarkanKeBatch $daftarkan): RedirectResponse
    {
        $batch = Batch::with('skema')->findOrFail($request->integer('id_batch'));
        $ids = array_map('intval', $request->input('id_peserta'));
        $masalah = $daftarkan->execute($ids, $batch);

        if ($masalah->isNotEmpty()) {
            $masalah->push('Satu peserta hanya boleh punya satu batch per bulan dan mode. Tidak ada yang disimpan.');

            return back()->withInput()->withErrors(['daftar' => $masalah->implode("\n")]);
        }

        return redirect()->route('peserta')->with('sukses', count($ids)." peserta masuk {$batch->nama}.");
    }
}
