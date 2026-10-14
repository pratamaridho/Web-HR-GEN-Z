<?php

namespace Tests\Feature\Dashboard;

use App\Modules\Akun\Models\User;
use App\Modules\Katalog\Models\Batch;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerluTindakanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function sebagai(string $email): static
    {
        return $this->actingAs(User::where('email', $email)->firstOrFail());
    }

    public function test_data_lengkap_menampilkan_semua_siap(): void
    {
        $this->sebagai('admin@example.test')->get('/dashboard')
            ->assertOk()
            ->assertSee('Oktober 2026: semua siap')
            ->assertDontSee('perlu tindakan');
    }

    public function test_batch_tanpa_peserta_dan_durasi_kurang_ditampilkan_dengan_alasan(): void
    {
        $bp = Batch::where('periode', '2026-10')->with('skema')->get()->firstWhere('skema.nama_skema', 'BP');
        Batch::create(['id_skema' => $bp->id_skema, 'periode' => '2026-10', 'mode' => 'Offline', 'status' => 'Planned']);

        $this->sebagai('admin@example.test')->get('/dashboard')
            ->assertOk()
            ->assertSee('Oktober 2026: 2 hal perlu tindakan')
            ->assertSee('Durasi belum terpenuhi')
            ->assertSee('BP')
            ->assertDontSee('kurang 0,5 hari')
            ->assertSee('Batch belum punya peserta')
            ->assertSee('Atur jadwal')
            ->assertSee('Tambah peserta');
    }

    public function test_management_melihat_panel_tanpa_tombol_aksi(): void
    {
        $bp = Batch::where('periode', '2026-10')->with('skema')->get()->firstWhere('skema.nama_skema', 'BP');
        Batch::create(['id_skema' => $bp->id_skema, 'periode' => '2026-10', 'mode' => 'Offline', 'status' => 'Planned']);

        $this->sebagai('management@example.test')->get('/dashboard')
            ->assertOk()
            ->assertSee('perlu tindakan')
            ->assertDontSee('Atur jadwal')
            ->assertDontSee('Tambah peserta');
    }
}
