<?php

namespace App\Modules\Kehadiran\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Katalog\Models\Batch;
use App\Modules\Kehadiran\Actions\KoreksiKehadiran;
use App\Modules\Kehadiran\Http\Requests\KoreksiKehadiranRequest;
use App\Modules\Kehadiran\Queries\RekapKehadiran;
use App\Modules\Peserta\Models\Peserta;
use App\Shared\Enums\StatusKehadiran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * FR 12: rekap dan koreksi kehadiran oleh Admin.
 */
class RekapKehadiranController extends Controller
{
    public function index(Request $request, RekapKehadiran $rekap): View
    {
        $cari = trim((string) $request->query('q'));
        $idBatch = $request->integer('batch') ?: null;

        $daftarPeriode = Batch::query()->distinct()->orderBy('periode')->pluck('periode');
        $periodeAktif = Batch::whereIn('status', ['Planned', 'Ongoing'])->min('periode') ?? $daftarPeriode->last();

        // Mencari atau memilih satu batch menjangkau semua periode; selain itu satu periode.
        $semuaPeriode = $cari !== '' || $idBatch !== null;
        $periode = $daftarPeriode->contains($request->query('periode')) ? $request->query('periode') : $periodeAktif;

        return view('rekap-kehadiran', [
            'kelompok' => $rekap->perBatch($cari !== '' ? $cari : null, $idBatch, $semuaPeriode ? null : $periode),
            'daftarBatch' => Batch::with('skema')->orderBy('periode')->orderBy('id_skema')->get(),
            'daftarPeriode' => $daftarPeriode,
            'periode' => $semuaPeriode ? null : $periode,
            'q' => $cari,
            'filterBatch' => $idBatch,
        ]);
    }

    public function form(Peserta $peserta, Batch $batch): View
    {
        $this->pastikanTerdaftar($peserta, $batch);

        return view('koreksi-kehadiran', [
            'peserta' => $peserta,
            'batch' => $batch->load('skema'),
            'sesi' => $batch->sesi()->orderBy('tanggal')->orderBy('jam_mulai')->orderBy('tbl_sesi.id_sesi')->get(),
            'tercatat' => DB::table('tbl_kehadiran')->where('id_peserta', $peserta->id_peserta)->pluck('status_hadir', 'id_sesi'),
            'pilihan' => array_column(StatusKehadiran::cases(), 'value'),
        ]);
    }

    public function simpan(KoreksiKehadiranRequest $request, Peserta $peserta, Batch $batch, KoreksiKehadiran $koreksi): RedirectResponse
    {
        $this->pastikanTerdaftar($peserta, $batch);

        $input = $request->validated()['kehadiran'];
        $idSesiBatch = $batch->sesi()->pluck('tbl_sesi.id_sesi')->map(fn ($id) => (int) $id)->all();

        if (array_diff(array_map('intval', array_keys($input)), $idSesiBatch) !== []) {
            return back()->withInput()->withErrors([
                'kehadiran' => 'Ada sesi yang bukan bagian dari batch peserta ini. Koreksi tidak disimpan.',
            ]);
        }

        $koreksi->execute($peserta->id_peserta, $batch->id_batch, $input);

        return redirect()->route('rekap-kehadiran')
            ->with('sukses', "Kehadiran {$peserta->nama} dikoreksi.");
    }

    private function pastikanTerdaftar(Peserta $peserta, Batch $batch): void
    {
        abort_unless(
            DB::table('tbl_daftar')->where('id_peserta', $peserta->id_peserta)->where('id_batch', $batch->id_batch)->exists(),
            404
        );
    }
}
