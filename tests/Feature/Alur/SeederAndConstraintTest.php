<?php

namespace Tests\Feature\Alur;

use App\Modules\Katalog\Models\Batch;
use App\Modules\Katalog\Models\Skema;
use App\Modules\Katalog\Models\Trainer;
use App\Modules\Penjadwalan\Models\Sesi;
use App\Modules\Peserta\Models\Peserta;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederAndConstraintTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Memverifikasi jumlah data hasil seeder sesuai spesifikasi.
     */
    public function test_seeder_creates_expected_data_counts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('tbl_skema', 6);
        $this->assertDatabaseCount('tbl_trainer', 3);
        $this->assertDatabaseCount('tbl_peserta', 84);
        $this->assertDatabaseCount('tbl_batch', 12);
        $this->assertDatabaseCount('tbl_daftar', 168);
        $this->assertDatabaseCount('tbl_sesi', 16);
    }

    /**
     * Memverifikasi constraint UNIQUE pada tbl_peserta menolak email ganda.
     */
    public function test_unique_constraint_rejects_duplicate_peserta_email(): void
    {
        Peserta::create([
            'nama' => 'Peserta A',
            'email' => 'peserta.unik@example.test',
        ]);

        $this->expectException(QueryException::class);

        Peserta::create([
            'nama' => 'Peserta B',
            'email' => 'peserta.unik@example.test',
        ]);
    }

    /**
     * Memverifikasi constraint UNIQUE uq_batch_skema_periode_mode menolak batch ganda.
     */
    public function test_unique_constraint_rejects_duplicate_batch_for_same_skema_periode_and_mode(): void
    {
        $skema = Skema::create([
            'nama_skema' => 'BP',
            'durasi_online' => 3.0,
            'durasi_offline' => 0.5,
        ]);

        Batch::create([
            'id_skema' => $skema->id_skema,
            'periode' => '2026-10',
            'mode' => 'Online',
            'status' => 'Planned',
        ]);

        $this->expectException(QueryException::class);

        // Percobaan batch kedua dengan skema, periode, dan mode yang sama harus gagal
        Batch::create([
            'id_skema' => $skema->id_skema,
            'periode' => '2026-10',
            'mode' => 'Online',
            'status' => 'Planned',
        ]);
    }

    /**
     * Memverifikasi constraint UNIQUE uq_sesi_jadwal_trainer menolak trainer ganda di tanggal dan jam mulai yang sama.
     */
    public function test_unique_constraint_rejects_duplicate_trainer_on_same_date_and_start_time(): void
    {
        $trainer = Trainer::create([
            'nama' => 'Trainer 1',
            'kompetensi' => 'BP, JWP',
        ]);

        Sesi::create([
            'id_trainer' => $trainer->id_trainer,
            'nama_sesi' => 'Sesi 1 di Zoom Room A',
            'tanggal' => '2026-10-19',
            'jam_mulai' => '19:00:00',
            'jam_selesai' => '21:30:00',
            'durasi_hari' => 1.0,
            'room' => 'Zoom Room A',
            'tipe' => 'Common',
            'status' => 'Planned',
        ]);

        $this->expectException(QueryException::class);

        // Percobaan membuat sesi kedua untuk trainer yang sama di tanggal & jam_mulai yang sama (meski room berbeda)
        Sesi::create([
            'id_trainer' => $trainer->id_trainer,
            'nama_sesi' => 'Sesi 2 di Zoom Room B',
            'tanggal' => '2026-10-19',
            'jam_mulai' => '19:00:00',
            'jam_selesai' => '21:30:00',
            'durasi_hari' => 1.0,
            'room' => 'Zoom Room B',
            'tipe' => 'Exclusive',
            'status' => 'Planned',
        ]);
    }

    /**
     * Memverifikasi constraint UNIQUE uq_sesi_jadwal_room menolak room ganda di tanggal dan jam mulai yang sama.
     */
    public function test_unique_constraint_rejects_duplicate_room_on_same_date_and_start_time(): void
    {
        $trainer1 = Trainer::create(['nama' => 'Trainer 1', 'kompetensi' => 'BP']);
        $trainer2 = Trainer::create(['nama' => 'Trainer 2', 'kompetensi' => 'JNA']);

        Sesi::create([
            'id_trainer' => $trainer1->id_trainer,
            'nama_sesi' => 'Sesi Trainer 1',
            'tanggal' => '2026-10-19',
            'jam_mulai' => '19:00:00',
            'jam_selesai' => '21:30:00',
            'durasi_hari' => 1.0,
            'room' => 'Zoom Room A',
            'tipe' => 'Exclusive',
            'status' => 'Planned',
        ]);

        $this->expectException(QueryException::class);

        // Percobaan membuat sesi di room yang sama (Zoom Room A) pada tanggal & jam_mulai yang sama oleh trainer berbeda
        Sesi::create([
            'id_trainer' => $trainer2->id_trainer,
            'nama_sesi' => 'Sesi Trainer 2',
            'tanggal' => '2026-10-19',
            'jam_mulai' => '19:00:00',
            'jam_selesai' => '21:30:00',
            'durasi_hari' => 1.0,
            'room' => 'Zoom Room A',
            'tipe' => 'Exclusive',
            'status' => 'Planned',
        ]);
    }
}
