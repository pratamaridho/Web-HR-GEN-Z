<?php

namespace Tests\Feature\Penjadwalan;

use App\Modules\Akun\Models\User;
use App\Modules\Penjadwalan\Queries\KalenderBulan;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KalenderSesiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function admin(): static
    {
        return $this->actingAs(User::where('email', 'admin@example.test')->firstOrFail());
    }

    public function test_kalender_adalah_tampilan_bawaan_dan_memuat_sesi_bulannya(): void
    {
        $this->admin()->get('/jadwal-sesi?bulan=2026-10')
            ->assertOk()
            ->assertSee('Oktober 2026')
            ->assertSee('Basic Programming')
            ->assertSee('Kalender')
            ->assertSee('Daftar');
    }

    public function test_navigasi_bulan_menautkan_bulan_sebelum_dan_sesudah(): void
    {
        $this->admin()->get('/jadwal-sesi?bulan=2026-10')
            ->assertSee('bulan=2026-09', false)
            ->assertSee('bulan=2026-11', false);
    }

    public function test_minggu_dimulai_senin_dan_mencakup_hari_bulan_lain(): void
    {
        $kal = app(KalenderBulan::class)->susun('2026-10', null, null);

        // 1 Oktober 2026 adalah Kamis: minggu pertama mulai Senin 28 September.
        $this->assertSame('2026-09-28', $kal['minggu'][0][0]['tanggal']->toDateString());
        $this->assertFalse($kal['minggu'][0][0]['dalamBulan']);
        $this->assertTrue($kal['minggu'][0][3]['dalamBulan']);
        $this->assertCount(7, $kal['minggu'][0]);
    }

    public function test_sesi_masuk_ke_hari_yang_benar(): void
    {
        $kal = app(KalenderBulan::class)->susun('2026-10', null, null);

        $hari = collect($kal['minggu'])->flatten(1)->first(fn ($h) => $h['tanggal']->toDateString() === '2026-10-19');

        $this->assertGreaterThan(0, $hari['sesi']->count());
        // Minggu 25 Oktober tidak ada sesi pada data contoh.
        $minggu = collect($kal['minggu'])->flatten(1)->first(fn ($h) => $h['tanggal']->toDateString() === '2026-10-25');
        $this->assertSame(0, $minggu['sesi']->count());
    }

    public function test_bulan_tanpa_sesi_menampilkan_keterangan(): void
    {
        $this->admin()->get('/jadwal-sesi?bulan=2027-03')
            ->assertOk()
            ->assertSee('Belum ada sesi pada bulan Maret 2027');
    }

    public function test_bulan_awal_mengikuti_sesi_mendatang_terdekat(): void
    {
        Carbon::setTestNow('2026-10-13 09:00:00');

        $this->assertSame('2026-10', app(KalenderBulan::class)->bulanAwal(null));
        $this->assertSame('2026-12', app(KalenderBulan::class)->bulanAwal('2026-12'));
    }

    public function test_parameter_bulan_tidak_sah_diabaikan(): void
    {
        Carbon::setTestNow('2026-10-13 09:00:00');

        $this->assertSame('2026-10', app(KalenderBulan::class)->bulanAwal('bukan-bulan'));
        $this->assertSame('2026-10', app(KalenderBulan::class)->bulanAwal('2026-13'));
    }

    public function test_label_bulan_benar_walau_hari_ini_tanggal_31(): void
    {
        Carbon::setTestNow('2026-10-31 09:00:00');

        $kal = app(KalenderBulan::class)->susun('2026-11', null, null);

        $this->assertSame('November 2026', $kal['label']);
    }

    public function test_tampilan_daftar_tetap_tersedia(): void
    {
        $this->admin()->get('/jadwal-sesi?tampil=daftar')
            ->assertOk()
            ->assertSee('Trainer · Room');
    }

    public function test_jumlah_peserta_tiap_sesi_dihitung_dari_batch_yang_dilayani(): void
    {
        $kalender = app(KalenderBulan::class)->susun('2026-10', null, null);
        $sesi = collect($kalender['minggu'])->flatten(1)->pluck('sesi')->flatten(1)->keyBy('nama_sesi');

        // Basic Programming melayani keenam batch (84 peserta); gabungan JNA + NA melayani dua batch (28)
        $this->assertSame(84, $kalender['pesertaPerSesi'][$sesi['Basic Programming (Sesi 1)']->id_sesi]);
        $this->assertSame(28, $kalender['pesertaPerSesi'][$sesi['Jaringan Dasar (gabungan)']->id_sesi]);
        $this->assertSame(14, $kalender['pesertaPerSesi'][$sesi['Web Development Lanjutan']->id_sesi]);
    }

    public function test_kalender_menyerahkan_jumlah_peserta_ke_tampilan(): void
    {
        $this->admin()->get('/jadwal-sesi?bulan=2026-10')
            ->assertOk()
            ->assertViewHas('kalender', fn (array $k) => in_array(84, $k['pesertaPerSesi'], true) && in_array(28, $k['pesertaPerSesi'], true));
    }
}
