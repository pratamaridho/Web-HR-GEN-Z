<?php

namespace App\Modules\Kehadiran\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Kehadiran\Actions\CatatKehadiran;
use App\Modules\Kehadiran\Http\Requests\CatatKehadiranRequest;
use App\Modules\Kehadiran\Models\Kehadiran;
use App\Modules\Penjadwalan\Models\Sesi;
use App\Modules\Peserta\Models\Peserta;
use App\Shared\Enums\StatusDaftar;
use App\Shared\Enums\StatusKehadiran;
use App\Shared\Enums\StatusSesi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * FR 11: jadwal mengajar dan pencatatan kehadiran oleh Trainer pada sesinya
 * sendiri. Daftar peserta berasal dari batch yang dilayani sesi.
 */
class KehadiranController extends Controller
{
    public function jadwalSaya(Request $request): View
    {
        $sesi = Sesi::with('batches.skema')
            ->where('id_trainer', $request->user()->trainer_id)
            ->orderBy('tanggal')->orderBy('jam_mulai')->orderBy('id_sesi')
            ->get();

        $jumlahPeserta = $sesi->mapWithKeys(fn (Sesi $s) => [
            $s->id_sesi => $this->queryPeserta($s)->count(),
        ]);

        return view('jadwal-saya', ['sesi' => $sesi, 'jumlahPeserta' => $jumlahPeserta]);
    }

    public function form(Sesi $sesi): View
    {
        Gate::authorize('input', [Kehadiran::class, $sesi]);

        $sesi->load('batches.skema');

        return view('kehadiran', [
            'sesi' => $sesi,
            'peserta' => $this->queryPeserta($sesi)->with(['batches' => fn ($b) => $b->with('skema')])->orderBy('nama')->get(),
            'tercatat' => Kehadiran::where('id_sesi', $sesi->id_sesi)->pluck('status_hadir', 'id_peserta'),
            'pilihan' => array_column(StatusKehadiran::cases(), 'value'),
        ]);
    }

    public function simpan(CatatKehadiranRequest $request, Sesi $sesi, CatatKehadiran $catat): RedirectResponse
    {
        // Usulan OP 02: Trainer mencatat sampai 24 jam setelah sesi selesai;
        // sesudahnya, atau bila sesi dibatalkan, koreksi dilakukan Admin (FR 12).
        if ($sesi->kehadiranTerkunci()) {
            return back()->withErrors([
                'kehadiran' => $sesi->status === StatusSesi::Cancelled->value
                    ? 'Sesi ini dibatalkan, jadi kehadiran tidak dicatat.'
                    : 'Batas 24 jam setelah sesi selesai sudah lewat. Kehadiran hanya dapat dikoreksi oleh Admin.',
            ]);
        }

        $input = $request->validated()['kehadiran'];

        $diizinkan = $this->queryPeserta($sesi)->pluck('tbl_peserta.id_peserta')->map(fn ($id) => (int) $id)->all();
        $luar = array_diff(array_map('intval', array_keys($input)), $diizinkan);

        if ($luar !== []) {
            return back()->withInput()->withErrors([
                'kehadiran' => 'Ada peserta yang bukan bagian dari batch sesi ini. Kehadiran tidak disimpan.',
            ]);
        }

        $catat->execute($sesi, $input);

        return redirect()->route('kehadiran.form', $sesi)
            ->with('sukses', 'Kehadiran '.count($input).' peserta tersimpan.');
    }

    /**
     * Peserta yang terdaftar pada batch yang dilayani sesi (tanpa duplikat).
     */
    private function queryPeserta(Sesi $sesi)
    {
        $idBatch = $sesi->batches()->pluck('tbl_batch.id_batch');

        // Peserta yang masih menunggu validasi belum ikut sesi.
        return Peserta::whereHas('batches', fn ($b) => $b->whereIn('tbl_batch.id_batch', $idBatch)
            ->where(fn ($s) => $s->whereNull('tbl_daftar.status')->orWhere('tbl_daftar.status', '<>', StatusDaftar::Registered->value)));
    }
}
