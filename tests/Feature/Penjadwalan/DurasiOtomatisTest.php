<?php

namespace Tests\Feature\Penjadwalan;

use App\Modules\Akun\Models\User;
use App\Modules\Katalog\Models\Batch;
use App\Modules\Katalog\Models\Trainer;
use App\Modules\Penjadwalan\Models\Sesi;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DurasiOtomatisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function simpan(string $mode, string $mulai, string $selesai, array $tambahan = []): void
    {
        $batch = Batch::where('mode', $mode)->first();

        $this->actingAs(User::where('email', 'admin@example.test')->firstOrFail())
            ->post('/jadwal-sesi', array_merge([
                'nama_sesi' => 'Sesi uji durasi',
                'tanggal' => $batch->periode.'-15',
                'jam_mulai' => $mulai,
                'jam_selesai' => $selesai,
                'id_trainer' => Trainer::first()->id_trainer,
                'room' => $mode === 'Online' ? 'Zoom Room A' : 'Kelas Lab 1',
                'id_batch' => [$batch->id_batch],
            ], $tambahan))->assertSessionHasNoErrors();
    }

    public function test_sesi_online_tersimpan_satu_hari_walau_durasi_dikirim_salah(): void
    {
        $this->simpan('Online', '19:00', '21:30', ['durasi_hari' => 3]);

        $this->assertSame(1.0, (float) Sesi::where('nama_sesi', 'Sesi uji durasi')->value('durasi_hari'));
    }

    public function test_sesi_offline_pendek_tersimpan_setengah_hari(): void
    {
        $this->simpan('Offline', '08:30', '12:00');

        $this->assertSame(0.5, (float) Sesi::where('nama_sesi', 'Sesi uji durasi')->value('durasi_hari'));
    }

    public function test_sesi_offline_sehari_penuh_tersimpan_satu_hari(): void
    {
        $this->simpan('Offline', '08:30', '15:30');

        $this->assertSame(1.0, (float) Sesi::where('nama_sesi', 'Sesi uji durasi')->value('durasi_hari'));
    }
}
