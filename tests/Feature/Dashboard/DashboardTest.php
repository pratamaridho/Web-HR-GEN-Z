<?php

namespace Tests\Feature\Dashboard;

use App\Modules\Akun\Models\User;
use App\Modules\Dashboard\Queries\RingkasanOperasional;
use App\Modules\Katalog\Models\Batch;
use App\Modules\Katalog\Models\Skema;
use App\Modules\Penjadwalan\Models\Sesi;
use App\Modules\Peserta\Models\Peserta;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * FR 14 (dashboard operasional, sembilan indikator), AC 14, UAT 14, UAT 15.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        Carbon::setTestNow('2026-10-21 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function ringkasan(): array
    {
        return app(RingkasanOperasional::class)->hitung();
    }

    // --- Akses ------------------------------------------------------------

    public function test_admin_dan_management_membuka_dashboard_trainer_ditolak(): void
    {
        $this->actingAs($this->user('admin@example.test'))->get('/dashboard')->assertOk();
        $this->actingAs($this->user('management@example.test'))->get('/dashboard')->assertOk();
        $this->actingAs($this->user('trainer@example.test'))->get('/dashboard')->assertForbidden();
    }

    public function test_management_hanya_membaca_tanpa_tombol_aksi(): void
    {
        $this->actingAs($this->user('management@example.test'))->get('/dashboard')
            ->assertDontSee(route('form-sesi'), false)
            ->assertDontSee(route('validasi-durasi'), false)
            ->assertDontSee(route('jadwal-sesi'), false);

        $this->actingAs($this->user('admin@example.test'))->get('/dashboard')
            ->assertSee(route('validasi-durasi'), false);
    }

    // --- Sembilan indikator ----------------------------------------------

    public function test_sembilan_indikator_tampil_pada_satu_halaman(): void
    {
        $this->actingAs($this->user('admin@example.test'))->get('/dashboard')
            ->assertSeeInOrder([
                'Periode aktif', 'Batch aktif', 'Peserta', 'Sesi', 'Konflik', 'Sesi mendatang',
                'Alokasi trainer', 'Progres skema', 'Kehadiran',
            ]);
    }

    public function test_nilai_indikator_pada_data_seed(): void
    {
        $r = $this->ringkasan();

        $this->assertSame('2026-10', $r['periode']);
        $this->assertSame(6, $r['batch_aktif']);
        $this->assertSame(84, $r['peserta']);
        $this->assertSame(10, $r['sesi']);
        $this->assertSame(0, $r['konflik']);
        $this->assertSame(0, $r['kehadiran']['total']);
        $this->assertSame(
            ['Trainer 1' => [4, 4], 'Trainer 2' => [3, 3], 'Trainer 3' => [3, 3]],
            $r['alokasi']->mapWithKeys(fn ($a) => [$a['trainer'] => [$a['sesi'], $a['hari']]])->all()
        );
        $this->assertTrue($r['progress']->every(fn ($p) => $p['persen_terjadwal'] === 100));
        $this->assertTrue($r['progress']->every(fn ($p) => $p['persen_selesai'] === 0));
    }

    public function test_sesi_mendatang_mulai_hari_ini_terdekat_lebih_dulu(): void
    {
        $nama = $this->ringkasan()['mendatang']->pluck('nama_sesi')->all();

        $this->assertSame('Basic Programming (Sesi 3)', $nama[0]); // Rabu 21 Okt
        $this->assertNotContains('Basic Programming (Sesi 1)', $nama);
        $this->assertNotContains('Basic Programming (Sesi 2)', $nama);
    }

    public function test_progress_menghitung_terjadwal_dan_selesai_terhadap_durasi_wajib(): void
    {
        Sesi::where('nama_sesi', 'Teknisi Utama Lanjutan (Sesi 1)')->update(['status' => 'Cancelled']);
        Sesi::where('nama_sesi', 'like', 'Basic Programming%')->update(['status' => 'Completed']);

        $p = $this->ringkasan()['progress']->keyBy('skema');

        $this->assertSame(83, $p['TUJK']['persen_terjadwal']); // 5 dari 6 hari
        $this->assertSame(100, $p['BP']['persen_selesai']);    // 3 dari 3 hari
        $this->assertSame(50, $p['NA']['persen_selesai']);     // BP 3 dari 6 hari
    }

    public function test_indikator_konflik_menghitung_sesi_bermasalah_dari_data(): void
    {
        $sesi = Sesi::where('nama_sesi', 'Web Development Lanjutan')->firstOrFail();
        $offline = Batch::create([
            'id_skema' => Skema::where('nama_skema', 'JWP')->value('id_skema'),
            'periode' => '2026-10', 'mode' => 'Offline', 'status' => 'Planned',
        ]);
        // Dipaksa lewat tabel penghubung: validasi aplikasi akan menolaknya.
        DB::table('tbl_sesi_batch')->insert(['id_batch' => $offline->id_batch, 'id_sesi' => $sesi->id_sesi]);

        $this->assertSame(1, $this->ringkasan()['konflik']);
    }

    public function test_ringkasan_kehadiran_hanya_sesi_yang_sudah_berjalan(): void
    {
        Carbon::setTestNow('2026-10-20 10:00:00');
        $lalu = Sesi::where('nama_sesi', 'Basic Programming (Sesi 1)')->firstOrFail();   // 19 Okt
        $depan = Sesi::where('nama_sesi', 'Basic Programming (Sesi 3)')->firstOrFail();  // 21 Okt
        $ids = Peserta::orderBy('id_peserta')->limit(5)->pluck('id_peserta');

        foreach ($ids as $i => $id) {
            DB::table('tbl_kehadiran')->insert([
                'id_peserta' => $id, 'id_sesi' => $lalu->id_sesi,
                'status_hadir' => ['Hadir', 'Hadir', 'Hadir', 'Izin', 'Alpa'][$i],
            ]);
        }
        DB::table('tbl_kehadiran')->insert(['id_peserta' => $ids[0], 'id_sesi' => $depan->id_sesi, 'status_hadir' => 'Alpa']);

        $k = $this->ringkasan()['kehadiran'];

        $this->assertSame(5, $k['total']);
        $this->assertSame([60, 20, 20], [$k['persen_hadir'], $k['persen_izin'], $k['persen_alpa']]);
    }

    // --- UAT 15 -----------------------------------------------------------

    public function test_menambah_sesi_valid_mengubah_jumlah_sesi_dan_alokasi_trainer(): void
    {
        $trainer = $this->user('trainer@example.test')->trainer;
        $batch = Batch::where('id_skema', Skema::where('nama_skema', 'JWP')->value('id_skema'))->value('id_batch');

        $this->actingAs($this->user('admin@example.test'))->post('/jadwal-sesi', [
            'nama_sesi' => 'Sesi Tambahan', 'tanggal' => '2026-10-26', 'durasi_hari' => '1',
            'jam_mulai' => '19:00', 'jam_selesai' => '21:30', 'id_trainer' => $trainer->id_trainer,
            'room' => 'Zoom Room A', 'status' => 'Planned', 'id_batch' => [$batch],
        ])->assertSessionHas('sukses');

        $r = $this->ringkasan();
        $t1 = $r['alokasi']->firstWhere('trainer', 'Trainer 1');

        $this->assertSame(11, $r['sesi']);
        $this->assertSame([5, 5], [$t1['sesi'], $t1['hari']]);
    }

    public function test_dashboard_tanpa_batch_menampilkan_nilai_kosong_tanpa_error(): void
    {
        DB::table('tbl_kehadiran')->delete();
        DB::table('tbl_sesi_batch')->delete();
        DB::table('tbl_sesi')->delete();
        DB::table('tbl_daftar')->delete();
        DB::table('tbl_batch')->delete();

        $r = $this->ringkasan();

        $this->assertNull($r['periode']);
        $this->assertSame([0, 0, 0, 0], [$r['batch_aktif'], $r['peserta'], $r['sesi'], $r['konflik']]);
        $this->actingAs($this->user('admin@example.test'))->get('/dashboard')->assertOk();
    }
}
