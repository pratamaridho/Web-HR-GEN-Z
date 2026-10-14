<?php

namespace Database\Seeders;

use App\Modules\Katalog\Models\Batch;
use App\Modules\Katalog\Models\Skema;
use App\Modules\Katalog\Models\Trainer;
use App\Modules\Penjadwalan\Models\Sesi;
use App\Modules\Peserta\Models\Daftar;
use App\Modules\Peserta\Models\Peserta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Data contoh siklus Oktober 2026 (online): 6 batch, 84 peserta, 10 sesi.
 * Butuh DataAwalSeeder lebih dulu (skema dan trainer).
 */
class DataContohSeeder extends Seeder
{
    private const SEMUA = ['BP', 'JNA', 'JWP', 'NA', 'TMJK', 'TUJK'];

    public function run(): void
    {
        DB::transaction(function () {
            $batch = $this->buatBatchDanPeserta();
            $trainer = Trainer::orderBy('id_trainer')->pluck('id_trainer')->values();

            // tanggal, nama sesi, trainer (0-2), room, skema yang ikut, catatan
            $jadwal = [
                ['2026-10-19', 'Basic Programming (Sesi 1)', 0, 'Zoom Room A', self::SEMUA, 'Fondasi; prasyarat semua skema (JWP wajib).'],
                ['2026-10-20', 'Basic Programming (Sesi 2)', 0, 'Zoom Room A', self::SEMUA, 'Lanjutan BP; materi tidak diulang di skema lain.'],
                ['2026-10-21', 'Basic Programming (Sesi 3)', 0, 'Zoom Room A', self::SEMUA, 'BP selesai; kelas dipecah menjadi parallel track.'],
                ['2026-10-22', 'Web Development Lanjutan', 0, 'Zoom Room A', ['JWP'], 'Setelah BP; jalur exclusive JWP.'],
                ['2026-10-22', 'Jaringan Dasar (gabungan)', 1, 'Zoom Room B', ['JNA', 'NA'], 'Gabungan JNA + NA; setelah BP.'],
                ['2026-10-22', 'Teknisi Jaringan Dasar (gabungan)', 2, 'Zoom Room C', ['TMJK', 'TUJK'], 'Gabungan TMJK + TUJK; setelah BP.'],
                ['2026-10-23', 'Network Administrator Lanjutan (Sesi 1)', 1, 'Zoom Room B', ['NA'], 'Setelah Jaringan Dasar (gabungan).'],
                ['2026-10-23', 'Teknisi Utama Lanjutan (Sesi 1)', 2, 'Zoom Room C', ['TUJK'], 'Setelah Teknisi Jaringan Dasar (gabungan).'],
                ['2026-10-24', 'Network Administrator Lanjutan (Sesi 2)', 1, 'Zoom Room B', ['NA'], 'Lanjutan sesi 1; jalur terpanjang NA.'],
                ['2026-10-24', 'Teknisi Utama Lanjutan (Sesi 2)', 2, 'Zoom Room C', ['TUJK'], 'Lanjutan sesi 1; jalur terpanjang TUJK.'],
            ];

            foreach ($jadwal as [$tanggal, $nama, $idxTrainer, $room, $skema, $catatan]) {
                $sesi = Sesi::create([
                    'id_trainer' => $trainer[$idxTrainer],
                    'nama_sesi' => $nama,
                    'tanggal' => $tanggal,
                    'jam_mulai' => '19:00:00',
                    'jam_selesai' => '21:30:00',
                    'durasi_hari' => 1,
                    'room' => $room,
                    'tipe' => count($skema) > 1 ? 'Common' : 'Exclusive',
                    'status' => 'Planned',
                    'catatan' => $catatan,
                ]);
                $sesi->batches()->attach(array_map(fn ($k) => $batch[$k]->id_batch, $skema));
            }
        });
    }

    /**
     * Satu batch per skema, 14 peserta per batch (peserta001 sampai peserta084).
     *
     * @return array<string, Batch>
     */
    private function buatBatchDanPeserta(): array
    {
        $batch = [];
        $nomor = 1;

        foreach (Skema::orderBy('id_skema')->get() as $skema) {
            $batch[$skema->nama_skema] = $b = Batch::create([
                'id_skema' => $skema->id_skema, 'periode' => '2026-10', 'mode' => 'Online', 'status' => 'Planned',
            ]);

            for ($i = 0; $i < 14; $i++, $nomor++) {
                $peserta = Peserta::create([
                    'nama' => sprintf('Peserta %03d', $nomor),
                    'email' => sprintf('peserta%03d@example.test', $nomor),
                ]);
                Daftar::create(['id_batch' => $b->id_batch, 'id_peserta' => $peserta->id_peserta, 'status' => 'Validated']);
            }
        }

        return $batch;
    }
}
