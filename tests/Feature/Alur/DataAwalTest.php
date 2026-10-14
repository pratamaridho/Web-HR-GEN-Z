<?php

namespace Tests\Feature\Alur;

use App\Modules\Akun\Models\User;
use App\Modules\Katalog\Models\Batch;
use App\Modules\Katalog\Models\Skema;
use App\Modules\Katalog\Models\Trainer;
use App\Modules\Penjadwalan\Models\Sesi;
use App\Modules\Peserta\Models\Peserta;
use Database\Seeders\DataAwalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Data awal: hanya akun, master skema, dan trainer. Peserta, batch, sesi,
 * dan kehadiran diisi sendiri oleh pengguna.
 */
class DataAwalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DataAwalSeeder::class);
    }

    public function test_hanya_akun_skema_dan_trainer_yang_terisi(): void
    {
        $this->assertSame(3, User::count());
        $this->assertSame(6, Skema::count());
        $this->assertSame(3, Trainer::count());

        $this->assertSame(0, Peserta::count());
        $this->assertSame(0, Batch::count());
        $this->assertSame(0, Sesi::count());
        $this->assertSame(0, DB::table('tbl_daftar')->count());
        $this->assertSame(0, DB::table('tbl_sesi_batch')->count());
        $this->assertSame(0, DB::table('tbl_kehadiran')->count());
    }

    public function test_durasi_master_skema_sesuai_brief(): void
    {
        $durasi = Skema::orderBy('id_skema')->get()
            ->mapWithKeys(fn ($s) => [$s->nama_skema => [(float) $s->durasi_online, (float) $s->durasi_offline]])->all();

        $this->assertSame([
            'BP' => [3.0, 0.5], 'JNA' => [4.0, 1.0], 'JWP' => [4.0, 1.0],
            'NA' => [6.0, 2.0], 'TMJK' => [4.0, 1.0], 'TUJK' => [6.0, 2.0],
        ], $durasi);
    }

    public function test_akun_trainer_terhubung_ke_trainer_pertama(): void
    {
        $this->assertSame(
            Trainer::orderBy('id_trainer')->value('id_trainer'),
            User::where('email', 'trainer@example.test')->value('trainer_id')
        );
    }

    public function test_seeder_dapat_diulang_tanpa_data_ganda(): void
    {
        $this->seed(DataAwalSeeder::class);

        $this->assertSame(3, User::count());
        $this->assertSame(6, Skema::count());
        $this->assertSame(3, Trainer::count());
    }

    public function test_semua_halaman_admin_terbuka_tanpa_error_pada_data_kosong(): void
    {
        $admin = User::where('email', 'admin@example.test')->firstOrFail();

        foreach (['/dashboard', '/data-master', '/peserta', '/jadwal-sesi', '/form-sesi', '/master-schedule',
            '/master-schedule?mode=Offline', '/validasi-durasi', '/rekap-kehadiran'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        $trainer = User::where('email', 'trainer@example.test')->firstOrFail();
        $this->actingAs($trainer)->get('/jadwal-saya')->assertOk()->assertSee('Belum ada sesi');
    }

    public function test_alur_dari_kosong_batch_peserta_sesi_dan_kehadiran(): void
    {
        $admin = User::where('email', 'admin@example.test')->firstOrFail();
        $trainer = User::where('email', 'trainer@example.test')->firstOrFail();

        $this->actingAs($admin)->post('/data-master/batch', [
            'id_skema' => Skema::where('nama_skema', 'BP')->value('id_skema'), 'periode' => '2026-12', 'mode' => 'Online',
        ])->assertSessionHas('sukses');
        $batch = Batch::firstOrFail();

        $this->actingAs($admin)->post('/peserta/impor', [
            'tempel' => "Ayu Lestari, ayu@example.test\nBudi Hartono, budi@example.test", 'id_batch' => $batch->id_batch,
        ])->assertSessionHas('sukses');

        // Peserta baru menunggu validasi sampai Admin memvalidasinya.
        $this->assertSame(2, DB::table('tbl_daftar')->where('status', 'Registered')->count());
        $this->actingAs($admin)->post('/peserta/validasi', ['id_batch' => $batch->id_batch])->assertSessionHas('sukses');
        $this->assertSame(2, DB::table('tbl_daftar')->where('status', 'Validated')->count());

        $this->actingAs($admin)->post('/jadwal-sesi', [
            'nama_sesi' => 'Basic Programming (Sesi 1)', 'tanggal' => '2026-12-07', 'durasi_hari' => '1',
            'jam_mulai' => '19:00', 'jam_selesai' => '21:30', 'id_trainer' => $trainer->trainer_id,
            'room' => 'Zoom Room A', 'status' => 'Planned', 'id_batch' => [$batch->id_batch],
        ])->assertSessionHas('sukses');
        $sesi = Sesi::firstOrFail();

        $ids = Peserta::pluck('id_peserta');
        $this->actingAs($trainer)->post('/kehadiran/'.$sesi->id_sesi, [
            'kehadiran' => $ids->mapWithKeys(fn ($id) => [$id => 'Hadir'])->all(),
        ])->assertSessionHas('sukses');

        $this->assertSame(2, DB::table('tbl_kehadiran')->count());
        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Desember 2026');
    }
}
