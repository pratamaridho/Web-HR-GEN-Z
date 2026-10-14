<?php

namespace App\Modules\Peserta\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Katalog\Models\Batch;
use App\Modules\Peserta\Actions\ImporPeserta;
use App\Modules\Peserta\Http\Requests\ImporPesertaRequest;
use App\Modules\Peserta\Http\Requests\TambahPesertaRequest;
use App\Modules\Peserta\Models\Peserta;
use App\Modules\Peserta\Queries\PesertaPerBatch;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Data peserta dan pendaftaran ke batch. Peserta tidak punya akun.
 */
class PesertaController extends Controller
{
    public function index(Request $request, PesertaPerBatch $perBatch): View
    {
        $q = trim((string) $request->query('q'));
        $idBatch = $request->integer('batch') ?: null;

        return view('peserta', $perBatch->susun($q, $idBatch, $request->query('periode')) + [
            'semuaPeserta' => Peserta::orderBy('nama')->get(['id_peserta', 'nama', 'email']),
            'daftarBatch' => Batch::with('skema')->orderBy('periode')->orderBy('mode')->orderBy('id_batch')->get(),
            'q' => $q,
            'filterBatch' => $idBatch,
        ]);
    }

    /** Perjalanan satu peserta: batch, status validasi, dan sesi per hari. */
    public function alur(Peserta $peserta): View
    {
        $peserta->load(['batches.skema', 'batches.sesi.trainer']);

        return view('peserta.alur', [
            'peserta' => $peserta,
            'kehadiran' => DB::table('tbl_kehadiran')->where('id_peserta', $peserta->id_peserta)->pluck('status_hadir', 'id_sesi'),
        ]);
    }

    public function simpan(TambahPesertaRequest $request, ImporPeserta $impor): RedirectResponse
    {
        $nama = trim($request->string('nama')->toString());
        $email = trim($request->string('email')->toString());
        $batch = $this->batchDipilih($request);

        if ($pemilik = Peserta::where('email', $email)->first()) {
            return $this->emailGanda($email, $pemilik);
        }

        try {
            $impor->execute("{$nama}, {$email}", $batch);
        } catch (UniqueConstraintViolationException) {
            // Dua admin menyimpan email yang sama bersamaan: database yang menolak.
            return $this->emailGanda($email, Peserta::where('email', $email)->first());
        }

        return redirect()->route('peserta')->with('sukses', "Peserta {$nama} tersimpan".$this->akhiran($batch).'.');
    }

    public function impor(ImporPesertaRequest $request, ImporPeserta $impor): RedirectResponse
    {
        $batch = $this->batchDipilih($request);
        $hasil = $impor->execute($request->string('tempel')->toString(), $batch);

        if ($hasil['galat'] !== []) {
            return back()->withInput()->withErrors([
                'tempel' => "Tidak ada peserta yang disimpan.\n".implode("\n", $hasil['galat']),
            ]);
        }

        return redirect()->route('peserta')
            ->with('sukses', "{$hasil['jumlah']} peserta ditambahkan".$this->akhiran($batch).'.');
    }

    private function batchDipilih(Request $request): ?Batch
    {
        return $request->filled('id_batch') ? Batch::with('skema')->find($request->integer('id_batch')) : null;
    }

    /** Kalimat tambahan "dan didaftarkan ke batch BP 2026-10 (Online)". */
    private function akhiran(?Batch $batch): string
    {
        return $batch ? " dan didaftarkan ke batch {$batch->skema->nama_skema} {$batch->periode} ({$batch->mode})" : '';
    }

    private function emailGanda(string $email, ?Peserta $pemilik): RedirectResponse
    {
        $nama = $pemilik->nama ?? 'peserta lain';

        return back()->withInput()->withErrors([
            'email' => "Email {$email} sudah tercatat atas nama {$nama}. Satu orang hanya boleh punya satu data peserta.",
        ]);
    }
}
