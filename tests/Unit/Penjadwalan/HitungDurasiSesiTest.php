<?php

namespace Tests\Unit\Penjadwalan;

use App\Modules\Penjadwalan\Actions\HitungDurasiSesi;
use PHPUnit\Framework\TestCase;

class HitungDurasiSesiTest extends TestCase
{
    public function test_sesi_online_selalu_satu_hari(): void
    {
        $this->assertSame(1.0, (new HitungDurasiSesi)->execute('Online', '19:00', '21:30'));
    }

    public function test_sesi_offline_sampai_empat_jam_setengah_hari(): void
    {
        $this->assertSame(0.5, (new HitungDurasiSesi)->execute('Offline', '08:30', '12:00'));
        $this->assertSame(0.5, (new HitungDurasiSesi)->execute('Offline', '13:00', '15:30'));
    }

    public function test_sesi_offline_lebih_dari_empat_jam_satu_hari(): void
    {
        $this->assertSame(1.0, (new HitungDurasiSesi)->execute('Offline', '08:30', '15:30'));
    }
}
