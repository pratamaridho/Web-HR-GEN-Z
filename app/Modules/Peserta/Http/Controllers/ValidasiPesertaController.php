<?php

namespace App\Modules\Peserta\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Peserta\Actions\ValidasiPeserta;
use App\Modules\Peserta\Http\Requests\ValidasiPesertaRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Memvalidasi pendaftaran peserta pada satu batch: satu per satu atau semua.
 */
class ValidasiPesertaController extends Controller
{
    public function __invoke(ValidasiPesertaRequest $request, ValidasiPeserta $validasi): RedirectResponse
    {
        $ids = $request->filled('id_peserta') ? array_map('intval', $request->input('id_peserta')) : null;

        $jumlah = $validasi->execute($request->integer('id_batch'), $ids);

        return back()->with('sukses', $jumlah > 0
            ? "{$jumlah} peserta divalidasi."
            : 'Tidak ada peserta yang menunggu validasi.');
    }
}
