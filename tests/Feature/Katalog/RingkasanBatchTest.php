<?php

namespace Tests\Feature\Katalog;

use App\Modules\Akun\Models\User;
use App\Modules\Katalog\Models\Batch;
use App\Modules\Katalog\Models\Skema;
use App\Modules\Katalog\Queries\RingkasanBatch;
use App\Modules\Peserta\Models\Peserta;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ringkasan kesiapan batch pada Batch Management: peserta tervalidasi,
 * durasi terjadwal dibanding wajib, dan penanda siap atau perlu tindakan.
 */
class RingkasanBatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function batch(string $skema, string $periode, string $mode): Batch
    {
        return Batch::with('skema')
            ->where('id_skema', Skema::where('nama_skema', $skema)->value('id_skema'))
            ->where('periode', $periode)->where('mode', $mode)->firstOrFail();
    }

    private function ringkasan(Batch $b): array
    {
        return app(RingkasanBatch::class)->untuk(collect([$b]))[$b->id_batch];
    }

    public function test_batch_contoh_siap(): void
    {
        $r = $this->ringkasan($this->batch('BP', '2026-10', 'Online'));

        $this->assertSame(14, $r['peserta']);
        $this->assertSame(14, $r['valid']);
        $this->assertSame(0, $r['menunggu']);
        $this->assertSame(3, $r['sesi']);
        $this->assertSame(3.0, $r['terjadwal']);
        $this->assertSame(3.0, $r['wajib']);
        $this->assertTrue($r['siap']);
    }

    public function test_peserta_menunggu_validasi_membuat_batch_perlu_tindakan(): void
    {
        $b = $this->batch('BP', '2026-10', 'Online');
        $baru = Peserta::create(['nama' => 'Peserta Baru', 'email' => 'baru@example.test']);
        $b->peserta()->attach($baru->id_peserta, ['status' => 'Registered']);

        $r = $this->ringkasan($b);

        $this->assertSame(15, $r['peserta']);
        $this->assertSame(14, $r['valid']);
        $this->assertSame(1, $r['menunggu']);
        $this->assertFalse($r['siap']);
        $this->assertContains('1 menunggu validasi', $r['masalah']);
    }

    public function test_batch_kosong_tanpa_peserta_dan_sesi(): void
    {
        $skema = Skema::where('nama_skema', 'BP')->firstOrFail();
        $b = Batch::create(['id_skema' => $skema->id_skema, 'periode' => '2027-03', 'mode' => 'Online', 'status' => 'Planned'])->load('skema');

        $r = $this->ringkasan($b);

        $this->assertSame(0, $r['peserta']);
        $this->assertSame(0, $r['sesi']);
        $this->assertFalse($r['siap']);
        $this->assertSame(['Belum ada peserta', 'Belum ada sesi'], $r['masalah']);
    }

    public function test_durasi_kurang_disebut_pada_masalah(): void
    {
        $b = $this->batch('JNA', '2026-10', 'Online');
        $sesi = $b->sesi()->orderBy('tanggal')->firstOrFail();
        $b->sesi()->detach($sesi->id_sesi);

        $r = $this->ringkasan($b->fresh('skema'));

        $this->assertFalse($r['siap']);
        $this->assertTrue(collect($r['masalah'])->contains(fn ($m) => str_starts_with($m, 'Durasi kurang')));
    }

    public function test_halaman_batch_management_menampilkan_ringkasan(): void
    {
        $admin = User::where('email', 'admin@example.test')->firstOrFail();

        $this->actingAs($admin)->get('/data-master?tab=batch')
            ->assertOk()
            ->assertSee('Semua siap')
            ->assertSee('14', false)
            ->assertSee('tervalidasi');
    }
}
