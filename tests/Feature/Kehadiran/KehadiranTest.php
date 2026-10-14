<?php

namespace Tests\Feature\Kehadiran;

use App\Modules\Akun\Models\User;
use App\Modules\Kehadiran\Models\Kehadiran;
use App\Modules\Penjadwalan\Models\Sesi;
use App\Modules\Peserta\Models\Peserta;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FR 11 (kehadiran oleh trainer), AC 11, UAT 12, UAT 13.
 * Trainer di seed: Trainer 1 (T1) mengampu BP x3 dan Web Development Lanjutan.
 */
class KehadiranTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function trainer(): User
    {
        return User::where('email', 'trainer@example.test')->firstOrFail();
    }

    private function sesi(string $nama): Sesi
    {
        return Sesi::where('nama_sesi', $nama)->firstOrFail();
    }

    private function pesertaSesi(Sesi $sesi): array
    {
        return Peserta::whereHas('batches', fn ($b) => $b->whereIn('tbl_batch.id_batch', $sesi->batches()->pluck('tbl_batch.id_batch')))
            ->orderBy('id_peserta')->pluck('id_peserta')->all();
    }

    // --- Jadwal saya ------------------------------------------------------

    public function test_jadwal_saya_hanya_memuat_sesi_trainer_sendiri_dengan_jumlah_peserta(): void
    {
        $this->actingAs($this->trainer())->get('/jadwal-saya')
            ->assertOk()
            ->assertSee('Basic Programming (Sesi 1)')
            ->assertSee('Web Development Lanjutan')
            ->assertSee('84')
            ->assertSee('14')
            ->assertDontSee('Jaringan Dasar (gabungan)')
            ->assertDontSee('Network Administrator Lanjutan');
    }

    // --- Daftar peserta sesi ---------------------------------------------

    public function test_daftar_peserta_berasal_dari_batch_yang_dilayani_sesi(): void
    {
        $sesi = $this->sesi('Web Development Lanjutan'); // hanya JWP: 14 peserta

        $respons = $this->actingAs($this->trainer())->get('/kehadiran/'.$sesi->id_sesi)->assertOk();

        $this->assertCount(14, $this->pesertaSesi($sesi));
        $respons->assertSee('Peserta 029')      // JWP: peserta 029-042
            ->assertDontSee('Peserta 001');     // BP, bukan batch sesi ini
    }

    // --- Simpan kehadiran -------------------------------------------------

    public function test_menyimpan_membuat_satu_catatan_per_peserta_dan_simpan_ulang_tidak_merangkap(): void
    {
        $sesi = $this->sesi('Web Development Lanjutan');
        $ids = $this->pesertaSesi($sesi);
        $data = array_fill_keys($ids, 'Hadir');
        $data[$ids[0]] = 'Izin';

        $this->actingAs($this->trainer())
            ->post('/kehadiran/'.$sesi->id_sesi, ['kehadiran' => $data])
            ->assertRedirect('/kehadiran/'.$sesi->id_sesi)
            ->assertSessionHas('sukses');

        $this->assertSame(14, Kehadiran::where('id_sesi', $sesi->id_sesi)->count());

        $data[$ids[0]] = 'Alpa'; // ubah lalu simpan ulang
        $this->actingAs($this->trainer())->post('/kehadiran/'.$sesi->id_sesi, ['kehadiran' => $data])->assertSessionHas('sukses');

        $this->assertSame(14, Kehadiran::where('id_sesi', $sesi->id_sesi)->count());
        $this->assertSame('Alpa', Kehadiran::where('id_sesi', $sesi->id_sesi)->where('id_peserta', $ids[0])->value('status_hadir'));
    }

    public function test_pilihan_tersimpan_tampil_kembali_pada_form(): void
    {
        $sesi = $this->sesi('Web Development Lanjutan');
        $id = $this->pesertaSesi($sesi)[0];

        $this->actingAs($this->trainer())->post('/kehadiran/'.$sesi->id_sesi, ['kehadiran' => [$id => 'Izin']]);

        $this->actingAs($this->trainer())->get('/kehadiran/'.$sesi->id_sesi)
            ->assertSee('name="kehadiran['.$id.']" value="Izin" checked', false);
    }

    public function test_peserta_di_luar_batch_sesi_dan_status_tak_dikenal_ditolak_tanpa_menyimpan_apa_pun(): void
    {
        $sesi = $this->sesi('Web Development Lanjutan');
        $dalam = $this->pesertaSesi($sesi)[0];
        $luar = Peserta::where('email', 'peserta001@example.test')->value('id_peserta'); // BP

        $this->actingAs($this->trainer())->from('/kehadiran/'.$sesi->id_sesi)
            ->post('/kehadiran/'.$sesi->id_sesi, ['kehadiran' => [$dalam => 'Hadir', $luar => 'Hadir']])
            ->assertSessionHasErrors('kehadiran');

        $this->actingAs($this->trainer())->from('/kehadiran/'.$sesi->id_sesi)
            ->post('/kehadiran/'.$sesi->id_sesi, ['kehadiran' => [$dalam => 'Bolos']])
            ->assertSessionHasErrors('kehadiran.'.$dalam);

        $this->assertSame(0, Kehadiran::count());
    }

    // --- Pembatasan (UAT 13) ---------------------------------------------

    public function test_trainer_tidak_dapat_membuka_atau_mencatat_sesi_trainer_lain(): void
    {
        $milikT2 = $this->sesi('Jaringan Dasar (gabungan)');
        $peserta = $this->pesertaSesi($milikT2)[0];

        $this->actingAs($this->trainer())->get('/kehadiran/'.$milikT2->id_sesi)->assertForbidden();
        $this->actingAs($this->trainer())
            ->post('/kehadiran/'.$milikT2->id_sesi, ['kehadiran' => [$peserta => 'Hadir']])
            ->assertForbidden();

        $this->assertSame(0, Kehadiran::count());
    }

    public function test_admin_dan_management_tidak_mencatat_kehadiran(): void
    {
        $sesi = $this->sesi('Web Development Lanjutan');
        $peserta = $this->pesertaSesi($sesi)[0];

        foreach (['admin@example.test', 'management@example.test'] as $email) {
            $user = User::where('email', $email)->first();
            $this->actingAs($user)->get('/kehadiran/'.$sesi->id_sesi)->assertForbidden();
            $this->actingAs($user)->post('/kehadiran/'.$sesi->id_sesi, ['kehadiran' => [$peserta => 'Hadir']])->assertForbidden();
        }

        $this->assertSame(0, Kehadiran::count());
    }

    public function test_tamu_dialihkan_ke_login(): void
    {
        $sesi = $this->sesi('Web Development Lanjutan');

        $this->get('/kehadiran/'.$sesi->id_sesi)->assertRedirect('/login');
        $this->post('/kehadiran/'.$sesi->id_sesi, [])->assertRedirect('/login');
    }
}
