<?php

namespace Tests\Feature\Alur;

use App\Modules\Akun\Models\User;
use App\Modules\Katalog\Models\Batch;
use App\Modules\Katalog\Models\Trainer;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Mengikuti alur demo lima menit dengan data contoh lengkap.
 */
class NaskahDemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function simpanSesi(int $idTrainer, string $room, string $tanggal): TestResponse
    {
        $batch = Batch::with('skema')->where('periode', '2026-10')->where('mode', 'Online')->get()
            ->filter(fn ($b) => in_array($b->skema->nama_skema, ['JNA', 'NA'], true))
            ->pluck('id_batch')->all();

        return $this->actingAs(User::where('email', 'admin@example.test')->firstOrFail())
            ->post('/jadwal-sesi', [
                'nama_sesi' => 'Sesi demo',
                'tanggal' => $tanggal,
                'jam_mulai' => '19:00',
                'jam_selesai' => '21:30',
                'id_trainer' => $idTrainer,
                'room' => $room,
                'id_batch' => $batch,
            ]);
    }

    public function test_trainer_2_pada_23_oktober_jam_19_ditolak_karena_trainer_bentrok(): void
    {
        $trainer2 = Trainer::orderBy('id_trainer')->skip(1)->first();

        $this->simpanSesi($trainer2->id_trainer, 'Zoom Room A', '2026-10-23')->assertSessionHasErrors('id_trainer');

        $this->assertStringContainsString('Trainer bentrok', session('errors')->first('id_trainer'));
    }

    public function test_trainer_1_di_zoom_room_b_pada_23_oktober_ditolak_karena_room_bentrok(): void
    {
        $trainer1 = Trainer::orderBy('id_trainer')->first();

        $this->simpanSesi($trainer1->id_trainer, 'Zoom Room B', '2026-10-23')->assertSessionHasErrors('room');

        $this->assertStringContainsString('Room bentrok', session('errors')->first('room'));
    }

    public function test_tanggal_26_oktober_tersimpan_dan_tampil_pada_kalender(): void
    {
        $trainer1 = Trainer::orderBy('id_trainer')->first();

        $this->simpanSesi($trainer1->id_trainer, 'Zoom Room A', '2026-10-26')->assertSessionHasNoErrors();

        $this->actingAs(User::where('email', 'admin@example.test')->firstOrFail())
            ->get('/jadwal-sesi?bulan=2026-10')
            ->assertOk()
            ->assertSee('Sesi demo');
    }

    public function test_enam_batch_baru_dibuat_sekali_klik_dari_batch_management(): void
    {
        $sebelum = Batch::count();

        $this->actingAs(User::where('email', 'admin@example.test')->firstOrFail())
            ->post('/data-master/batch-bulanan', ['periode' => '2026-12', 'mode' => 'Online'])
            ->assertSessionHas('sukses');

        $this->assertSame($sebelum + 6, Batch::count());
    }
}
