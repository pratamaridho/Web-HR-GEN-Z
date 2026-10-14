<?php

namespace Tests\Feature\Kehadiran;

use App\Modules\Akun\Models\User;
use App\Modules\Katalog\Models\Batch;
use App\Modules\Katalog\Models\Skema;
use App\Modules\Kehadiran\Models\Kehadiran;
use App\Modules\Penjadwalan\Actions\UbahSesi;
use App\Modules\Penjadwalan\Models\Sesi;
use App\Modules\Peserta\Models\Peserta;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * FR 12 (koreksi kehadiran oleh admin) dan FR 13 (status penyelesaian peserta);
 * AC 12, AC 13, UAT 16. Ambang 80% dan hanya sesi Completed (usulan, OP 02).
 */
class RekapKehadiranTest extends TestCase
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

    private function batch(string $skema): Batch
    {
        return Batch::where('id_skema', Skema::where('nama_skema', $skema)->value('id_skema'))
            ->where('periode', '2026-10')->firstOrFail();
    }

    /** Peserta ke-$n pada skema (urut id). */
    private function peserta(string $skema, int $n = 1): Peserta
    {
        return $this->batch($skema)->peserta()->orderBy('tbl_peserta.id_peserta')->skip($n - 1)->firstOrFail();
    }

    private function sesiBatch(Batch $batch)
    {
        return Sesi::whereHas('batches', fn ($b) => $b->where('tbl_batch.id_batch', $batch->id_batch))
            ->orderBy('tanggal')->orderBy('id_sesi')->get();
    }

    private function selesaikan(Sesi $sesi, string $status = 'Completed'): void
    {
        app(UbahSesi::class)->execute($sesi, ['status' => $status], $sesi->batches()->pluck('tbl_batch.id_batch')->all());
    }

    private function koreksi(Peserta $p, Batch $b, array $statusPerSesi)
    {
        return $this->actingAs($this->admin())->post(
            "/rekap-kehadiran/{$p->id_peserta}/{$b->id_batch}/koreksi",
            ['kehadiran' => $statusPerSesi]
        );
    }

    private function statusDaftar(Peserta $p, Batch $b): string
    {
        return DB::table('tbl_daftar')->where('id_peserta', $p->id_peserta)->where('id_batch', $b->id_batch)->value('status');
    }

    // --- Akses ------------------------------------------------------------

    public function test_hanya_admin_membuka_rekap_dan_koreksi(): void
    {
        $p = $this->peserta('BP');
        $b = $this->batch('BP');

        $this->actingAs($this->admin())->get('/rekap-kehadiran')->assertOk();
        $this->actingAs($this->admin())->get("/rekap-kehadiran/{$p->id_peserta}/{$b->id_batch}/koreksi")->assertOk();

        foreach (['trainer@example.test', 'management@example.test'] as $email) {
            $user = User::where('email', $email)->first();
            $this->actingAs($user)->get('/rekap-kehadiran')->assertForbidden();
            $this->actingAs($user)->post("/rekap-kehadiran/{$p->id_peserta}/{$b->id_batch}/koreksi", ['kehadiran' => []])->assertForbidden();
        }
    }

    public function test_tamu_dialihkan_ke_login(): void
    {
        $this->get('/rekap-kehadiran')->assertRedirect('/login');
    }

    // --- FR 12 ------------------------------------------------------------

    public function test_rekap_menampilkan_jumlah_hadir_izin_alpa_per_peserta(): void
    {
        $p = $this->peserta('BP');
        $b = $this->batch('BP');
        [$s1, $s2, $s3] = $this->sesiBatch($b);

        $this->koreksi($p, $b, [$s1->id_sesi => 'Hadir', $s2->id_sesi => 'Izin', $s3->id_sesi => 'Alpa'])
            ->assertSessionHas('sukses');

        $this->actingAs($this->admin())->get('/rekap-kehadiran?q='.urlencode($p->email))
            ->assertOk()
            ->assertSee($p->nama)
            ->assertSeeInOrder(['>1<', '>1<', '>1<'], false);
    }

    public function test_admin_mengoreksi_alpa_menjadi_izin_dan_rekap_serta_dashboard_diperbarui(): void
    {
        $p = $this->peserta('BP');
        $b = $this->batch('BP');
        [$s1] = $this->sesiBatch($b);

        $this->koreksi($p, $b, [$s1->id_sesi => 'Alpa']);
        $this->assertSame('Alpa', Kehadiran::where('id_peserta', $p->id_peserta)->where('id_sesi', $s1->id_sesi)->value('status_hadir'));

        Carbon::setTestNow('2026-10-25 08:00:00');
        $this->koreksi($p, $b, [$s1->id_sesi => 'Izin'])->assertSessionHas('sukses');

        $this->assertSame(1, Kehadiran::where('id_peserta', $p->id_peserta)->where('id_sesi', $s1->id_sesi)->count());
        $this->assertSame('Izin', Kehadiran::where('id_peserta', $p->id_peserta)->where('id_sesi', $s1->id_sesi)->value('status_hadir'));
        $this->actingAs($this->admin())->get('/dashboard')->assertSee('Izin (1)');
        Carbon::setTestNow();
    }

    public function test_form_koreksi_menampilkan_pilihan_sebelumnya(): void
    {
        $p = $this->peserta('BP');
        $b = $this->batch('BP');
        [$s1] = $this->sesiBatch($b);
        $this->koreksi($p, $b, [$s1->id_sesi => 'Alpa']);

        $this->actingAs($this->admin())->get("/rekap-kehadiran/{$p->id_peserta}/{$b->id_batch}/koreksi")
            ->assertSee('Sebelumnya: Alpa')
            ->assertSee('name="kehadiran['.$s1->id_sesi.']" value="Alpa" checked', false);
    }

    public function test_koreksi_untuk_sesi_di_luar_batch_peserta_ditolak(): void
    {
        $p = $this->peserta('BP');
        $b = $this->batch('BP');
        $luar = Sesi::where('nama_sesi', 'Web Development Lanjutan')->firstOrFail(); // hanya JWP

        $this->actingAs($this->admin())->from('/x')
            ->post("/rekap-kehadiran/{$p->id_peserta}/{$b->id_batch}/koreksi", ['kehadiran' => [$luar->id_sesi => 'Hadir']])
            ->assertSessionHasErrors('kehadiran');

        $this->assertSame(0, Kehadiran::count());
    }

    public function test_peserta_yang_tidak_terdaftar_di_batch_tidak_dapat_dikoreksi(): void
    {
        $p = $this->peserta('BP');
        $b = $this->batch('NA');

        $this->actingAs($this->admin())
            ->get("/rekap-kehadiran/{$p->id_peserta}/{$b->id_batch}/koreksi")
            ->assertNotFound();
    }

    public function test_trainer_tidak_dapat_mengubah_kehadiran_setelah_sesi_completed(): void
    {
        $sesi = Sesi::where('nama_sesi', 'Web Development Lanjutan')->firstOrFail();
        $peserta = $this->peserta('JWP');
        $trainer = User::where('email', 'trainer@example.test')->firstOrFail();

        $this->actingAs($trainer)->post('/kehadiran/'.$sesi->id_sesi, ['kehadiran' => [$peserta->id_peserta => 'Hadir']])
            ->assertSessionHas('sukses');

        $this->selesaikan($sesi);
        Carbon::setTestNow($sesi->fresh()->selesai->copy()->addHours(25));

        $this->actingAs($trainer)->from('/kehadiran/'.$sesi->id_sesi)
            ->post('/kehadiran/'.$sesi->id_sesi, ['kehadiran' => [$peserta->id_peserta => 'Alpa']])
            ->assertSessionHasErrors('kehadiran');

        $this->assertSame('Hadir', Kehadiran::where('id_sesi', $sesi->id_sesi)->value('status_hadir'));
    }

    // --- FR 13 ------------------------------------------------------------

    public function test_peserta_dengan_kehadiran_memenuhi_ambang_ditandai_completed_dan_yang_di_bawahnya_tidak(): void
    {
        $b = $this->batch('NA'); // sesi: BP x3, Jaringan Dasar, NA Lanjutan x2 = 6 sesi
        $sesi = $this->sesiBatch($b);
        $this->assertCount(6, $sesi);
        foreach ($sesi as $s) {
            $this->selesaikan($s);
        }

        $lulus = $this->peserta('NA', 1);   // hadir 5 dari 6 = 83%
        $kurang = $this->peserta('NA', 2);  // hadir 4 dari 6 = 67%
        $tanpa = $this->peserta('NA', 3);   // tanpa catatan

        $this->koreksi($lulus, $b, $sesi->mapWithKeys(fn ($s, $i) => [$s->id_sesi => $i < 5 ? 'Hadir' : 'Alpa'])->all());
        $this->koreksi($kurang, $b, $sesi->mapWithKeys(fn ($s, $i) => [$s->id_sesi => $i < 4 ? 'Hadir' : 'Izin'])->all());

        $this->assertSame('Completed', $this->statusDaftar($lulus, $b));
        $this->assertSame('Validated', $this->statusDaftar($kurang, $b));
        $this->assertSame('Validated', $this->statusDaftar($tanpa, $b));
    }

    public function test_perubahan_kehadiran_menghitung_ulang_status(): void
    {
        $b = $this->batch('BP');
        $sesi = $this->sesiBatch($b);
        foreach ($sesi as $s) {
            $this->selesaikan($s);
        }
        $p = $this->peserta('BP');

        $this->koreksi($p, $b, $sesi->mapWithKeys(fn ($s) => [$s->id_sesi => 'Hadir'])->all());
        $this->assertSame('Completed', $this->statusDaftar($p, $b));

        $this->koreksi($p, $b, [$sesi[2]->id_sesi => 'Alpa']); // 2 dari 3 = 67%
        $this->assertSame('Ongoing', $this->statusDaftar($p, $b));
    }

    public function test_hanya_sesi_completed_yang_dihitung(): void
    {
        $b = $this->batch('BP');
        $sesi = $this->sesiBatch($b); // semua Planned
        $p = $this->peserta('BP');

        $this->koreksi($p, $b, $sesi->mapWithKeys(fn ($s) => [$s->id_sesi => 'Hadir'])->all());

        $this->assertSame('Validated', $this->statusDaftar($p, $b));
    }

    public function test_menandai_sesi_completed_menghitung_ulang_status_peserta(): void
    {
        $b = $this->batch('BP');
        $sesi = $this->sesiBatch($b);
        $p = $this->peserta('BP');

        $this->koreksi($p, $b, $sesi->mapWithKeys(fn ($s) => [$s->id_sesi => 'Hadir'])->all());
        $this->assertSame('Validated', $this->statusDaftar($p, $b));

        foreach ($sesi as $s) {
            $this->selesaikan($s);
        }

        $this->assertSame('Completed', $this->statusDaftar($p, $b));
    }

    public function test_peserta_dropped_tidak_diubah(): void
    {
        $b = $this->batch('BP');
        $sesi = $this->sesiBatch($b);
        foreach ($sesi as $s) {
            $this->selesaikan($s);
        }
        $p = $this->peserta('BP');
        DB::table('tbl_daftar')->where('id_peserta', $p->id_peserta)->where('id_batch', $b->id_batch)->update(['status' => 'Dropped']);

        $this->koreksi($p, $b, $sesi->mapWithKeys(fn ($s) => [$s->id_sesi => 'Hadir'])->all());

        $this->assertSame('Dropped', $this->statusDaftar($p, $b));
    }

    public function test_status_completed_tampil_pada_rekap(): void
    {
        $b = $this->batch('BP');
        $sesi = $this->sesiBatch($b);
        foreach ($sesi as $s) {
            $this->selesaikan($s);
        }
        $p = $this->peserta('BP');
        $this->koreksi($p, $b, $sesi->mapWithKeys(fn ($s) => [$s->id_sesi => 'Hadir'])->all());

        $this->actingAs($this->admin())->get('/rekap-kehadiran?q='.urlencode($p->email))
            ->assertSee('100%')
            ->assertSee('Completed');
    }
}
