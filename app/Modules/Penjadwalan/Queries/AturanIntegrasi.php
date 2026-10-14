<?php

namespace App\Modules\Penjadwalan\Queries;

use App\Modules\Katalog\Models\Batch;
use Illuminate\Support\Facades\DB;

/**
 * Aturan integrasi materi (brief bagian 4). Aturan "dapat" digabung tidak
 * diperiksa; yang diperiksa adalah aturan yang bersifat wajib atau kesepakatan
 * penggabungan pada jadwal:
 *
 * - jwp-bp: setiap sesi BP juga diikuti batch JWP (peserta JWP wajib BP).
 * - jna-na: JNA dan NA berbagi minimal satu sesi di luar BP (digabung).
 * - tmjk-tujk: TMJK dan TUJK berbagi minimal satu sesi di luar BP (digabung).
 */
class AturanIntegrasi
{
    /**
     * Teks aturan untuk ditampilkan pada Program Master.
     *
     * @var list<string>
     */
    public const DAFTAR = [
        'Basic Programming (BP) dapat menjadi sesi common foundational untuk lima skema lain; durasinya dihitung penuh ke tiap skema yang mengikutinya.',
        'Peserta JWP wajib mengikuti Basic Programming.',
        'Materi relevan JNA dan NA digabung dalam satu kelas.',
        'Materi relevan TMJK dan TUJK digabung dalam satu kelas.',
        'Sesi bersama dengan learning outcome yang sama tidak diulang.',
        'Setelah sesi common selesai, kelas dapat dipecah menjadi parallel track bila resource memungkinkan.',
        'Jadwal dioptimalkan tanpa mengurangi total durasi tiap skema.',
    ];

    /**
     * @return list<array{kode:string, aturan:string, ok:?bool, pesan:string}>
     */
    public function untuk(string $periode, string $mode): array
    {
        $batch = Batch::with('skema')->where('periode', $periode)->where('mode', $mode)->get()
            ->keyBy(fn (Batch $b) => $b->skema->nama_skema);

        $sesi = fn (string $skema) => isset($batch[$skema])
            ? DB::table('tbl_sesi_batch')->where('id_batch', $batch[$skema]->id_batch)->pluck('id_sesi')->map(fn ($i) => (int) $i)->all()
            : null;

        $hasil = [];

        // JWP wajib BP
        $bp = $sesi('BP');
        $jwp = $sesi('JWP');
        if ($bp === null || $jwp === null) {
            $hasil[] = $this->belumBisa('jwp-bp', 'JWP wajib mengikuti BP');
        } else {
            $kurang = count(array_diff($bp, $jwp));
            $ok = $bp !== [] && $kurang === 0;
            $hasil[] = [
                'kode' => 'jwp-bp',
                'aturan' => 'JWP wajib mengikuti BP',
                'ok' => $ok,
                'pesan' => $ok
                    ? 'Semua sesi BP diikuti JWP.'
                    : ($bp === [] ? 'Belum ada sesi BP.' : "{$kurang} sesi BP belum diikuti batch JWP."),
            ];
        }

        foreach ([['jna-na', 'JNA', 'NA'], ['tmjk-tujk', 'TMJK', 'TUJK']] as [$kode, $a, $b]) {
            $sa = $sesi($a);
            $sb = $sesi($b);
            $judul = "{$a} dan {$b} satu kelas";

            if ($sa === null || $sb === null) {
                $hasil[] = $this->belumBisa($kode, $judul);

                continue;
            }

            // Sesi BP dihadiri semua skema, jadi tidak dihitung sebagai penggabungan.
            $bersama = count(array_diff(array_intersect($sa, $sb), $bp ?? []));
            $hasil[] = [
                'kode' => $kode,
                'aturan' => $judul,
                'ok' => $bersama > 0,
                'pesan' => $bersama > 0
                    ? "{$bersama} sesi bersama."
                    : 'Belum ada sesi bersama.',
            ];
        }

        return $hasil;
    }

    /**
     * @return array{kode:string, aturan:string, ok:null, pesan:string}
     */
    private function belumBisa(string $kode, string $aturan): array
    {
        return ['kode' => $kode, 'aturan' => $aturan, 'ok' => null, 'pesan' => 'Batch belum lengkap.'];
    }
}
