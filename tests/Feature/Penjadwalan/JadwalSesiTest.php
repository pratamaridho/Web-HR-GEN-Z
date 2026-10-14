<?php

namespace Tests\Feature\Penjadwalan;

use App\Modules\Akun\Models\User;
use App\Modules\Katalog\Models\Batch;
use App\Modules\Katalog\Models\Skema;
use App\Modules\Katalog\Models\Trainer;
use App\Modules\Penjadwalan\Actions\SimpanSesi;
use App\Modules\Penjadwalan\Models\Sesi;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * FR 07 (jadwal sesi) dan FR 08 (cek konflik, automation utama);
 * AC 07, AC 08; UAT 05 sampai UAT 09.
 */
class JadwalSesiTest extends TestCase
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

    private function trainer(string $kode): int
    {
        return Trainer::where('nama', 'Trainer '.$kode)->value('id_trainer');
    }

    private function batchIds(array $skema, string $periode = '2026-10', string $mode = 'Online'): array
    {
        return array_map(fn ($s) => Batch::firstOrCreate(
            ['id_skema' => Skema::where('nama_skema', $s)->value('id_skema'), 'periode' => $periode, 'mode' => $mode],
            ['status' => 'Planned']
        )->id_batch, $skema);
    }

    private function data(array $override = []): array
    {
        return array_merge([
            'nama_sesi' => 'Sesi Uji',
            'tanggal' => '2026-10-26',
            'durasi_hari' => '1',
            'jam_mulai' => '19:00',
            'jam_selesai' => '21:30',
            'id_trainer' => $this->trainer('1'),
            'room' => 'Zoom Room A',
            'status' => 'Planned',
            'id_batch' => $this->batchIds(['JWP']),
        ], $override);
    }

    private function simpan(array $override = [])
    {
        return $this->actingAs($this->admin())->from('/form-sesi')->post('/jadwal-sesi', $this->data($override));
    }

    // --- Akses ------------------------------------------------------------

    public function test_hanya_admin_membuka_jadwal_dan_menyimpan_sesi(): void
    {
        $this->actingAs($this->admin())->get('/jadwal-sesi')->assertOk();
        $this->actingAs($this->admin())->get('/form-sesi')->assertOk();

        foreach (['trainer@example.test', 'management@example.test'] as $email) {
            $user = User::where('email', $email)->first();
            $this->actingAs($user)->get('/jadwal-sesi')->assertForbidden();
            $this->actingAs($user)->get('/form-sesi')->assertForbidden();
            $this->actingAs($user)->post('/jadwal-sesi', $this->data())->assertForbidden();
        }

        $this->assertSame(16, Sesi::count());
    }

    // --- FR 07 ------------------------------------------------------------

    public function test_jadwal_tampil_per_hari_dengan_jenis_trainer_room_dan_skema(): void
    {
        $this->actingAs($this->admin())->get('/jadwal-sesi?tampil=daftar')
            ->assertOk()
            ->assertSee('Senin, 19 Oktober 2026')
            ->assertSee('Basic Programming (Sesi 1)')
            ->assertSee('Common')
            ->assertSee('Exclusive')
            ->assertSee('Trainer 1')
            ->assertSee('Zoom Room A')
            ->assertSee('19.00–21.30');
    }

    public function test_jadwal_dapat_difilter_skema_dan_trainer(): void
    {
        $this->actingAs($this->admin())->get('/jadwal-sesi?trainer='.$this->trainer('3'))
            ->assertSee('Teknisi Jaringan Dasar')
            ->assertDontSee('Basic Programming (Sesi 1)');

        $skemaJwp = Skema::where('nama_skema', 'JWP')->value('id_skema');
        $this->actingAs($this->admin())->get('/jadwal-sesi?skema='.$skemaJwp)
            ->assertSee('Web Development Lanjutan')
            ->assertSee('Basic Programming (Sesi 1)')
            ->assertDontSee('Teknisi Jaringan Dasar');
    }

    public function test_sesi_bersama_enam_batch_tersimpan_satu_baris_enam_tautan_jenis_common(): void
    {
        $semua = $this->batchIds(['BP', 'JNA', 'JWP', 'NA', 'TMJK', 'TUJK']);

        $this->simpan(['nama_sesi' => 'Basic Programming Uji', 'id_batch' => $semua])
            ->assertRedirect('/jadwal-sesi')
            ->assertSessionHas('sukses');

        $sesi = Sesi::where('nama_sesi', 'Basic Programming Uji')->sole();
        $this->assertSame('Common', $sesi->tipe);
        $this->assertSame('Planned', $sesi->status);
        $this->assertSame(6, DB::table('tbl_sesi_batch')->where('id_sesi', $sesi->id_sesi)->count());
        $this->assertSame(17, Sesi::count());
    }

    public function test_sesi_satu_batch_berjenis_exclusive(): void
    {
        $this->simpan(['nama_sesi' => 'Eksklusif Uji'])->assertSessionHas('sukses');

        $this->assertSame('Exclusive', Sesi::where('nama_sesi', 'Eksklusif Uji')->value('tipe'));
    }

    public function test_sesi_tanpa_batch_tidak_dapat_disimpan(): void
    {
        $this->simpan(['id_batch' => []])->assertSessionHasErrors('id_batch');

        $this->assertSame(16, Sesi::count());
    }

    public function test_kegagalan_menyimpan_tautan_batch_tidak_meninggalkan_baris_sesi(): void
    {
        try {
            app(SimpanSesi::class)->execute(
                $this->trainer('1'), 'Gagal', '2026-10-27', '19:00:00', '21:30:00', 1.0, 'Zoom Room A', [999999]
            );
            $this->fail('Seharusnya melempar exception karena batch tidak ada.');
        } catch (QueryException) {
            // diharapkan
        }

        $this->assertSame(16, Sesi::count());
        $this->assertDatabaseMissing('tbl_sesi', ['nama_sesi' => 'Gagal']);
    }

    public function test_input_sesi_divalidasi(): void
    {
        $this->simpan(['nama_sesi' => '', 'tanggal' => 'bukan-tanggal', 'jam_selesai' => '18:00', 'room' => 'Ruang Gaib'])
            ->assertSessionHasErrors(['nama_sesi', 'tanggal', 'jam_selesai', 'room']);
    }

    // --- FR 08 ------------------------------------------------------------

    public function test_trainer_bentrok_ditolak_dengan_pesan_trainer_dan_sesi_yang_bentrok(): void
    {
        // Seed: Jumat 23 Okt 19.00, Trainer 2, Zoom Room B (NA Lanjutan Sesi 1)
        $this->simpan(['tanggal' => '2026-10-23', 'id_trainer' => $this->trainer('2'), 'room' => 'Zoom Room A'])
            ->assertSessionHasErrors('id_trainer');

        $pesan = session('errors')->first('id_trainer');
        $this->assertStringContainsString('Trainer bentrok', $pesan);
        $this->assertStringContainsString('Trainer 2', $pesan);
        $this->assertStringContainsString('Network Administrator Lanjutan (Sesi 1)', $pesan);
        $this->assertSame(16, Sesi::count());
    }

    public function test_room_bentrok_ditolak_dengan_pesan_room_dan_sesi_yang_bentrok(): void
    {
        $this->simpan(['tanggal' => '2026-10-23', 'id_trainer' => $this->trainer('1'), 'room' => 'Zoom Room B'])
            ->assertSessionHasErrors('room');

        $pesan = session('errors')->first('room');
        $this->assertStringContainsString('Room bentrok', $pesan);
        $this->assertStringContainsString('Zoom Room B', $pesan);
        $this->assertStringContainsString('Network Administrator Lanjutan (Sesi 1)', $pesan);
        $this->assertSame(16, Sesi::count());
    }

    public function test_batch_online_dan_offline_dalam_satu_sesi_ditolak(): void
    {
        $campur = array_merge($this->batchIds(['JWP']), $this->batchIds(['JWP'], '2026-11', 'Offline'));

        $this->simpan(['id_batch' => $campur])->assertSessionHasErrors('id_batch');

        $this->assertStringContainsString('mode', session('errors')->first('id_batch'));
        $this->assertSame(16, Sesi::count());
    }

    public function test_sesi_paralel_dengan_trainer_dan_room_berbeda_pada_jam_sama_diterima(): void
    {
        $this->simpan(['nama_sesi' => 'Paralel A', 'id_trainer' => $this->trainer('1'), 'room' => 'Zoom Room A'])
            ->assertSessionHas('sukses');
        $this->simpan(['nama_sesi' => 'Paralel B', 'id_trainer' => $this->trainer('2'), 'room' => 'Zoom Room B', 'id_batch' => $this->batchIds(['NA'])])
            ->assertSessionHas('sukses');

        $this->assertSame(18, Sesi::count());
    }

    public function test_permintaan_yang_sama_dua_kali_tidak_membuat_sesi_rangkap(): void
    {
        $this->simpan(['nama_sesi' => 'Sekali'])->assertSessionHas('sukses');
        $this->simpan(['nama_sesi' => 'Sekali'])->assertSessionHasErrors();

        $this->assertSame(1, Sesi::where('nama_sesi', 'Sekali')->count());
    }

    public function test_pagar_database_menolak_sesi_bentrok_walau_validasi_terlewati(): void
    {
        $this->expectException(QueryException::class);

        Sesi::create([
            'id_trainer' => $this->trainer('2'), 'nama_sesi' => 'Langsung', 'tanggal' => '2026-10-23',
            'jam_mulai' => '19:00:00', 'jam_selesai' => '21:30:00', 'durasi_hari' => 1,
            'room' => 'Zoom Room C', 'tipe' => 'Exclusive', 'status' => 'Planned',
        ]);
    }

    // --- Ubah sesi --------------------------------------------------------

    public function test_ubah_sesi_tanpa_mengubah_jadwal_tidak_bentrok_dengan_dirinya_sendiri(): void
    {
        $sesi = Sesi::where('nama_sesi', 'Network Administrator Lanjutan (Sesi 1)')->firstOrFail();

        $this->actingAs($this->admin())->from('/form-sesi/'.$sesi->id_sesi)
            ->put('/jadwal-sesi/'.$sesi->id_sesi, $this->data([
                'nama_sesi' => 'NA Lanjutan Diubah',
                'tanggal' => '2026-10-23',
                'id_trainer' => $sesi->id_trainer,
                'room' => $sesi->room,
                'id_batch' => $sesi->batches()->pluck('tbl_batch.id_batch')->all(),
            ]))
            ->assertRedirect('/jadwal-sesi')
            ->assertSessionHas('sukses');

        $this->assertSame('NA Lanjutan Diubah', $sesi->fresh()->nama_sesi);
        $this->assertSame(16, Sesi::count());
    }

    public function test_ubah_sesi_ke_trainer_yang_bentrok_ditolak_dan_data_tidak_berubah(): void
    {
        // Kamis 22 Okt 19.00: Web Dev (T1), Jaringan Dasar (T2). Pindahkan Web Dev ke T2.
        $sesi = Sesi::where('nama_sesi', 'Web Development Lanjutan')->firstOrFail();
        $trainerAsal = $sesi->id_trainer;

        $this->actingAs($this->admin())->from('/form-sesi/'.$sesi->id_sesi)
            ->put('/jadwal-sesi/'.$sesi->id_sesi, $this->data([
                'nama_sesi' => $sesi->nama_sesi,
                'tanggal' => '2026-10-22',
                'id_trainer' => $this->trainer('2'),
                'room' => $sesi->room,
                'id_batch' => $sesi->batches()->pluck('tbl_batch.id_batch')->all(),
            ]))
            ->assertSessionHasErrors('id_trainer');

        $this->assertSame($trainerAsal, $sesi->fresh()->id_trainer);
    }

    public function test_ubah_sesi_memperbarui_jenis_sesuai_jumlah_batch(): void
    {
        $sesi = Sesi::where('nama_sesi', 'Web Development Lanjutan')->firstOrFail();
        $this->assertSame('Exclusive', $sesi->tipe);

        $this->actingAs($this->admin())
            ->put('/jadwal-sesi/'.$sesi->id_sesi, $this->data([
                'nama_sesi' => $sesi->nama_sesi,
                'tanggal' => '2026-10-22',
                'id_trainer' => $sesi->id_trainer,
                'room' => $sesi->room,
                'id_batch' => $this->batchIds(['JWP', 'NA']),
            ]))
            ->assertSessionHas('sukses');

        $this->assertSame('Common', $sesi->fresh()->tipe);
        $this->assertSame(2, $sesi->batches()->count());
    }
}
