<?php

namespace Tests\Feature\Katalog;

use App\Modules\Akun\Models\User;
use App\Modules\Katalog\Models\Batch;
use App\Modules\Katalog\Models\Skema;
use App\Modules\Katalog\Models\Trainer;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * FR 02 (master skema), FR 03 (master trainer), FR 04 (batch bulanan);
 * AC 02, AC 03, AC 04; UAT 04.
 */
class DataMasterTest extends TestCase
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

    public function test_hanya_admin_membuka_data_master(): void
    {
        $this->actingAs($this->admin())->get('/data-master')->assertOk();
        $this->actingAs(User::where('email', 'trainer@example.test')->first())->get('/data-master')->assertForbidden();
        $this->actingAs(User::where('email', 'management@example.test')->first())->get('/data-master')->assertForbidden();
    }

    public function test_tanpa_login_dialihkan_ke_login(): void
    {
        $this->get('/data-master')->assertRedirect('/login');
        $this->post('/data-master/batch', [])->assertRedirect('/login');
    }

    public function test_trainer_dan_management_tidak_dapat_menambah_batch(): void
    {
        $data = ['id_skema' => Skema::first()->id_skema, 'periode' => '2026-11', 'mode' => 'Online'];

        foreach (['trainer@example.test', 'management@example.test'] as $email) {
            $this->actingAs(User::where('email', $email)->first())
                ->post('/data-master/batch', $data)
                ->assertForbidden();
        }

        $this->assertSame(12, Batch::count());
    }

    // --- FR 02 / AC 02 ----------------------------------------------------

    public function test_enam_skema_tampil_dengan_durasi_online_dan_offline(): void
    {
        $this->actingAs($this->admin())->get('/data-master')
            ->assertSeeInOrder(['BP', '3', '0,5'])
            ->assertSeeInOrder(['NA', '6', '2'])
            ->assertSeeInOrder(['TUJK', '6', '2']);

        foreach (Skema::pluck('nama_skema') as $nama) {
            $this->actingAs($this->admin())->get('/data-master')->assertSee($nama);
        }
    }

    public function test_nama_skema_ganda_ditolak_database(): void
    {
        $this->expectException(QueryException::class);

        Skema::create(['nama_skema' => 'BP', 'durasi_online' => 1, 'durasi_offline' => 1]);
    }

    // --- FR 03 / AC 03 ----------------------------------------------------

    public function test_tiga_trainer_tampil_dengan_kompetensinya(): void
    {
        $this->assertSame(3, Trainer::count());

        $this->actingAs($this->admin())->get('/data-master')
            ->assertSee('BP, JWP')
            ->assertSee('JNA, NA')
            ->assertSee('TMJK, TUJK');
    }

    // --- FR 04 / AC 04 ----------------------------------------------------

    public function test_enam_batch_2026_10_online_tampil(): void
    {
        $this->assertSame(6, Batch::where('periode', '2026-10')->where('mode', 'Online')->count());

        $this->actingAs($this->admin())->get('/data-master')
            ->assertSee('Batch 1 · Oktober 2026 · Online')
            ->assertSee('6 skema')
            ->assertSee('Planned');
    }

    public function test_batch_bulan_lain_untuk_skema_sama_diperbolehkan(): void
    {
        $skema = Skema::where('nama_skema', 'BP')->firstOrFail();

        $this->actingAs($this->admin())
            ->post('/data-master/batch', ['id_skema' => $skema->id_skema, 'periode' => '2026-11', 'mode' => 'Online'])
            ->assertRedirect('/data-master')
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('tbl_batch', [
            'id_skema' => $skema->id_skema, 'periode' => '2026-11', 'mode' => 'Online', 'status' => 'Planned',
        ]);
        $this->assertSame(13, Batch::count());
    }

    public function test_batch_ganda_ditolak_dengan_pesan_penyebab(): void
    {
        $skema = Skema::where('nama_skema', 'JWP')->firstOrFail();

        $this->actingAs($this->admin())->from('/data-master')
            ->post('/data-master/batch', ['id_skema' => $skema->id_skema, 'periode' => '2026-10', 'mode' => 'Online'])
            ->assertRedirect('/data-master')
            ->assertSessionHasErrors(['batch' => 'Batch sudah ada. JWP periode 2026-10 mode Online sudah terdaftar. Ganti periode atau mode.']);

        $this->assertSame(12, Batch::count());
    }

    public function test_masukan_batch_divalidasi(): void
    {
        $this->actingAs($this->admin())->from('/data-master')
            ->post('/data-master/batch', ['id_skema' => 9999, 'periode' => '2026-13', 'mode' => 'Hybrid'])
            ->assertSessionHasErrors(['id_skema', 'periode', 'mode']);

        $this->assertSame(12, Batch::count());
    }

    public function test_tidak_ada_rute_hapus_batch(): void
    {
        $batch = Batch::first();

        $this->actingAs($this->admin())->delete('/data-master/batch/'.$batch->id_batch)->assertStatus(404);

        $hapus = collect(Route::getRoutes())->filter(
            fn ($r) => in_array('DELETE', $r->methods(), true) && str_contains($r->uri(), 'batch')
        );
        $this->assertCount(0, $hapus);
    }
}
