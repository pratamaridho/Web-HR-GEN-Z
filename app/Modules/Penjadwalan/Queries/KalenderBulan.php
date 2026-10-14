<?php

namespace App\Modules\Penjadwalan\Queries;

use App\Modules\Penjadwalan\Models\Sesi;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Menyusun kalender satu bulan (minggu mulai Senin) berisi sesi tiap hari.
 */
class KalenderBulan
{
    /**
     * Bulan yang ditampilkan: dari parameter bila sah, bila tidak bulan sesi
     * mendatang terdekat, lalu bulan sesi terakhir, lalu bulan ini.
     */
    public function bulanAwal(?string $param): string
    {
        if ($param !== null && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $param)) {
            return $param;
        }

        $tanggal = Sesi::whereDate('tanggal', '>=', today())->orderBy('tanggal')->value('tanggal')
            ?? Sesi::max('tanggal');

        return $tanggal ? substr((string) $tanggal, 0, 7) : now()->format('Y-m');
    }

    /**
     * @return array{label: string, sebelumnya: string, berikutnya: string, bulanIni: string, jumlah: int, pesertaPerSesi: array<int, int>, minggu: list<list<array{tanggal: Carbon, dalamBulan: bool, hariIni: bool, sesi: Collection<int, Sesi>}>>}
     */
    public function susun(string $bulan, ?int $skema, ?int $trainer): array
    {
        $awal = Carbon::createFromFormat('!Y-m', $bulan);
        $mulai = $awal->copy()->startOfWeek(Carbon::MONDAY);
        $akhir = $awal->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $sesi = Sesi::with(['trainer', 'batches.skema'])
            ->whereDate('tanggal', '>=', $mulai->toDateString())
            ->whereDate('tanggal', '<=', $akhir->toDateString())
            ->when($skema, fn ($q) => $q->whereHas('batches', fn ($b) => $b->where('tbl_batch.id_skema', $skema)))
            ->when($trainer, fn ($q) => $q->where('id_trainer', $trainer))
            ->orderBy('tanggal')->orderBy('jam_mulai')->orderBy('id_sesi')
            ->get();

        $pesertaPerSesi = $this->hitungPeserta($sesi->pluck('id_sesi'));
        $sesi = $sesi->groupBy(fn (Sesi $s) => $s->tanggal->toDateString());

        $minggu = [];
        for ($hari = $mulai->copy(); $hari <= $akhir; $hari->addDay()) {
            $minggu[intdiv($hari->diffInDays($mulai), 7)][] = [
                'tanggal' => $hari->copy(),
                'dalamBulan' => $hari->month === $awal->month,
                'hariIni' => $hari->isToday(),
                'sesi' => $sesi->get($hari->toDateString(), collect()),
            ];
        }

        return [
            'label' => $awal->translatedFormat('F Y'),
            'sebelumnya' => $awal->copy()->subMonth()->format('Y-m'),
            'berikutnya' => $awal->copy()->addMonth()->format('Y-m'),
            'bulanIni' => now()->format('Y-m'),
            'jumlah' => $sesi->flatten(1)->filter(fn (Sesi $s) => $s->tanggal->format('Y-m') === $bulan)->count(),
            'pesertaPerSesi' => $pesertaPerSesi,
            'minggu' => array_values($minggu),
        ];
    }

    /**
     * Peserta tiap sesi: gabungan peserta semua batch yang dilayani sesi,
     * dihitung sekali jalan agar tidak per sesi.
     *
     * @param  Collection<int, int>  $idSesi
     * @return array<int, int>
     */
    private function hitungPeserta(Collection $idSesi): array
    {
        return DB::table('tbl_sesi_batch as sb')
            ->join('tbl_daftar as d', 'd.id_batch', '=', 'sb.id_batch')
            ->whereIn('sb.id_sesi', $idSesi)
            ->groupBy('sb.id_sesi')
            ->selectRaw('sb.id_sesi, COUNT(DISTINCT d.id_peserta) as jumlah')
            ->pluck('jumlah', 'id_sesi')
            ->map(fn ($n) => (int) $n)
            ->all();
    }
}
