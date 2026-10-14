<?php

namespace App\Modules\Penjadwalan\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Katalog\Models\Batch;
use App\Modules\Katalog\Models\Trainer;
use App\Modules\Penjadwalan\Actions\SimpanSesi;
use App\Modules\Penjadwalan\Actions\UbahSesi;
use App\Modules\Penjadwalan\Http\Requests\SimpanSesiRequest;
use App\Modules\Penjadwalan\Models\Sesi;
use App\Shared\Enums\StatusSesi;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * FR 07 (form sesi) dan FR 08 (cek konflik). Tidak ada hapus sesi.
 */
class SesiController extends Controller
{
    private const PESAN_PAGAR = 'Sesi tidak disimpan. Trainer atau room yang sama sudah dipakai pada jam ini. Ubah trainer, room, atau jam sesi.';

    public function form(?Sesi $sesi = null): View
    {
        return view('form-sesi', [
            'sesi' => $sesi,
            'idBatchTerpilih' => $sesi?->batches()->pluck('tbl_batch.id_batch')->all() ?? [],
            'daftarBatch' => Batch::with('skema')->orderBy('periode')->orderBy('mode')->orderBy('id_batch')->get(),
            'daftarTrainer' => Trainer::orderBy('id_trainer')->get(),
            'daftarRoom' => config('hrgz.rooms'),
            // Bawaan: tanggal sesi terakhir, supaya sesi paralel pada hari itu cepat ditambah.
            'tanggalBawaan' => ($t = Sesi::max('tanggal')) ? substr((string) $t, 0, 10) : now()->toDateString(),
            'daftarStatus' => [StatusSesi::Planned->value, StatusSesi::Cancelled->value],
        ]);
    }

    public function simpan(SimpanSesiRequest $request, SimpanSesi $simpanSesi): RedirectResponse
    {
        $a = $request->atributSesi();

        try {
            $simpanSesi->execute(
                $a['id_trainer'], $a['nama_sesi'], $a['tanggal'], $a['jam_mulai'], $a['jam_selesai'],
                $a['durasi_hari'], $a['room'], $request->idBatch(), $a['status'] ?? null, $a['catatan'],
            );
        } catch (UniqueConstraintViolationException) {
            // Pagar terakhir: constraint UNIQUE bila dua permintaan lolos validasi bersamaan.
            return back()->withInput()->withErrors(['id_trainer' => $this->pesanPagar($a)]);
        }

        return redirect()->route('jadwal-sesi')
            ->with('sukses', "Sesi {$a['nama_sesi']} tersimpan.");
    }

    public function ubah(SimpanSesiRequest $request, Sesi $sesi, UbahSesi $ubahSesi): RedirectResponse
    {
        $a = $request->atributSesi();

        try {
            $ubahSesi->execute($sesi, $a, $request->idBatch());
        } catch (UniqueConstraintViolationException) {
            return back()->withInput()->withErrors(['id_trainer' => $this->pesanPagar($a, $sesi->id_sesi)]);
        }

        return redirect()->route('jadwal-sesi')
            ->with('sukses', "Sesi {$sesi->nama_sesi} diperbarui.");
    }

    /**
     * Pesan untuk constraint UNIQUE. Database tidak bisa mengecualikan sesi
     * Cancelled, jadi jam mulai yang persis sama dengan sesi batal tetap
     * ditahan; pesannya menyebut sesi batal itu.
     *
     * @param  array<string, mixed>  $a
     */
    private function pesanPagar(array $a, ?int $kecuali = null): string
    {
        $batal = Sesi::where('status', StatusSesi::Cancelled->value)
            ->whereDate('tanggal', $a['tanggal'])
            ->where('jam_mulai', $a['jam_mulai'])
            ->where(fn ($q) => $q->where('id_trainer', $a['id_trainer'])->orWhere('room', $a['room']))
            ->when($kecuali, fn ($q, $id) => $q->where('id_sesi', '!=', $id))
            ->first();

        return $batal
            ? "Sesi tidak disimpan. Jam mulai ini masih tercatat pada sesi yang dibatalkan: {$batal->nama_sesi}. Pilih jam mulai lain, atau ubah sesi yang dibatalkan itu."
            : self::PESAN_PAGAR;
    }
}
