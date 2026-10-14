<?php

namespace Tests\Feature\Katalog;

use App\Modules\Katalog\Models\Batch;
use App\Modules\Katalog\Models\Skema;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabelBulanTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_label_bulan_benar_pada_hari_ke_31(): void
    {
        // Tanggal 31 Oktober: November hanya 30 hari, sehingga pembacaan "Y-m"
        // tanpa tanda seru akan meluap menjadi 1 Desember.
        Carbon::setTestNow('2026-10-31 10:00:00');

        $skema = Skema::create(['nama_skema' => 'BP', 'durasi_online' => 3, 'durasi_offline' => 0.5]);
        $batch = Batch::create(['id_skema' => $skema->id_skema, 'periode' => '2026-11', 'mode' => 'Online', 'status' => 'Planned']);

        $this->assertSame('Batch 1 · November 2026 · Online', $batch->nama_siklus);
    }
}
