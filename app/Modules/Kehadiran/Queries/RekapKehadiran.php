<?php

namespace App\Modules\Kehadiran\Queries;

use App\Shared\Enums\StatusSesi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * FR 12: rekap kehadiran per peserta, dikelompokkan per batch.
 *
 * Hadir/Izin/Alpa menghitung seluruh catatan pada sesi batch itu. Persentase
 * Hadir mengikuti definisi FR 13: Hadir terhadap sesi berstatus Completed.
 */
class RekapKehadiran
{
    /**
     * @return Collection<int, object{id_batch: int, skema: string, mode: string, periode: string, jumlah: int, rata_hadir: int, selesai: int, sesi_selesai: int, peserta: Collection<int, object>}>
     */
    public function perBatch(?string $cari, ?int $idBatch, ?string $periode): Collection
    {
        $daftar = DB::table('tbl_daftar as d')
            ->join('tbl_peserta as p', 'p.id_peserta', '=', 'd.id_peserta')
            ->join('tbl_batch as b', 'b.id_batch', '=', 'd.id_batch')
            ->join('tbl_skema as s', 's.id_skema', '=', 'b.id_skema')
            ->when($cari, fn ($q) => $q->where(
                fn ($w) => $w->where('p.nama', 'like', "%{$cari}%")->orWhere('p.email', 'like', "%{$cari}%")
            ))
            ->when($idBatch, fn ($q) => $q->where('d.id_batch', $idBatch))
            ->when($periode, fn ($q) => $q->where('b.periode', $periode))
            ->orderBy('b.periode')->orderBy('b.mode')->orderBy('b.id_skema')->orderBy('b.id_batch')
            ->orderBy('p.nama')->orderBy('p.id_peserta')
            ->select('d.id_peserta', 'd.id_batch', 'd.status', 'p.nama', 'p.email', 's.nama_skema', 'b.periode', 'b.mode')
            ->get();

        if ($daftar->isEmpty()) {
            return collect();
        }

        $catatan = DB::table('tbl_kehadiran as k')
            ->join('tbl_sesi_batch as sb', 'sb.id_sesi', '=', 'k.id_sesi')
            ->join('tbl_sesi as ss', 'ss.id_sesi', '=', 'k.id_sesi')
            ->whereIn('k.id_peserta', $daftar->pluck('id_peserta')->unique())
            ->whereIn('sb.id_batch', $daftar->pluck('id_batch')->unique())
            ->groupBy('k.id_peserta', 'sb.id_batch', 'k.status_hadir', 'ss.status')
            ->selectRaw('k.id_peserta, sb.id_batch, k.status_hadir, ss.status as status_sesi, COUNT(*) as jumlah')
            ->get();

        $sesiSelesai = DB::table('tbl_sesi_batch as sb')
            ->join('tbl_sesi as s', 's.id_sesi', '=', 'sb.id_sesi')
            ->where('s.status', StatusSesi::Completed->value)
            ->whereIn('sb.id_batch', $daftar->pluck('id_batch')->unique())
            ->groupBy('sb.id_batch')
            ->selectRaw('sb.id_batch, COUNT(*) as jumlah')
            ->pluck('jumlah', 'id_batch');

        return $daftar->groupBy('id_batch')->map(function (Collection $baris) use ($catatan, $sesiSelesai) {
            $pertama = $baris->first();
            $total = (int) ($sesiSelesai[$pertama->id_batch] ?? 0);

            $peserta = $baris->map(function ($r) use ($catatan, $total) {
                $milik = $catatan->where('id_peserta', $r->id_peserta)->where('id_batch', $r->id_batch);
                $hitung = fn (string $status) => (int) $milik->where('status_hadir', $status)->sum('jumlah');
                $hadirSelesai = (int) $milik->where('status_hadir', 'Hadir')->where('status_sesi', StatusSesi::Completed->value)->sum('jumlah');

                return (object) [
                    'id_peserta' => $r->id_peserta,
                    'id_batch' => $r->id_batch,
                    'nama' => $r->nama,
                    'email' => $r->email,
                    'hadir' => $hitung('Hadir'),
                    'izin' => $hitung('Izin'),
                    'alpa' => $hitung('Alpa'),
                    'persen' => $total > 0 ? (int) round($hadirSelesai / $total * 100) : 0,
                    'status' => $r->status,
                ];
            })->values();

            return (object) [
                'id_batch' => $pertama->id_batch,
                'skema' => config("hrgz.skema.{$pertama->nama_skema}", $pertama->nama_skema),
                'kode' => $pertama->nama_skema,
                'mode' => $pertama->mode,
                'periode' => $pertama->periode,
                'jumlah' => $peserta->count(),
                'sesi_selesai' => $total,
                'rata_hadir' => $total > 0 ? (int) round($peserta->avg('persen')) : 0,
                'selesai' => $peserta->where('status', 'Completed')->count(),
                'peserta' => $peserta,
            ];
        })->values();
    }
}
