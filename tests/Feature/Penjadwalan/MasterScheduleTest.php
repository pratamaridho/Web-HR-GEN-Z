<?php

namespace Tests\Feature\Penjadwalan;

use App\Modules\Akun\Models\User;
use App\Modules\Dashboard\Queries\RingkasanOperasional;
use App\Modules\Katalog\Models\Batch;
use App\Modules\Katalog\Models\Skema;
use App\Modules\Katalog\Models\Trainer;
use App\Modules\Penjadwalan\Models\Sesi;
use App\Modules\Penjadwalan\Queries\AturanIntegrasi;
use App\Modules\Penjadwalan\Queries\MasterSchedule;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Master Schedule online dan offline, penanda Parallel, kolom Catatan, dan
 * aturan integrasi (brief bagian 4 dan 5; deliverable 1 dan 2).
 */
class MasterScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@example.test')->firstOrFail();
    }

    private function batch(string $skema, string $periode, string $mode): Batch
    {
        return Batch::where('id_skema', Skema::where('nama_skema', $skema)->value('id_skema'))
            ->where('periode', $periode)->where('mode', $mode)->firstOrFail();
    }

    // --- Akses ------------------------------------------------------------

    public function test_hanya_admin_membuka_master_schedule(): void
    {
        $this->actingAs($this->admin())->get('/master-schedule')->assertOk();

        foreach (['trainer@example.test', 'management@example.test'] as $email) {
            $this->actingAs(User::where('email', $email)->first())->get('/master-schedule')->assertForbidden();
        }
    }

    public function test_tamu_dialihkan_ke_login(): void
    {
        $this->get('/master-schedule')->assertRedirect('/login');
    }

    // --- Skenario Online --------------------------------------------------

    public function test_master_schedule_online_memuat_field_minimum_dan_ringkasan(): void
    {
        $this->actingAs($this->admin())->get('/master-schedule')
            ->assertOk()
            ->assertSee('Basic Programming (Sesi 1)')
            ->assertSee('19.00–21.30')
            ->assertSee('2,5 jam')
            ->assertSee('Zoom Room A')
            ->assertSee('84 peserta')
            ->assertSee('Fondasi')                // kolom Catatan
            ->assertSee('6 hari pelaksanaan')
            ->assertSee('6 calendar days')
            ->assertSee('3 trainer')
            ->assertSee('3 room');
    }

    public function test_sesi_paralel_ditandai_dan_sesi_bp_tidak(): void
    {
        $r = app(MasterSchedule::class)->untukMode('Online');
        $baris = collect($r['baris'])->keyBy('sesi');

        $this->assertFalse($baris['Basic Programming (Sesi 1)']['paralel']);
        $this->assertTrue($baris['Web Development Lanjutan']['paralel']);
        $this->assertTrue($baris['Jaringan Dasar (gabungan)']['paralel']);
        $this->assertTrue($baris['Network Administrator Lanjutan (Sesi 1)']['paralel']);
        $this->assertSame('Common', $baris['Jaringan Dasar (gabungan)']['jenis']);
        $this->assertSame('Exclusive', $baris['Web Development Lanjutan']['jenis']);
    }

    // --- Skenario Offline -------------------------------------------------

    public function test_skenario_offline_ada_di_seed_dan_memenuhi_durasi_tiap_skema(): void
    {
        $this->assertSame(6, Batch::where('mode', 'Offline')->where('periode', '2026-11')->count());

        $offline = app(MasterSchedule::class)->untukMode('Offline');

        $this->assertCount(6, $offline['baris']);
        $this->assertSame(2, $offline['ringkasan']['hari_pelaksanaan']);
        $this->assertSame(2, $offline['ringkasan']['calendar_days']);
        $this->assertSame(3, $offline['ringkasan']['trainer']);
        $this->assertSame(3, $offline['ringkasan']['room']);
        $this->assertCount(6, $offline['durasi']);
        $this->assertTrue(collect($offline['durasi'])->every(fn ($d) => $d['ok']));
    }

    public function test_halaman_offline_tampil_dengan_ruang_dan_jam_offline(): void
    {
        $this->actingAs($this->admin())->get('/master-schedule?mode=Offline')
            ->assertOk()
            ->assertSee('Kelas Lab 1')
            ->assertSee('08.30–12.00')
            ->assertSee('08.30–15.30')
            ->assertSee('2 hari pelaksanaan')
            ->assertSee('0,5 hari');
    }

    public function test_skenario_offline_tidak_mengubah_dashboard_periode_aktif(): void
    {
        $this->actingAs($this->admin())->get('/dashboard')->assertOk()->assertSee('Oktober 2026');
        $this->assertSame(10, app(RingkasanOperasional::class)->hitung()['sesi']);
    }

    // --- Aturan integrasi -------------------------------------------------

    public function test_aturan_integrasi_terpenuhi_pada_kedua_skenario_seed(): void
    {
        $cek = app(AturanIntegrasi::class);

        foreach ([['2026-10', 'Online'], ['2026-11', 'Offline']] as [$periode, $mode]) {
            $hasil = collect($cek->untuk($periode, $mode));
            $this->assertCount(3, $hasil);
            $this->assertTrue($hasil->every(fn ($h) => $h['ok'] === true), "{$periode} {$mode}");
        }
    }

    public function test_jwp_yang_tidak_mengikuti_semua_sesi_bp_ditandai_tidak_sesuai(): void
    {
        $jwp = $this->batch('JWP', '2026-10', 'Online');
        $sesiBp = Sesi::where('nama_sesi', 'Basic Programming (Sesi 2)')->firstOrFail();
        DB::table('tbl_sesi_batch')->where('id_batch', $jwp->id_batch)->where('id_sesi', $sesiBp->id_sesi)->delete();

        $hasil = collect(app(AturanIntegrasi::class)->untuk('2026-10', 'Online'))->keyBy('kode');

        $this->assertFalse($hasil['jwp-bp']['ok']);
        $this->assertStringContainsString('JWP', $hasil['jwp-bp']['pesan']);
        $this->assertTrue($hasil['jna-na']['ok']);

        $this->actingAs($this->admin())->get('/validasi-durasi?periode=2026-10')
            ->assertSee('Penggabungan skema')
            ->assertSee('JWP wajib mengikuti BP')
            ->assertSee('Belum');
    }

    public function test_jna_dan_na_yang_tidak_digabung_ditandai_tidak_sesuai(): void
    {
        $na = $this->batch('NA', '2026-10', 'Online');
        $gabungan = Sesi::where('nama_sesi', 'Jaringan Dasar (gabungan)')->firstOrFail();
        DB::table('tbl_sesi_batch')->where('id_batch', $na->id_batch)->where('id_sesi', $gabungan->id_sesi)->delete();

        $hasil = collect(app(AturanIntegrasi::class)->untuk('2026-10', 'Online'))->keyBy('kode');

        $this->assertFalse($hasil['jna-na']['ok']);
    }

    public function test_panel_aturan_integrasi_tampil_di_program_master(): void
    {
        $this->actingAs($this->admin())->get('/data-master')
            ->assertSee('Penggabungan skema')
            ->assertSee('Basic Programming')
            ->assertSee('JWP wajib');
    }

    // --- Catatan ----------------------------------------------------------

    public function test_catatan_sesi_dapat_disimpan_dan_tampil_pada_jadwal(): void
    {
        $batch = $this->batch('JWP', '2026-10', 'Online');

        $this->actingAs($this->admin())->post('/jadwal-sesi', [
            'nama_sesi' => 'Sesi Catatan', 'tanggal' => '2026-10-26', 'durasi_hari' => '1',
            'jam_mulai' => '19:00', 'jam_selesai' => '21:30',
            'id_trainer' => Trainer::where('nama', 'Trainer 1')->value('id_trainer'),
            'room' => 'Zoom Room A', 'status' => 'Planned', 'id_batch' => [$batch->id_batch],
            'catatan' => 'Prasyarat: BP selesai',
        ])->assertSessionHas('sukses');

        $this->assertSame('Prasyarat: BP selesai', Sesi::where('nama_sesi', 'Sesi Catatan')->value('catatan'));
        $this->actingAs($this->admin())->get('/jadwal-sesi')->assertSee('Prasyarat: BP selesai');
    }

    public function test_jadwal_sesi_menandai_parallel(): void
    {
        $this->actingAs($this->admin())->get('/jadwal-sesi')->assertSee('Parallel');
    }

    // --- Per periode -------------------------------------------------------

    public function test_master_schedule_memakai_periode_yang_dipilih(): void
    {
        $ini = $this->actingAs($this->admin());

        $ini->get('/master-schedule?mode=Online&periode=2026-10')->assertOk()->assertSee('Oktober 2026')->assertSee('Basic Programming (Sesi 1)');

        // periode lain tanpa batch online diabaikan dan kembali ke periode terawal
        $ini->get('/master-schedule?mode=Online&periode=2030-01')->assertOk()->assertSee('Basic Programming (Sesi 1)');

        $ini->get('/master-schedule?mode=Offline&periode=2026-11')->assertOk()->assertSee('November 2026')->assertSee('Kelas Lab 1');
    }

    public function test_sesi_periode_lain_tidak_masuk_master_schedule(): void
    {
        $oktober = app(MasterSchedule::class)->untukMode('Online', '2026-10');
        $kosong = app(MasterSchedule::class)->untukMode('Online', '2027-05');

        $this->assertNotEmpty($oktober['baris']);
        $this->assertSame('2026-10', $oktober['periode']);
        $this->assertSame([], $kosong['baris']);
        $this->assertSame(0, $kosong['ringkasan']['hari_pelaksanaan']);
    }
}
