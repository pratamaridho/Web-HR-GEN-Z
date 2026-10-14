<?php

namespace Tests\Feature\Alur;

use App\Modules\Akun\Models\User;
use App\Modules\Katalog\Models\Batch;
use App\Modules\Katalog\Models\Skema;
use App\Modules\Peserta\Models\Peserta;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Penyederhanaan input: bawaan form sesi, tempel banyak peserta, daftar langsung
 * ke batch, dan buat enam batch sebulan sekali klik (PRD 6.1, P1).
 */
class InputMudahTest extends TestCase
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

    private function batchBp(): Batch
    {
        return Batch::where('id_skema', Skema::where('nama_skema', 'BP')->value('id_skema'))
            ->where('periode', '2026-10')->firstOrFail();
    }

    // --- Form sesi --------------------------------------------------------

    public function test_form_sesi_bertahap_dengan_bawaan_tanggal_terakhir_dan_jam_otomatis(): void
    {
        $this->actingAs($this->admin())->get('/form-sesi')
            ->assertOk()
            ->assertSee('value="2026-11-03"', false)   // tanggal sesi terakhir di jadwal
            ->assertSee('value="19:00"', false)
            ->assertSee('value="21:30"', false)
            ->assertSee('Pilih batch')
            ->assertSee('Atur jadwal')
            ->assertSee('Isi detail sesi')
            ->assertSee('Jam terisi otomatis')
            ->assertSee('Durasi dihitung otomatis')
            ->assertDontSee('name="durasi_hari"', false);
    }

    public function test_menu_admin_tidak_memuat_form_sesi_terpisah(): void
    {
        $this->actingAs($this->admin())->get('/dashboard')
            ->assertSee('data-menu="jadwal-sesi"', false)
            ->assertDontSee('data-menu="form-sesi"', false);
    }

    // --- Peserta ----------------------------------------------------------

    public function test_tambah_peserta_dapat_langsung_didaftarkan_ke_batch(): void
    {
        $batch = $this->batchBp();

        $this->actingAs($this->admin())
            ->post('/peserta', ['nama' => 'Ayu Lestari', 'email' => 'ayu@example.test', 'id_batch' => $batch->id_batch])
            ->assertSessionHas('sukses');

        $id = Peserta::where('email', 'ayu@example.test')->value('id_peserta');
        $this->assertDatabaseHas('tbl_daftar', ['id_batch' => $batch->id_batch, 'id_peserta' => $id, 'status' => 'Registered']);
    }

    public function test_tempel_banyak_peserta_tersimpan_dan_terdaftar_dengan_pemisah_apa_pun(): void
    {
        $batch = $this->batchBp();
        $daftar = "Ayu Lestari, ayu@example.test\n\nBudi Hartono;budi@example.test\r\nCitra Maharani\tcitra@example.test\n";

        $this->actingAs($this->admin())
            ->post('/peserta/impor', ['tempel' => $daftar, 'id_batch' => $batch->id_batch])
            ->assertRedirect('/peserta')
            ->assertSessionHas('sukses');

        $this->assertSame(87, Peserta::count());
        $this->assertSame(3, DB::table('tbl_daftar')->where('id_batch', $batch->id_batch)
            ->whereIn('id_peserta', Peserta::whereIn('email', ['ayu@example.test', 'budi@example.test', 'citra@example.test'])->pluck('id_peserta'))
            ->count());
    }

    public function test_tempel_tanpa_batch_hanya_menyimpan_peserta(): void
    {
        $this->actingAs($this->admin())
            ->post('/peserta/impor', ['tempel' => 'Ayu Lestari, ayu@example.test'])
            ->assertSessionHas('sukses');

        $this->assertSame(85, Peserta::count());
        $this->assertSame(168, DB::table('tbl_daftar')->count());
    }

    public function test_tempel_dengan_baris_salah_tidak_menyimpan_apa_pun_dan_menyebut_nomor_baris(): void
    {
        $ada = Peserta::where('email', 'peserta001@example.test')->firstOrFail();
        $daftar = "Ayu Lestari, ayu@example.test\nBudi Hartono, bukan-email\nCitra, peserta001@example.test\nDian Permana, ayu@example.test";

        $respons = $this->actingAs($this->admin())->from('/peserta')
            ->post('/peserta/impor', ['tempel' => $daftar])
            ->assertRedirect('/peserta')
            ->assertSessionHasErrors('tempel');

        $pesan = session('errors')->first('tempel');
        $this->assertStringContainsString('Baris 2', $pesan);
        $this->assertStringContainsString('Baris 3', $pesan);
        $this->assertStringContainsString($ada->nama, $pesan);
        $this->assertStringContainsString('Baris 4', $pesan);
        $this->assertSame(84, Peserta::count());
    }

    public function test_tempel_kosong_ditolak_dan_hanya_admin_yang_boleh(): void
    {
        $this->actingAs($this->admin())->from('/peserta')
            ->post('/peserta/impor', ['tempel' => "  \n "])
            ->assertSessionHasErrors('tempel');

        foreach (['trainer@example.test', 'management@example.test'] as $email) {
            $this->actingAs(User::where('email', $email)->first())
                ->post('/peserta/impor', ['tempel' => 'A, a@example.test'])
                ->assertForbidden();
        }

        $this->assertSame(84, Peserta::count());
    }

    // --- Batch bulanan ----------------------------------------------------

    public function test_enam_batch_bulan_baru_dibuat_sekali_klik_dan_tidak_berulang(): void
    {
        $this->actingAs($this->admin())
            ->post('/data-master/batch-bulanan', ['periode' => '2026-11', 'mode' => 'Online'])
            ->assertRedirect('/data-master')
            ->assertSessionHas('sukses');

        $this->assertSame(6, Batch::where('periode', '2026-11')->where('mode', 'Online')->count());
        $this->assertSame(['Planned'], Batch::where('periode', '2026-11')->pluck('status')->unique()->all());

        $this->actingAs($this->admin())
            ->post('/data-master/batch-bulanan', ['periode' => '2026-11', 'mode' => 'Online'])
            ->assertSessionHas('sukses');

        $this->assertSame(18, Batch::count());
    }

    public function test_batch_bulanan_hanya_melengkapi_yang_belum_ada(): void
    {
        $this->actingAs($this->admin())
            ->post('/data-master/batch-bulanan', ['periode' => '2026-10', 'mode' => 'Online']);

        $this->assertSame(12, Batch::count());

        $this->actingAs($this->admin())
            ->post('/data-master/batch-bulanan', ['periode' => '2026-10', 'mode' => 'Offline']);

        $this->assertSame(18, Batch::count());
    }

    public function test_batch_bulanan_divalidasi_dan_hanya_admin(): void
    {
        $this->actingAs($this->admin())->from('/data-master')
            ->post('/data-master/batch-bulanan', ['periode' => '2026-13', 'mode' => 'Online'])
            ->assertSessionHasErrors('periode');

        $this->actingAs(User::where('email', 'trainer@example.test')->first())
            ->post('/data-master/batch-bulanan', ['periode' => '2026-11', 'mode' => 'Online'])
            ->assertForbidden();

        $this->assertSame(12, Batch::count());
    }
}
