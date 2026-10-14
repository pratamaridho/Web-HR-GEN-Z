<?php

namespace Tests\Feature\Penjadwalan;

use App\Modules\Akun\Models\User;
use App\Modules\Katalog\Models\Batch;
use App\Modules\Katalog\Models\Skema;
use App\Modules\Penjadwalan\Models\Sesi;
use App\Modules\Penjadwalan\Queries\DurasiSkema;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FR 09 (validasi durasi skema), AC 09, UAT 10; FR 10 (status batch), AC 10.
 */
class ValidasiDurasiTest extends TestCase
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

    // --- Akses ------------------------------------------------------------

    public function test_hanya_admin_membuka_validasi_durasi(): void
    {
        $this->actingAs($this->admin())->get('/validasi-durasi')->assertOk();

        foreach (['trainer@example.test', 'management@example.test'] as $email) {
            $this->actingAs(User::where('email', $email)->first())->get('/validasi-durasi')->assertForbidden();
        }

    }

    public function test_tamu_dialihkan_ke_login(): void
    {
        $this->get('/validasi-durasi')->assertRedirect('/login');
    }

    // --- FR 09 ------------------------------------------------------------

    public function test_data_seed_memenuhi_durasi_enam_dari_enam_skema(): void
    {
        $this->actingAs($this->admin())->get('/validasi-durasi')
            ->assertOk()
            ->assertSee('6 dari 6 skema sesuai')
            ->assertDontSee('Kurang');
    }

    public function test_sesi_bersama_dihitung_ke_setiap_skema_tanpa_dihitung_ganda_dalam_satu_skema(): void
    {
        $baris = app(DurasiSkema::class)->untukPeriode('2026-10');

        $this->assertCount(6, $baris);
        $this->assertSame(
            ['BP' => 3.0, 'JNA' => 4.0, 'JWP' => 4.0, 'NA' => 6.0, 'TMJK' => 4.0, 'TUJK' => 6.0],
            $baris->mapWithKeys(fn ($r) => [$r['skema'] => $r['terjadwal']])->all()
        );
    }

    public function test_memperpendek_satu_sesi_mengubah_skema_terkait_menjadi_tidak_sesuai(): void
    {
        Sesi::where('nama_sesi', 'Network Administrator Lanjutan (Sesi 1)')->update(['durasi_hari' => 0.5]);

        $this->actingAs($this->admin())->get('/validasi-durasi')
            ->assertSee('5 dari 6 skema sesuai')
            ->assertSee('Kurang')
            ->assertSee('0,5 hari');
    }

    public function test_sesi_dibatalkan_tidak_dihitung(): void
    {
        Sesi::where('nama_sesi', 'Teknisi Utama Lanjutan (Sesi 1)')->update(['status' => 'Cancelled']);

        $this->actingAs($this->admin())->get('/validasi-durasi')
            ->assertSee('5 dari 6 skema sesuai')
            ->assertSee('1 hari');
    }

    public function test_periode_lain_tanpa_sesi_tidak_sesuai_dan_tidak_mengganggu_periode_aktif(): void
    {
        Batch::create([
            'id_skema' => Skema::where('nama_skema', 'BP')->value('id_skema'),
            'periode' => '2026-12', 'mode' => 'Offline', 'status' => 'Planned',
        ]);

        $this->actingAs($this->admin())->get('/validasi-durasi')->assertSee('6 dari 6 skema sesuai');

        $this->actingAs($this->admin())->get('/validasi-durasi?periode=2026-12')
            ->assertSee('0 dari 1 skema sesuai')
            ->assertSee('0,5 hari');
    }

    // --- FR 10 / AC 10: status batch -------------------------------------

    public function test_admin_mengubah_status_batch_dan_data_tidak_terhapus(): void
    {
        $batch = Batch::first();

        $this->actingAs($this->admin())
            ->patch('/data-master/batch/'.$batch->id_batch.'/status', ['status' => 'Completed'])
            ->assertRedirect('/data-master')
            ->assertSessionHas('sukses');

        $this->assertSame('Completed', $batch->fresh()->status);
        $this->assertSame(12, Batch::count());
        $this->actingAs($this->admin())->get('/data-master')->assertSee('Completed');
    }

    public function test_status_batch_divalidasi_dan_hanya_admin_yang_mengubah(): void
    {
        $batch = Batch::first();

        $this->actingAs($this->admin())->from('/data-master')
            ->patch('/data-master/batch/'.$batch->id_batch.'/status', ['status' => 'Dihapus'])
            ->assertSessionHasErrors('status');

        foreach (['trainer@example.test', 'management@example.test'] as $email) {
            $this->actingAs(User::where('email', $email)->first())
                ->patch('/data-master/batch/'.$batch->id_batch.'/status', ['status' => 'Cancelled'])
                ->assertForbidden();
        }

        $this->assertSame('Planned', $batch->fresh()->status);
    }
}
