<?php

namespace Tests\Feature\Peserta;

use App\Modules\Akun\Models\User;
use App\Modules\Katalog\Models\Batch;
use App\Modules\Katalog\Models\Skema;
use App\Modules\Peserta\Models\Daftar;
use App\Modules\Peserta\Models\Peserta;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FR 05 (data peserta), FR 06 (pendaftaran ke batch); AC 05, AC 06; UAT 03, UAT 11.
 */
class PesertaTest extends TestCase
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

    private function batch(string $skema, string $periode = '2026-10', string $mode = 'Online'): Batch
    {
        return Batch::firstOrCreate(
            ['id_skema' => Skema::where('nama_skema', $skema)->value('id_skema'), 'periode' => $periode, 'mode' => $mode],
            ['status' => 'Planned']
        );
    }

    // --- Akses ------------------------------------------------------------

    public function test_hanya_admin_membuka_dan_mengubah_peserta(): void
    {
        $this->actingAs($this->admin())->get('/peserta')->assertOk();

        foreach (['trainer@example.test', 'management@example.test'] as $email) {
            $user = User::where('email', $email)->first();
            $this->actingAs($user)->get('/peserta')->assertForbidden();
            $this->actingAs($user)->post('/peserta', ['nama' => 'X', 'email' => 'x@example.test'])->assertForbidden();
            $this->actingAs($user)->post('/peserta/daftar', [])->assertForbidden();
        }

        $this->assertSame(84, Peserta::count());
    }

    // --- FR 05 ------------------------------------------------------------

    public function test_daftar_peserta_tampil_dan_dapat_dicari_dan_difilter_batch(): void
    {
        // Baris kartu batch dipakai sebagai acuan karena dropdown pendaftaran memuat semua email.
        $sel = fn (string $email) => '<span class="block truncate text-xs text-mute">'.$email.'</span>';

        $this->actingAs($this->admin())->get('/peserta')
            ->assertOk()
            ->assertSee($sel('peserta001@example.test'), false)
            ->assertSee('6 batch · 84 peserta');

        $this->actingAs($this->admin())->get('/peserta?q=peserta084')
            ->assertSee($sel('peserta084@example.test'), false)
            ->assertDontSee($sel('peserta001@example.test'), false);

        $idBatch = $this->batch('BP')->id_batch;
        $this->actingAs($this->admin())->get('/peserta?batch='.$idBatch)
            ->assertSee($sel('peserta001@example.test'), false)
            ->assertDontSee($sel('peserta084@example.test'), false);
    }

    public function test_peserta_baru_dengan_email_baru_tersimpan(): void
    {
        $this->actingAs($this->admin())
            ->post('/peserta', ['nama' => 'Ayu Lestari', 'email' => 'ayu.lestari@example.test'])
            ->assertRedirect('/peserta')
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('tbl_peserta', ['email' => 'ayu.lestari@example.test', 'nama' => 'Ayu Lestari']);
        $this->assertSame(85, Peserta::count());
    }

    public function test_email_ganda_ditolak_dengan_pesan_jelas(): void
    {
        $ada = Peserta::where('email', 'peserta001@example.test')->firstOrFail();

        $this->actingAs($this->admin())->from('/peserta')
            ->post('/peserta', ['nama' => 'Orang Lain', 'email' => 'peserta001@example.test'])
            ->assertRedirect('/peserta')
            ->assertSessionHasErrors(['email' => "Email peserta001@example.test sudah tercatat atas nama {$ada->nama}. Satu orang hanya boleh punya satu data peserta."]);

        $this->assertSame(84, Peserta::count());
    }

    public function test_input_peserta_divalidasi(): void
    {
        $this->actingAs($this->admin())->from('/peserta')
            ->post('/peserta', ['nama' => '', 'email' => 'bukan-email'])
            ->assertSessionHasErrors(['nama', 'email']);
    }

    // --- FR 06 ------------------------------------------------------------

    public function test_pendaftaran_ke_batch_tersimpan_dengan_status_registered(): void
    {
        $peserta = Peserta::create(['nama' => 'Baru', 'email' => 'baru@example.test']);
        $batch = $this->batch('NA');

        $this->actingAs($this->admin())
            ->post('/peserta/daftar', ['id_peserta' => [$peserta->id_peserta], 'id_batch' => $batch->id_batch])
            ->assertRedirect('/peserta')
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('tbl_daftar', [
            'id_batch' => $batch->id_batch, 'id_peserta' => $peserta->id_peserta, 'status' => 'Registered',
        ]);
    }

    public function test_pendaftaran_kedua_pada_batch_sama_ditolak(): void
    {
        $peserta = Peserta::where('email', 'peserta014@example.test')->firstOrFail(); // BP
        $batch = $this->batch('BP');
        $sebelum = Daftar::count();

        $this->actingAs($this->admin())->from('/peserta')
            ->post('/peserta/daftar', ['id_peserta' => [$peserta->id_peserta], 'id_batch' => $batch->id_batch])
            ->assertSessionHasErrors('daftar');

        $this->assertStringContainsString("{$peserta->nama} sudah di Batch 1 · Basic Programming 2026 · Online.", session('errors')->first('daftar'));

        $this->assertSame($sebelum, Daftar::count());
    }

    public function test_peserta_sama_dapat_didaftarkan_ke_batch_periode_berikutnya_tanpa_data_baru(): void
    {
        $peserta = Peserta::where('email', 'peserta001@example.test')->firstOrFail();
        $november = $this->batch('BP', '2026-11');

        $this->actingAs($this->admin())
            ->post('/peserta/daftar', ['id_peserta' => [$peserta->id_peserta], 'id_batch' => $november->id_batch])
            ->assertSessionHas('sukses');

        $this->assertSame(84, Peserta::count());
        $this->assertSame(3, Daftar::where('id_peserta', $peserta->id_peserta)->count());
    }

    public function test_pendaftaran_banyak_peserta_dibatalkan_seluruhnya_bila_salah_satu_ganda(): void
    {
        $baru = Peserta::create(['nama' => 'Baru', 'email' => 'baru@example.test']);
        $sudah = Peserta::where('email', 'peserta001@example.test')->firstOrFail(); // BP
        $batch = $this->batch('BP');

        $this->actingAs($this->admin())->from('/peserta')
            ->post('/peserta/daftar', ['id_peserta' => [$baru->id_peserta, $sudah->id_peserta], 'id_batch' => $batch->id_batch])
            ->assertSessionHasErrors('daftar');

        $this->assertDatabaseMissing('tbl_daftar', ['id_peserta' => $baru->id_peserta]);
    }

    public function test_daftar_peserta_batch_tampil_pada_peserta_batch(): void
    {
        $batch = $this->batch('TUJK');

        $this->assertSame(14, $batch->peserta()->count());
    }

    public function test_peserta_skema_lain_tidak_boleh_masuk_batch_kedua_pada_periode_dan_mode_sama(): void
    {
        $peserta = Peserta::whereHas('batches', fn ($q) => $q->where('tbl_batch.mode', 'Online')->where('tbl_batch.periode', '2026-10'))->firstOrFail();
        $punya = $peserta->batches()->where('tbl_batch.periode', '2026-10')->where('tbl_batch.mode', 'Online')->firstOrFail();
        $lain = Batch::where('periode', '2026-10')->where('mode', 'Online')->where('id_batch', '!=', $punya->id_batch)->firstOrFail();
        $sebelum = Daftar::count();

        $this->actingAs($this->admin())->from('/peserta')
            ->post('/peserta/daftar', ['id_peserta' => [$peserta->id_peserta], 'id_batch' => $lain->id_batch])
            ->assertSessionHasErrors('daftar');

        $pesan = session('errors')->first('daftar');
        $this->assertStringContainsString("{$peserta->nama} sudah di", $pesan);
        $this->assertStringContainsString('Satu peserta hanya boleh punya satu batch per bulan dan mode.', $pesan);
        $this->assertSame($sebelum, Daftar::count());
    }

    public function test_mode_berbeda_pada_periode_sama_diperbolehkan(): void
    {
        $peserta = Peserta::whereHas('batches', fn ($q) => $q->where('tbl_batch.periode', '2026-10')->where('tbl_batch.mode', 'Online'))->firstOrFail();
        $skema = $peserta->batches()->where('tbl_batch.periode', '2026-10')->firstOrFail()->id_skema;
        $offline = Batch::create(['id_skema' => $skema, 'periode' => '2026-10', 'mode' => 'Offline', 'status' => 'Planned']);

        $this->actingAs($this->admin())
            ->post('/peserta/daftar', ['id_peserta' => [$peserta->id_peserta], 'id_batch' => $offline->id_batch])
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('tbl_daftar', ['id_batch' => $offline->id_batch, 'id_peserta' => $peserta->id_peserta]);
    }
}
