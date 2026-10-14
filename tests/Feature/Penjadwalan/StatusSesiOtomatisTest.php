<?php

namespace Tests\Feature\Penjadwalan;

use App\Modules\Akun\Models\User;
use App\Modules\Katalog\Models\Batch;
use App\Modules\Katalog\Models\Trainer;
use App\Modules\Penjadwalan\Actions\PerbaruiStatusSesi;
use App\Modules\Penjadwalan\Models\Sesi;
use App\Modules\Peserta\Models\Peserta;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusSesiOtomatisTest extends TestCase
{
    use RefreshDatabase;

    private Sesi $sesi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        // BP sesi 1: 19.00-21.30 pada 19 Oktober 2026, melayani 84 peserta.
        $this->sesi = Sesi::where('nama_sesi', 'like', 'Basic Programming%')->orderBy('tanggal')->firstOrFail();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function jalankan(string $waktu): int
    {
        return app(PerbaruiStatusSesi::class)->execute(Carbon::parse($waktu));
    }

    public function test_sebelum_jam_mulai_tetap_planned(): void
    {
        $this->jalankan('2026-10-19 18:59:00');

        $this->assertSame('Planned', $this->sesi->fresh()->status);
    }

    public function test_saat_berlangsung_menjadi_ongoing(): void
    {
        $this->jalankan('2026-10-19 20:00:00');

        $this->assertSame('Ongoing', $this->sesi->fresh()->status);
    }

    public function test_setelah_jam_selesai_menjadi_completed(): void
    {
        $this->jalankan('2026-10-19 21:30:00');

        $this->assertSame('Completed', $this->sesi->fresh()->status);
    }

    public function test_sesi_yang_dibatalkan_manual_tidak_diubah(): void
    {
        $this->sesi->update(['status' => 'Cancelled']);

        $this->jalankan('2026-10-20 12:00:00');

        $this->assertSame('Cancelled', $this->sesi->fresh()->status);
    }

    public function test_peserta_di_bawah_kuota_membuat_sesi_otomatis_cancelled_saat_mulai(): void
    {
        $kecil = Batch::create(['id_skema' => Batch::first()->id_skema, 'periode' => '2026-12', 'mode' => 'Online', 'status' => 'Planned']);
        foreach (range(1, 3) as $i) {
            $p = Peserta::create(['nama' => "Uji {$i}", 'email' => "uji{$i}@example.test"]);
            $kecil->peserta()->attach($p->id_peserta, ['status' => 'Validated']);
        }
        $sesi = Sesi::create([
            'id_trainer' => Trainer::first()->id_trainer, 'nama_sesi' => 'Sesi kecil', 'tanggal' => '2026-12-07',
            'jam_mulai' => '19:00:00', 'jam_selesai' => '21:30:00', 'durasi_hari' => 1,
            'room' => 'Zoom Room C', 'tipe' => 'Exclusive', 'status' => 'Planned',
        ]);
        $sesi->batches()->attach($kecil->id_batch);

        $this->jalankan('2026-12-07 19:00:00');

        $hasil = $sesi->fresh();
        $this->assertSame('Cancelled', $hasil->status);
        $this->assertStringContainsString('Dibatalkan otomatis: peserta 3 dari minimal 5', $hasil->catatan);
    }

    public function test_kuota_terpenuhi_tidak_dibatalkan(): void
    {
        $this->jalankan('2026-10-19 19:00:00');

        $this->assertSame('Ongoing', $this->sesi->fresh()->status);
    }

    public function test_kelulusan_dihitung_ulang_saat_sesi_menjadi_completed(): void
    {
        $peserta = Peserta::whereHas('batches', fn ($b) => $b->whereIn('tbl_batch.id_batch', $this->sesi->batches->pluck('id_batch')))->firstOrFail();
        $this->sesi->peserta()->attach($peserta->id_peserta, ['status_hadir' => 'Hadir']);

        $this->jalankan('2026-10-19 21:31:00');

        $this->assertSame(
            'Completed',
            $peserta->batches()->whereIn('tbl_batch.id_batch', $this->sesi->batches->pluck('id_batch'))->first()->pivot->status,
        );
    }

    public function test_trainer_boleh_mencatat_dalam_24_jam_setelah_selesai_dan_tidak_sesudahnya(): void
    {
        $this->sesi->update(['status' => 'Completed']);
        $trainer = User::where('email', 'trainer@example.test')->firstOrFail();
        $peserta = Peserta::whereHas('batches', fn ($b) => $b->whereIn('tbl_batch.id_batch', $this->sesi->batches->pluck('id_batch')))->firstOrFail();
        $kirim = fn () => $this->actingAs($trainer)->from("/kehadiran/{$this->sesi->id_sesi}")
            ->post("/kehadiran/{$this->sesi->id_sesi}", ['kehadiran' => [$peserta->id_peserta => 'Hadir']]);

        Carbon::setTestNow('2026-10-20 20:00:00');
        $kirim()->assertSessionHas('sukses');

        Carbon::setTestNow('2026-10-20 21:31:00');
        $kirim()->assertSessionHasErrors('kehadiran');
    }

    public function test_sesi_dibatalkan_tidak_menerima_kehadiran(): void
    {
        $this->sesi->update(['status' => 'Cancelled']);
        $trainer = User::where('email', 'trainer@example.test')->firstOrFail();
        $peserta = Peserta::whereHas('batches', fn ($b) => $b->whereIn('tbl_batch.id_batch', $this->sesi->batches->pluck('id_batch')))->firstOrFail();

        $this->actingAs($trainer)->from("/kehadiran/{$this->sesi->id_sesi}")
            ->post("/kehadiran/{$this->sesi->id_sesi}", ['kehadiran' => [$peserta->id_peserta => 'Hadir']])
            ->assertSessionHasErrors('kehadiran');
    }

    public function test_perintah_terjadwal_memperbarui_status(): void
    {
        Carbon::setTestNow('2026-10-19 20:00:00');

        $this->artisan('sesi:perbarui-status')->assertExitCode(0);

        $this->assertSame('Ongoing', $this->sesi->fresh()->status);
    }

    public function test_status_yang_dikirim_form_hanya_planned_atau_cancelled(): void
    {
        $this->actingAs(User::where('email', 'admin@example.test')->firstOrFail())
            ->put("/jadwal-sesi/{$this->sesi->id_sesi}", [
                'nama_sesi' => $this->sesi->nama_sesi,
                'tanggal' => $this->sesi->tanggal->toDateString(),
                'jam_mulai' => '19:00', 'jam_selesai' => '21:30',
                'id_trainer' => $this->sesi->id_trainer, 'room' => $this->sesi->room,
                'id_batch' => $this->sesi->batches->pluck('id_batch')->all(),
                'status' => 'Completed',
            ])->assertSessionHasErrors('status');
    }

    public function test_mengubah_sesi_tanpa_status_tidak_mengembalikannya_ke_planned(): void
    {
        $this->sesi->update(['status' => 'Completed']);

        $this->actingAs(User::where('email', 'admin@example.test')->firstOrFail())
            ->put("/jadwal-sesi/{$this->sesi->id_sesi}", [
                'nama_sesi' => $this->sesi->nama_sesi,
                'tanggal' => $this->sesi->tanggal->toDateString(),
                'jam_mulai' => '19:00', 'jam_selesai' => '21:30',
                'id_trainer' => $this->sesi->id_trainer, 'room' => $this->sesi->room,
                'id_batch' => $this->sesi->batches->pluck('id_batch')->all(),
            ])->assertSessionHasNoErrors();

        $this->assertSame('Completed', $this->sesi->fresh()->status);
    }
}
