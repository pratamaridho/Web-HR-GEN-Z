<?php

namespace Tests\Feature\Penjadwalan;

use App\Modules\Akun\Models\User;
use App\Modules\Katalog\Models\Batch;
use App\Modules\Katalog\Models\Trainer;
use App\Modules\Penjadwalan\Models\Sesi;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Sesi Cancelled tidak lagi menghalangi trainer dan room.
 * Sesi acuan: sesi BP pertama (Trainer 1, Zoom Room A, 19.00-21.30).
 */
class BentrokSesiBatalTest extends TestCase
{
    use RefreshDatabase;

    private Sesi $acuan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->acuan = Sesi::where('jam_mulai', '19:00:00')->where('room', 'Zoom Room A')->orderBy('tanggal')->firstOrFail();
        $this->acuan->update(['status' => 'Cancelled']);
    }

    private function simpan(array $ubah): TestResponse
    {
        return $this->actingAs(User::where('email', 'admin@example.test')->firstOrFail())
            ->post('/jadwal-sesi', array_merge([
                'nama_sesi' => 'Sesi pengganti',
                'tanggal' => $this->acuan->tanggal->toDateString(),
                'jam_mulai' => '20:00',
                'jam_selesai' => '22:00',
                'id_trainer' => $this->acuan->id_trainer,
                'room' => $this->acuan->room,
                'id_batch' => [Batch::where('mode', 'Online')->first()->id_batch],
            ], $ubah));
    }

    public function test_jam_beririsan_dengan_sesi_batal_diperbolehkan(): void
    {
        $this->simpan([])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tbl_sesi', ['nama_sesi' => 'Sesi pengganti', 'room' => 'Zoom Room A']);
    }

    public function test_sesi_yang_tidak_batal_tetap_menghalangi(): void
    {
        $this->acuan->update(['status' => 'Planned']);

        $this->simpan([])->assertSessionHasErrors('id_trainer');
    }

    public function test_jam_mulai_persis_sama_dengan_sesi_batal_ditolak_dengan_pesan_yang_menyebutnya(): void
    {
        $this->simpan(['jam_mulai' => '19:00', 'jam_selesai' => '21:30'])->assertSessionHasErrors('id_trainer');

        $pesan = session('errors')->first('id_trainer');
        $this->assertStringContainsString('dibatalkan', $pesan);
        $this->assertStringContainsString($this->acuan->nama_sesi, $pesan);
        $this->assertDatabaseMissing('tbl_sesi', ['nama_sesi' => 'Sesi pengganti']);
    }

    public function test_sesi_batal_tidak_membuat_sesi_lain_ditandai_parallel(): void
    {
        $kembar = Sesi::create([
            'id_trainer' => Trainer::where('id_trainer', '!=', $this->acuan->id_trainer)->first()->id_trainer,
            'nama_sesi' => 'Sesi kembar', 'tanggal' => $this->acuan->tanggal->toDateString(),
            'jam_mulai' => '19:00:00', 'jam_selesai' => '21:30:00', 'durasi_hari' => 1,
            'room' => 'Zoom Room C', 'tipe' => 'Exclusive', 'status' => 'Planned',
        ]);
        $kembar->batches()->attach($this->acuan->batches->first()->id_batch);
        $kunci = $this->acuan->tanggal->toDateString().'|19:00:00';

        $this->actingAs(User::where('email', 'admin@example.test')->firstOrFail())
            ->get('/jadwal-sesi?tampil=daftar')
            ->assertOk()
            ->assertViewHas('paralel', fn ($paralel) => ! isset($paralel[$kunci]));
    }
}
