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
 * Bentrok berarti jam beririsan, bukan hanya jam mulai yang sama.
 * Sesi acuan: sesi BP pertama (Trainer 1, Zoom Room A, 19.00-21.30).
 */
class BentrokIrisanTest extends TestCase
{
    use RefreshDatabase;

    private Sesi $acuan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->acuan = Sesi::where('jam_mulai', '19:00:00')->where('room', 'Zoom Room A')->orderBy('tanggal')->firstOrFail();
    }

    private function simpan(array $ubah): TestResponse
    {
        $tanggal = $this->acuan->tanggal->toDateString();

        return $this->actingAs(User::where('email', 'admin@example.test')->firstOrFail())
            ->post('/jadwal-sesi', array_merge([
                'nama_sesi' => 'Sesi uji irisan',
                'tanggal' => $tanggal,
                'jam_mulai' => '22:00',
                'jam_selesai' => '23:00',
                'id_trainer' => Trainer::where('id_trainer', '!=', $this->acuan->id_trainer)->first()->id_trainer,
                'room' => 'Zoom Room C',
                'id_batch' => [Batch::where('mode', 'Online')->first()->id_batch],
            ], $ubah));
    }

    public function test_trainer_yang_sama_dengan_jam_beririsan_sebagian_ditolak(): void
    {
        $this->simpan(['id_trainer' => $this->acuan->id_trainer, 'jam_mulai' => '21:29', 'jam_selesai' => '22:30'])
            ->assertSessionHasErrors('id_trainer');

        $this->assertStringContainsString('Trainer bentrok', session('errors')->first('id_trainer'));
    }

    public function test_room_yang_sama_dengan_jam_beririsan_sebagian_ditolak(): void
    {
        $this->simpan(['room' => $this->acuan->room, 'jam_mulai' => '18:00', 'jam_selesai' => '19:01'])
            ->assertSessionHasErrors('room');

        $this->assertStringContainsString('Room bentrok', session('errors')->first('room'));
    }

    public function test_sesi_yang_menelan_sesi_lain_ditolak(): void
    {
        $this->simpan(['room' => $this->acuan->room, 'jam_mulai' => '17:00', 'jam_selesai' => '23:00'])
            ->assertSessionHasErrors('room');
    }

    public function test_sesi_yang_berurutan_persis_diperbolehkan(): void
    {
        $this->simpan([
            'id_trainer' => $this->acuan->id_trainer, 'room' => $this->acuan->room,
            'jam_mulai' => '21:30', 'jam_selesai' => '23:00',
        ])->assertSessionHasNoErrors();
    }

    public function test_pesan_menyebut_sesi_yang_bertabrakan_beserta_jamnya(): void
    {
        $this->simpan(['room' => $this->acuan->room, 'jam_mulai' => '20:00', 'jam_selesai' => '22:00']);

        $pesan = session('errors')->first('room');
        $this->assertStringContainsString($this->acuan->nama_sesi, $pesan);
        $this->assertStringContainsString('19.00–21.30', $pesan);
    }

    public function test_mengubah_sesi_sendiri_tidak_dianggap_bentrok_dengan_dirinya(): void
    {
        $this->actingAs(User::where('email', 'admin@example.test')->firstOrFail())
            ->put("/jadwal-sesi/{$this->acuan->id_sesi}", [
                'nama_sesi' => $this->acuan->nama_sesi,
                'tanggal' => $this->acuan->tanggal->toDateString(),
                'jam_mulai' => '19:00',
                'jam_selesai' => '21:00',
                'id_trainer' => $this->acuan->id_trainer,
                'room' => $this->acuan->room,
                'id_batch' => $this->acuan->batches()->pluck('tbl_batch.id_batch')->all(),
            ])->assertSessionHasNoErrors();
    }
}
