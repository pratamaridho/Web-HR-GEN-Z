<?php

namespace Tests\Feature\Katalog;

use App\Modules\Katalog\Models\Batch;
use App\Modules\Katalog\Models\Skema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NamaBatchTest extends TestCase
{
    use RefreshDatabase;

    private function batch(Skema $skema, string $periode, string $mode): Batch
    {
        return Batch::create([
            'id_skema' => $skema->id_skema, 'periode' => $periode, 'mode' => $mode, 'status' => 'Planned',
        ]);
    }

    public function test_nama_memuat_urutan_nama_lengkap_tahun_dan_mode(): void
    {
        $jwp = Skema::create(['nama_skema' => 'JWP', 'durasi_online' => 4, 'durasi_offline' => 1]);

        $batch = $this->batch($jwp, '2026-10', 'Online');

        $this->assertSame('Batch 1 · Junior Web Programmer 2026 · Online', $batch->nama);
    }

    public function test_urutan_naik_per_skema_dalam_tahun_yang_sama(): void
    {
        $jwp = Skema::create(['nama_skema' => 'JWP', 'durasi_online' => 4, 'durasi_offline' => 1]);
        $bp = Skema::create(['nama_skema' => 'BP', 'durasi_online' => 3, 'durasi_offline' => 0.5]);

        $oktober = $this->batch($jwp, '2026-10', 'Online');
        $november = $this->batch($jwp, '2026-11', 'Offline');
        $lainnya = $this->batch($bp, '2026-10', 'Online');
        $tahunDepan = $this->batch($jwp, '2027-01', 'Online');

        $this->assertStringStartsWith('Batch 1 ·', $oktober->nama);
        $this->assertStringStartsWith('Batch 2 ·', $november->nama);
        $this->assertStringStartsWith('Batch 1 ·', $lainnya->nama);
        $this->assertStringStartsWith('Batch 1 ·', $tahunDepan->nama);
    }

    public function test_skema_di_luar_daftar_memakai_kodenya(): void
    {
        $baru = Skema::create(['nama_skema' => 'XYZ', 'durasi_online' => 2, 'durasi_offline' => 1]);

        $this->assertSame('Batch 1 · XYZ 2026 · Online', $this->batch($baru, '2026-10', 'Online')->nama);
    }

    public function test_nomor_mengikuti_siklus_bukan_skema(): void
    {
        $jwp = Skema::create(['nama_skema' => 'JWP', 'durasi_online' => 4, 'durasi_offline' => 1]);
        $bp = Skema::create(['nama_skema' => 'BP', 'durasi_online' => 3, 'durasi_offline' => 0.5]);

        $this->batch($jwp, '2026-10', 'Online');
        $novemberBp = $this->batch($bp, '2026-11', 'Online');

        // BP tidak punya batch Oktober, tetapi siklus Oktober sudah ada, jadi November = Batch 2.
        $this->assertSame('Batch 2 · November 2026 · Online', $novemberBp->nama_siklus);
    }

    public function test_enam_skema_pada_siklus_yang_sama_berbagi_nomor(): void
    {
        $a = Skema::create(['nama_skema' => 'JWP', 'durasi_online' => 4, 'durasi_offline' => 1]);
        $b = Skema::create(['nama_skema' => 'BP', 'durasi_online' => 3, 'durasi_offline' => 0.5]);

        $this->assertSame(
            $this->batch($a, '2026-10', 'Online')->nama_siklus,
            $this->batch($b, '2026-10', 'Online')->nama_siklus,
        );
    }
}
