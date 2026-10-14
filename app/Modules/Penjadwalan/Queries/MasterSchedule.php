<?php

namespace App\Modules\Penjadwalan\Queries;

use App\Modules\Katalog\Models\Batch;
use App\Modules\Penjadwalan\Models\Sesi;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Master Schedule satu skenario (Online atau Offline) pada satu periode,
 * dengan field minimum brief bagian 5: tanggal/hari, waktu, sesi, skema, kelompok peserta,
 * trainer, room, jenis (Common/Parallel/Exclusive), durasi, dan catatan.
 *
 * Parallel diturunkan: dua sesi atau lebih pada tanggal dan jam mulai sama.
 */
class MasterSchedule
{
    public function __construct(private readonly DurasiSkema $durasi) {}

    /**
     * Daftar periode yang punya batch pada mode ini, dari yang terawal.
     *
     * @return Collection<int, string>
     */
    public function daftarPeriode(string $mode): Collection
    {
        return Batch::where('mode', $mode)->distinct()->orderBy('periode')->pluck('periode');
    }

    /**
     * @param  string|null  $periode  format YYYY-MM; null memakai periode terawal pada mode ini
     * @return array{periode: string|null, baris: list<array<string, mixed>>, ringkasan: array<string, int>, durasi: list<array<string, mixed>>}
     */
    public function untukMode(string $mode, ?string $periode = null): array
    {
        $periode ??= $this->daftarPeriode($mode)->first();

        $sesi = Sesi::with(['trainer', 'batches.skema'])
            ->whereHas('batches', fn ($b) => $b->where('tbl_batch.mode', $mode)->where('tbl_batch.periode', $periode))
            ->orderBy('tanggal')->orderBy('jam_mulai')->orderBy('id_sesi')
            ->get();

        $slot = $sesi->countBy(fn (Sesi $s) => $s->tanggal->toDateString().'|'.$s->jam_mulai);
        $tanggal = $sesi->map(fn (Sesi $s) => $s->tanggal->toDateString())->unique()->values();

        $baris = $sesi->map(function (Sesi $s) use ($slot, $tanggal) {
            $idBatch = $s->batches->pluck('id_batch');
            $jam = (strtotime($s->jam_selesai) - strtotime($s->jam_mulai)) / 3600;

            return [
                'id_sesi' => $s->id_sesi,
                'hari' => $tanggal->search($s->tanggal->toDateString()) + 1,
                'tanggal' => $s->tanggal,
                'waktu' => str_replace(':', '.', substr($s->jam_mulai, 0, 5)).'–'.str_replace(':', '.', substr($s->jam_selesai, 0, 5)),
                'sesi' => $s->nama_sesi,
                'skema' => $s->batches->pluck('skema.nama_skema')->unique()->implode(', '),
                'peserta' => (int) DB::table('tbl_daftar')->whereIn('id_batch', $idBatch)->distinct()->count('id_peserta'),
                'trainer' => $s->trainer->nama,
                'room' => $s->room,
                'jenis' => $s->tipe,
                'paralel' => ($slot[$s->tanggal->toDateString().'|'.$s->jam_mulai] ?? 0) > 1,
                'durasi_jam' => $jam,
                'durasi_hari' => (float) $s->durasi_hari,
                'catatan' => $s->catatan,
                'status' => $s->status,
            ];
        })->values()->all();

        $durasi = $periode === null
            ? []
            : $this->durasi->untukPeriode($periode)->where('mode', $mode)->values()->all();

        $awal = $tanggal->first();
        $akhir = $tanggal->last();

        return [
            'periode' => $periode,
            'baris' => $baris,
            'ringkasan' => [
                'hari_pelaksanaan' => $tanggal->count(),
                'calendar_days' => $awal ? (int) Carbon::parse($awal)->diffInDays(Carbon::parse($akhir)) + 1 : 0,
                'trainer' => $sesi->pluck('id_trainer')->unique()->count(),
                'room' => $sesi->pluck('room')->unique()->count(),
            ],
            'durasi' => $durasi,
        ];
    }
}
