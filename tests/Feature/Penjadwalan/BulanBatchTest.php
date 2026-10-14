<?php

namespace Tests\Feature\Penjadwalan;

use App\Modules\Akun\Models\User;
use App\Modules\Katalog\Models\Batch;
use App\Modules\Katalog\Models\Trainer;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class BulanBatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function simpan(Batch $batch, string $tanggal): TestResponse
    {
        return $this->actingAs(User::where('email', 'admin@example.test')->firstOrFail())
            ->post('/jadwal-sesi', [
                'nama_sesi' => 'Sesi uji bulan',
                'tanggal' => $tanggal,
                'jam_mulai' => '19:00',
                'jam_selesai' => '21:30',
                'id_trainer' => Trainer::first()->id_trainer,
                'room' => 'Zoom Room C',
                'id_batch' => [$batch->id_batch],
            ]);
    }

    public function test_tanggal_di_luar_bulan_batch_ditolak_dengan_alasan_jelas(): void
    {
        $oktober = Batch::where('periode', '2026-10')->where('mode', 'Online')->firstOrFail();
        $november = Batch::create(['id_skema' => $oktober->id_skema, 'periode' => '2026-11', 'mode' => 'Online', 'status' => 'Planned']);

        $this->simpan($november, '2026-10-13')->assertSessionHasErrors('tanggal');

        $pesan = session('errors')->first('tanggal');
        $this->assertMatchesRegularExpression('/Batch \d+ · November 2026 · Online/u', $pesan);
        $this->assertStringContainsString('13 Oktober 2026', $pesan);
        $this->assertStringContainsString('bulan November 2026', $pesan);
    }

    public function test_tanggal_di_dalam_bulan_batch_diterima(): void
    {
        $oktober = Batch::where('periode', '2026-10')->where('mode', 'Online')->firstOrFail();

        $this->simpan($oktober, '2026-10-27')->assertSessionHasNoErrors();
    }

    public function test_salah_satu_batch_di_luar_bulan_cukup_untuk_menolak(): void
    {
        $oktober = Batch::where('periode', '2026-10')->where('mode', 'Online')->firstOrFail();
        $november = Batch::create(['id_skema' => $oktober->id_skema, 'periode' => '2026-11', 'mode' => 'Online', 'status' => 'Planned']);

        $this->actingAs(User::where('email', 'admin@example.test')->firstOrFail())
            ->post('/jadwal-sesi', [
                'nama_sesi' => 'Sesi campur bulan', 'tanggal' => '2026-10-27',
                'jam_mulai' => '19:00', 'jam_selesai' => '21:30',
                'id_trainer' => Trainer::first()->id_trainer, 'room' => 'Zoom Room C',
                'id_batch' => [$oktober->id_batch, $november->id_batch],
            ])->assertSessionHasErrors('tanggal');
    }
}
