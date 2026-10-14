<?php

namespace Database\Seeders;

use App\Modules\Katalog\Models\Batch;
use App\Modules\Katalog\Models\Skema;
use App\Modules\Katalog\Models\Trainer;
use App\Modules\Penjadwalan\Models\Sesi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Skenario offline (periode 2026-11, Senin 2 dan Selasa 3 November 2026).
 * Peserta yang sama didaftarkan ke batch Offline skemanya.
 * Jam 08.30-15.30 adalah usulan (PRD OP 04).
 */
class MasterScheduleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $batch = $this->buatBatchOffline();
            $trainer = Trainer::orderBy('id_trainer')->pluck('id_trainer')->values();
            $semua = ['BP', 'JNA', 'JWP', 'NA', 'TMJK', 'TUJK'];

            // tanggal, mulai, selesai, durasi hari, nama, trainer, room, skema, catatan
            $jadwal = [
                ['2026-11-02', '08:30', '12:00', 0.5, 'Basic Programming (Offline)', 0, 'Kelas Lab 1', $semua, 'Pagi; kapasitas kelas harus memuat seluruh peserta.'],
                ['2026-11-02', '13:00', '15:30', 0.5, 'Web Development Lanjutan (Offline)', 0, 'Kelas Lab 1', ['JWP'], 'Siang; setelah BP; jalur exclusive JWP.'],
                ['2026-11-02', '13:00', '15:30', 0.5, 'Jaringan Dasar (gabungan, Offline)', 1, 'Kelas Lab 2', ['JNA', 'NA'], 'Siang; gabungan JNA + NA; setelah BP.'],
                ['2026-11-02', '13:00', '15:30', 0.5, 'Teknisi Jaringan Dasar (gabungan, Offline)', 2, 'Kelas Lab 3', ['TMJK', 'TUJK'], 'Siang; gabungan TMJK + TUJK; setelah BP.'],
                ['2026-11-03', '08:30', '15:30', 1, 'Network Administrator Lanjutan (Offline)', 1, 'Kelas Lab 2', ['NA'], 'Hari penuh; setelah Jaringan Dasar (gabungan).'],
                ['2026-11-03', '08:30', '15:30', 1, 'Teknisi Utama Lanjutan (Offline)', 2, 'Kelas Lab 3', ['TUJK'], 'Hari penuh; setelah Teknisi Jaringan Dasar (gabungan).'],
            ];

            foreach ($jadwal as [$tanggal, $mulai, $selesai, $durasi, $nama, $idxTrainer, $room, $skema, $catatan]) {
                $sesi = Sesi::create([
                    'id_trainer' => $trainer[$idxTrainer], 'nama_sesi' => $nama, 'tanggal' => $tanggal,
                    'jam_mulai' => $mulai.':00', 'jam_selesai' => $selesai.':00', 'durasi_hari' => $durasi,
                    'room' => $room, 'tipe' => count($skema) > 1 ? 'Common' : 'Exclusive',
                    'status' => 'Planned', 'catatan' => $catatan,
                ]);
                $sesi->batches()->attach(array_map(fn ($k) => $batch[$k]->id_batch, $skema));
            }
        });
    }

    /**
     * @return array<string, Batch>
     */
    private function buatBatchOffline(): array
    {
        $batch = [];

        foreach (Skema::orderBy('id_skema')->get() as $skema) {
            $batch[$skema->nama_skema] = Batch::create([
                'id_skema' => $skema->id_skema, 'periode' => '2026-11', 'mode' => 'Offline', 'status' => 'Planned',
            ]);

            $oktober = Batch::where('id_skema', $skema->id_skema)->where('periode', '2026-10')->first();
            $peserta = $oktober
                ? DB::table('tbl_daftar')->where('id_batch', $oktober->id_batch)->pluck('id_peserta')
                : collect();

            DB::table('tbl_daftar')->insert($peserta->map(fn ($id) => [
                'id_batch' => $batch[$skema->nama_skema]->id_batch, 'id_peserta' => $id, 'status' => 'Validated',
            ])->all());
        }

        return $batch;
    }
}
